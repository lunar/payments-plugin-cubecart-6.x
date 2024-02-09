<?php

if (!class_exists('\\Lunar\\Lunar')) {
    require_once('vendor/autoload.php');
}

class Gateway
{
    const REMOTE_URL = 'https://pay.lunar.money/?id=';
    const TEST_REMOTE_URL = 'https://hosted-checkout-git-develop-lunar-app.vercel.app/?id=';

    // private $_config;
    private $_module;
    private $_lang;
    private $_basket;

    private $paymentMethod = 'card';

    private $intentKey = '_lunar_intent_id';
    private $currencyCode = '';
    private $testMode = false;
    private $args = [];

    /** @var \Lunar\Lunar */
    private $apiClient;

    public function __construct($module = false, $basket = false)
    {
        $GLOBALS['language']->loadDefinitions('lunar_text', CC_ROOT_DIR . '/modules/plugins/Lunar_Payments/language', 'module.definitions.xml');
        // $this->_config  = $GLOBALS['config']->get('config');

        $this->_module  = $module;
        $this->_lang = $GLOBALS['language']->getStrings('lunar_text');
		$this->_basket	= $basket ?: $GLOBALS['cart']->basket;
        $this->testMode = !!$_COOKIE['lunar_testmode'];

        $this->apiClient = new \Lunar\Lunar($this->_module['app_key'], null, $this->testMode);
    }

    public function transfer()
    {
        return [
            'action'  => 'index.php?_g=rm&type=plugins&cmd=call&module=Lunar_Payments',
            'method'  => 'post',
            'target'  => '_self',
            'submit'  => 'auto',
        ];
    }

    /**
     * REDIRECT
     */
    public function call()
    {
        $this->setArgs();

        try {
            $paymentIntentId = $this->apiClient->payments()->create($this->args);
            $this->savePaymentIntent($paymentIntentId);
        } catch(Lunar\Exception\ApiException $e) {
            $this->redirectBackWithNotification($e->getMessage());
        }

        if (! $paymentIntentId) {
            $this->redirectBackWithNotification('An error occurred creating payment intent. 
                Please try again or contact system administrator.'
            );
        }

        httpredir(($this->testMode ? self::TEST_REMOTE_URL : self::REMOTE_URL) . $paymentIntentId);

        
    }

