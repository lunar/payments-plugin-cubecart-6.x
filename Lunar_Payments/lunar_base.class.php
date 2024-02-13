<?php

if (!class_exists('\\Lunar\\Lunar')) {
    require_once('vendor/autoload.php');
}

class LunarPaymentsBase
{
    const REMOTE_URL = 'https://pay.lunar.money/?id=';
    const TEST_REMOTE_URL = 'https://hosted-checkout-git-develop-lunar-app.vercel.app/?id=';

    protected $paymentMethod = '';
    protected $moduleCode = '';
    protected $_lang;
    protected $_db;
    protected $_module;
    protected $_basket;
    protected $intentKey = '_lunar_intent_id';
    protected $currencyCode = '';
    protected $testMode = false;
    protected $args = [];

    /** @var \Lunar\Lunar */
    protected $apiClient;

    public function __construct($module = false, $basket = false)
    {
        $GLOBALS['language']->loadDefinitions('lunar_text', CC_ROOT_DIR.'/modules/plugins/Lunar_Payments/language', 'module.definitions.xml');
        $this->_lang = $GLOBALS['language']->getStrings('lunar_text');

        $this->_db = $GLOBALS['db'];

        $this->_module = $module;

        $basket = $basket ?: $GLOBALS['cart']->basket;
        $this->_basket = $basket;

        $this->testMode = !!$_COOKIE['lunar_testmode'];

        $this->apiClient = new \Lunar\Lunar($this->_module['app_key'], null, $this->testMode);
    }

    public function transfer()
    {
        return [
            'action'  => 'index.php?_g=rm&type=plugins&cmd=process&module='.$this->moduleCode,
            'method'  => 'post',
            'target'  => '_self',
            'submit'  => 'auto',
        ];
    }

    /**
     * REDIRECT
     */
    public function process()
    {
        $this->setArgs();

        try {
            $paymentIntentId = $this->apiClient->payments()->create($this->args);
            $this->savePaymentIntent($paymentIntentId);
        } catch(Lunar\Exception\ApiException $e) {
            $this->displayErrorMessage($e->getMessage());
        }

        if (! $paymentIntentId) {
            $this->displayErrorMessage($this->_lang['error_create_intent']);
        }

        httpredir(($this->testMode ? self::TEST_REMOTE_URL : self::REMOTE_URL) . $paymentIntentId);        
    }

    /**
     * CALLBACK
     */
    public function call()
    {
        if (empty($orderId = sanitizeVar($_GET['orderid']))) {
            $this->displayErrorMessage($this->_lang['idsmissing']);
        }

        $transactionId = $this->getPaymentIntent();
        // $transactionId = '5ebc58fb-edf9-52c3-b10b-f9b5e4a90581';

        $order = Order::getInstance();
        $orderSummary = $order->getSummary($orderId);

        $transactionData = [];
        $transactionData['status'] = 'Authorized';
        $transactionData['trans_id'] = $transactionId;
        $transactionData['order_id'] = $orderId;
        $transactionData['amount'] = sprintf("%.2f", $orderSummary["total"]);
        $transactionData['customer_id'] = $orderSummary["customer_id"];
        $transactionData['gateway'] = $this->moduleCode;

        $transactionData['notes'] = [];

        if ($orderSummary['status'] != '1') {
            $this->displayErrorMessage($this->_lang['txn_exists']); // @TODO maybe we need another text error here
        }

        $transaction_exists  = $this->_db->select('CubeCart_transactions', ['id'], ['trans_id' => $transactionId]);
        if ($transaction_exists) {
            $this->displayErrorMessage($this->_lang['txn_exists']);
        }

        try {
            $apiResponse = $this->apiClient->payments()->fetch($transactionId);
        } catch (\Lunar\Exception\ApiException $e) {
            $this->displayErrorMessage($e->getMessage());
        }

        if (empty($apiResponse)) {
            $this->displayErrorMessage($this->_lang['invalidtxn']);
        }

        if (empty($apiResponse['authorisationCreated'])) {
            $this->displayErrorMessage($this->_lang['confirmerror']);
        }

        $order->paymentStatus(Order::PAYMENT_SUCCESS, $orderId);

        $transactionData['notes'][] = $this->_lang['paysuccess'];

        $order->logTransaction($transactionData);

        setcookie($this->intentKey, null, 1);

        if ('instant' == $this->_module['capture_mode']) {
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

            /**
             * We need at least 1 second between authorization and capture log
             * to have them sorted in the right order by time (cubecart defaults)
             */
            sleep(1);

            if (isset($apiResponse['captureState'])) {
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

            $order->logTransaction($transactionData);

        } else {
            $order->orderStatus(Order::ORDER_PROCESS, $orderId);
        }

        httpredir(CC_STORE_URL.'/index.php?_a=complete');
    }

    /**
     * 
     */
    private function setArgs()
    {
        $orderClass = Order::getInstance();
        $orderSummary = $orderClass->getSummary($this->_basket['cart_order_id']);

        $this->currencyCode = $orderSummary['currency'];

        $address = implode(', ', [$orderSummary['line1'], $orderSummary['line2'], $orderSummary['town'], 
            $orderSummary['state'], $orderSummary['postcode'], $orderSummary['country']]);

        $this->args = [
            'integration' => [
                'key' => $this->_module['public_key'],
                'name' => $this->_module['shop_name'] ?: $GLOBALS['config']->get('config', 'store_name'),
                'logo' => $this->_module['logo_url'],
            ],
            'amount' => [
                'currency' => $this->currencyCode,
                'decimal' => (string) $orderSummary['total'],
            ],
            'custom' => [
                'orderId' => $orderSummary['cart_order_id'],
                'products' => $this->getFormattedProducts(),
                'customer' => [
                    'name' => $orderSummary['first_name'].' '.$orderSummary['last_name'],
                    'email' => $orderSummary['email'],
                    'phoneNo' => $orderSummary['phone'],
                    'address' => $address,
                    'ip' => get_ip_address(),
                ],
                'platform' => [
                    'name' => 'CubeCart',
                    'version' => CC_VERSION,
                ],
                'lunarPluginVersion' => $this->getPluginVersion(),
            ],
            'redirectUrl' => CC_STORE_URL.'/index.php?_g=rm&type=plugins&cmd=call&module='.$this->moduleCode
                                .'&orderid='.$orderSummary['cart_order_id'],
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
        return setcookie($this->intentKey, $paymentIntentId, 0, '', '', false, true);
    }

    /**
     * 
     */
    private function getPaymentIntent()
    {
        return isset($_COOKIE[$this->intentKey]) ? $_COOKIE[$this->intentKey] : null;
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
    private function displayErrorMessage(string $errorMessage)
    {
        setcookie($this->intentKey, null, 1);

        $GLOBALS['gui']->setError($errorMessage);

        httpredir(CC_STORE_URL.'/index.php?_a=checkout');
    }

    public function form() {}
    public function repeatVariables() {}
	public function fixedVariables() {}
}
