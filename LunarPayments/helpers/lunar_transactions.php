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

    public function __construct($pluginCode, $orderId)
    {
        if (!$this->orderId = $orderId) {
            return;
        }

        $GLOBALS['language']->loadDefinitions('lunar_text', CC_ROOT_DIR.'/modules/plugins/LunarPayments/language', 'module.definitions.xml');
        $this->_lang = $GLOBALS['language']->getStrings('lunar_text');

        $this->_config = $GLOBALS['config']->get($pluginCode);
    
        $this->apiClient = new \Lunar\Lunar($this->_config['app_key'], null, !!$_COOKIE['lunar_testmode']);
    
        $this->order = Order::getInstance();
        $this->orderSummary = $this->order->getSummary($this->orderId);
    
        /** @var Database */
        $db = $GLOBALS['db'];
    
        $lastTransaction = $db->select('CubeCart_transactions', false,
            [
                'order_id' => $this->orderId,
                'gateway' => $pluginCode
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
     * It will VOID or REFUND transaction based on last order transaction from DB
     */
    public function cancelTransaction()
    {
        if ($this->lastTransaction['status'] === 'Captured') {
            $this->actionType = 'refund';
            $this->langKey = 'refunded';
            $this->canInsertTransaction = true;
        } elseif ($this->lastTransaction['status'] === 'Authorized') {
            $this->actionType = 'cancel';
            $this->langKey = 'voided';
            $this->canInsertTransaction = true;
        }
        
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

            $GLOBALS['main']->successMessage($this->trans($this->langKey));
            
        } elseif ('declined' === $apiResponse["{$actionType}State"]) {
            $transaction['status'] = ucfirst($actionType).' Failed';
            $transaction['notes'][] = $apiResponse['declinedReason']['error'];

            $this->order->logTransaction($transaction);

        } else {
            $GLOBALS['main']->errorMessage($this->trans('error_general_admin_txn'));
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