    /**
     * CALLBACK
     */
    public function process()
    {
        if (empty($orderId = sanitizeVar($_GET['orderid']))) {
            $this->redirectBackWithNotification($this->_lang['idsmissing']);
        }

        // $orderId = sanitizeVar($_GET['orderid']);

        $transactionId = $this->getPaymentIntent();

        $order = Order::getInstance();
        $orderSummary = $order->getSummary($orderId);


        $transactionData = [];
        $transactionData['status'] = 'Authorized';
        $transactionData['trans_id'] = $transactionId;
        $transactionData['order_id'] = $orderId;
        $transactionData['amount'] = sprintf("%.2f", $orderSummary["total"]);
        $transactionData['customer_id'] = $orderSummary["customer_id"];
        //$transData['gateway'] = $this->_module['name'];
        $transactionData['gateway'] = "Lunar_Payments";
        $transactionData['notes'] = [];

        if ($orderSummary['status'] != '1') {
            return;
        }

        $trans_id  = $GLOBALS['db']->select('CubeCart_transactions', ['id'], ['trans_id' => $transactionId]);
        if ($trans_id) {
            $this->redirectBackWithNotification($this->_lang['txn_exists']);
        }

        try {
            $apiResponse = $this->apiClient->payments()->fetch($transactionId);
        } catch (\Lunar\Exception\ApiException $e) {
            $this->redirectBackWithNotification($e->getMessage());
        }

        if (empty($apiResponse)) {
            $this->redirectBackWithNotification($this->_lang['invalidtxn']);
        }
        if (empty($apiResponse['authorisationCreated'])) {
            $this->redirectBackWithNotification($this->_lang['confirmerror']);
        }

        $order->paymentStatus(Order::PAYMENT_SUCCESS, $orderId);
        $order->orderStatus(Order::ORDER_PROCESS, $orderId);

        $transactionData['notes'][] = $this->_lang['paysuccess'];

        if ($this->_module['capture_mode'] == 'instant') {
            try {
                $apiResponse = $this->apiClient->payments()->capture($transactionId, [
                    'amount' => [
                        'currency' => $orderSummary["currency"],
                        'decimal' => (string) $orderSummary["total"],
                    ]
                ]);
            } catch (\Lunar\Exception\ApiException $e) {
                $transactionData['notes'][] = $e->getMessage();
            }

            if (!empty($apiResponse) {
                if ('completed' === $apiResponse['captureState']) {
                    $order->orderStatus(Order::ORDER_COMPLETE, $orderId);
                    $transactionData['status'] = 'Captured';
                    $transactionData['notes'][] = $this->_lang['captured'];
                }
                if ('declined' === $apiResponse['captureState']) {
                    $transactionData['status'] = 'Capture Failed';
                    $transactionData['notes'][] = $apiResponse['declinedReason']['error'];
                }
            }
        }

        $order->logTransaction($transactionData);

        httpredir(CC_STORE_URL.'index.php?_a=complete');
        // return true;
    }

    /**
     * 
     */
    private function setArgs()
    { 
        $this->currencyCode = $GLOBALS['config']->get('config','default_currency');
        if ($GLOBALS['session']->has('currency', 'client')) {
            $this->currencyCode = $GLOBALS['session']->get('currency', 'client');
        }

        $address = $this->_basket['billing_address']; // default billing address, true for all addresses
        $addressLine = implode(', ', [$address['line1'], $address['line2'], $address['town'], $address['state'], $address['postcode'], $address['country']]);

        $this->args = [
            'integration' => [
                'key' => $this->_module['public_key'],
                'name' => $this->_module['shop_name'] ?: $GLOBALS['config']->get('config', 'store_name'),
                'logo' => $this->_module['logo_url'],
            ],
            'amount' => [
                'currency' => $this->currencyCode,
                'decimal' => (string) $this->_basket['total'],
            ],
            'custom' => [
                'orderId' => $this->_basket['cart_order_id'],
                'products' => $this->getFormattedProducts(),
                'customer' => [
                    'name' => $address['first_name']." ".$address['last_name'],
                    'email' => $address['email'],
                    'phoneNo' => $address['phone'],
                    'address' => $addressLine,
                    'ip' => get_ip_address(),
                ],
                'platform' => [
                    'name' => 'CubeCart',
                    'version' => CC_VERSION,
                ],
                'lunarPluginVersion' => $this->getPluginVersion(),
            ],
            'redirectUrl' => CC_STORE_URL.'index.php?_g=rm&type=plugins&cmd=process&module=Lunar_Payments'
                                .'&orderid='.$this->_basket['cart_order_id'],
            'preferredPaymentMethod' => $this->paymentMethod,
        ];

        if ($this->_module['configuration_id']) {
            $this->args['mobilePayConfiguration'] = [
                'configurationID' => $this->_module['configuration_id'],
                'logo' => $this->_module['logo_url'],
            ];
        }

        if ($this->testMode) {
            $this->args['test'] = $this->getTestObject();
        }
    }

    /**
     * 
     */
    private function savePaymentIntent($paymentIntentId)
    {
        $this->_basket[$this->intentKey] = $paymentIntentId;
    }

    /**
     * 
     */
    private function getPaymentIntent()
    {
        return $this->_basket[$this->intentKey];
    }

    /**
     * 
     */
    private function getFormattedProducts()
    {
        $products = $this->_basket['contents'];
        
		$products_array = [];
        foreach ( $products as $product ) {
			$products_array[] = [
				'ID' => $product['id'],
				'Name' => $product['name'],
				'Quantity' => $product['quantity']
            ];
		}

        return $products_array;
    }

    /**
     *
     */
    private function getTestObject(): array
    {
        return [
            "card"        => [
                "scheme"  => "supported",
                "code"    => "valid",
                "status"  => "valid",
                "limit"   => [
                    "decimal"  => "25000.99",
                    "currency" => $this->currencyCode,
                    
                ],
                "balance" => [
                    "decimal"  => "25000.99",
                    "currency" => $this->currencyCode,
                    
                ]
            ],
            "fingerprint" => "success",
            "tds"         => [
                "fingerprint" => "success",
                "challenge"   => true,
                "status"      => "authenticated"
            ],
        ];
    }

    /**
     *
     */
    private function getPluginVersion()
    {
        $xml = new SimpleXMLElement(file_get_contents(dirname(__FILE__).'/config.xml'));

        if(!empty($xml->info->version)) {
            return (string) $xml->info->version;
        }
    }

    /**
     * 
     */
    private function redirectBackWithNotification(string $errorMessage)
    {
        $GLOBALS['main']->errorMessage($errorMessage);
        httpredir(CC_STORE_URL.'index.php?_a=checkout');
    }


    // /**
    //  * SET ARGS
    //  */
    // private function setArgs()
    // {
    //     $orderClass = Order::getInstance();
    //     $orderSummary = $orderClass->getSummary($this->_basket['cart_order_id']);

    //     $this->currencyCode = $orderSummary['currency'];

    //     $address = implode(', ', [$orderSummary['line1'], $orderSummary['line2'], $orderSummary['town'], 
    //         $orderSummary['state'], $orderSummary['postcode'], $orderSummary['country']]);

    //     $this->args = [
    //         'integration' => [
    //             'key' => $this->_module['public_key'],
    //             'name' => $this->_module['shop_name'],
    //             'logo' => $this->_module['logo_url'],
    //         ],
    //         'amount' => [
    //             'currency' => $this->currencyCode,
    //             'decimal' => (string) $orderSummary['total'],
    //         ],
    //         'custom' => [
    //             'orderId' => $orderSummary['cart_order_id'],
    //             'products' => $this->getFormattedProducts(),
    //             'customer' => [
    //                 'name' => $orderSummary['first_name'].' '.$orderSummary['last_name'],
    //                 'email' => $orderSummary['email'],
    //                 'phoneNo' => $orderSummary['phone'],
    //                 'address' => $address,
    //                 'ip' => get_ip_address(),
    //             ],
    //             'platform' => [
    //                 'name' => 'CubeCart',
    //                 'version' => CC_VERSION,
    //             ],
    //             'lunarPluginVersion' => $this->getPluginVersion(),
    //         ],
    //         'redirectUrl' => '',
    //         'preferredPaymentMethod' => $this->paymentMethod,
    //     ];

    //     if ($this->_module['configuration_id']) {
    //         $this->args['mobilePayConfiguration'] = [
    //             'configurationID' => $this->_module['configuration_id'],
    //             'logo' => $this->_module['logo_url'],
    //         ];
    //     }

    //     if ($this->testMode) {
    //         $this->args['test'] = $this->getTestObject();
    //     }
    // }


    public function form() {}
    public function repeatVariables() {}
	public function fixedVariables() {}
}
