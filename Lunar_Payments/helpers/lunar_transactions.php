<?php

if (!class_exists('\\Lunar\\Lunar')) {
    require_once(dirname(__DIR__).'/vendor/autoload.php');
}

class lunarTransactions
{
    private $_lang;
    private $_config;
    private $order;
    private $orderId;
    private $apiClient;
    private $lastTransaction;
    private $orderSummary;
    private $actionType;
    private $langKey;
    private $canInsertTransaction;
    private $newOrderStatus;

    public function __construct($moduleName, $orderId)
    {
        if (!$this->orderId = $orderId) {
            return;
        }

        $this->_lang = $GLOBALS['language']->getStrings('lunar_text');

        $this->_config = $GLOBALS['config']->get($moduleName);
    
        $this->apiClient = new \Lunar\Lunar($this->_config['app_key'], null, !!$_COOKIE['lunar_testmode']);
    
        $this->order = Order::getInstance();
        $this->orderSummary = $this->order->getSummary($this->orderId);
    
        /** @var Database */
        $db = $GLOBALS['db'];
    
        $lastTransaction = $db->select('CubeCart_transactions', false,
            [
                'order_id' => $this->orderId,
                'gateway' => $moduleName
            ],
            [
                'time' => 'DESC',
            ]
        );

        if (empty($lastTransaction) || empty($lastTransaction[0]['trans_id'])) {
            $this->canInsertTransaction = false;
        } else {
            $this->lastTransaction = $lastTransaction[0];
        }
    }

    /**
     * 
     */
    public function captureTransaction()
    {
        $this->actionType = 'capture';
        $this->langKey = 'captured';
        $this->canInsertTransaction = ($this->lastTransaction['status'] === 'Authorized');

        $this->processTransaction();
    }

    /**
     * 
     */
    public function refundTransaction()
    {
        $this->actionType = 'refund';
        $this->langKey = 'refunded';
        $this->canInsertTransaction = ($this->lastTransaction['status'] === 'Captured');
        /**
         * Set new status on order that has been refunded 
         * 70 is an arbitrarily chosen order status ID, so as not to interfere with any other status.
         */
        $this->newOrderStatus = 70;

        $this->processTransaction();
    }

    /**
     * 
     */
    public function cancelTransaction()
    {
        $this->actionType = 'cancel';
        $this->langKey = 'voided';
        $this->canInsertTransaction = ($this->lastTransaction['status'] === 'Authorized');
        $this->newOrderStatus = Order::ORDER_CANCELLED;

        $this->processTransaction();
    }

    /**
     * 
     */
    private function processTransaction()
    {
        if (empty($this->canInsertTransaction)) {
            return;
        }

        $transaction = $this->lastTransaction;
        $transaction['notes'] = [];
        unset($transaction['id']);

        $actionType = $this->actionType;

        $g = isset($GLOBALS['_GET']['_g']) ? $GLOBALS['_GET']['_g'] : null;
        $isAdminRequest = ($g === 'orders');

        try {
            $apiResponse = $this->apiClient->payments()->{$actionType}(
                $transaction['trans_id'],
                [
                    'amount' => [
                        'currency' => $this->orderSummary['currency'],
                        'decimal' => (string) $transaction['amount'],
                    ]
                ]
            );
        } catch(Lunar\Exception\ApiException $e) {
            $transaction['notes'][] = $e->getMessage();
        }

        if (!empty($apiResponse["{$actionType}State"]) && 'completed' == $apiResponse["{$actionType}State"]) {
            
            if (!empty($this->newOrderStatus)) {
                $this->order->orderStatus($this->newOrderStatus, $this->orderId);
            }

            $transaction['notes'][] = $this->trans($this->langKey);
            $transaction['status'] = ucfirst($this->langKey);

            $this->order->logTransaction($transaction);

            if ($isAdminRequest) {
                $GLOBALS['main']->successMessage($this->trans($this->langKey));
            }
            
        } elseif ('declined' === $apiResponse["{$actionType}State"]) {
            $transaction['status'] = ucfirst($actionType).' Failed';
            $transaction['notes'][] = $apiResponse['declinedReason']['error'];

            $this->order->logTransaction($transaction);

        } else {
            if ($isAdminRequest) {
                $GLOBALS['main']->errorMessage($this->trans('error_general_admin_txn'));
            }
        }
    }

    /**
     * 
     */
    private function trans($key)
    {
        return isset($this->_lang[$key]) ? ($this->_lang[$key]) : '';
    }

}
