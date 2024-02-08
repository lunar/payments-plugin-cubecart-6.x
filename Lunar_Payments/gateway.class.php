<?php

class Gateway
{
    // private $_config;
    private $_module;
    private $_lang;

    public function __construct($module = false, $basket = false)
    {
        // $this->_config  = $GLOBALS['config']->get('config');
        $this->_module  = $module;
        $GLOBALS['language']->loadDefinitions('lunar_text', CC_ROOT_DIR . '/modules/plugins/Lunar_Payments/language', 'module.definitions.xml');
        $this->_lang = $GLOBALS['language']->getStrings('lunar_text');
    }

    public function transfer()
    {
        $transfer  = [
            'action'  => 'index.php?_g=rm&type=plugins&cmd=call&module=Lunar_Payments&cart_order_id=' . $GLOBALS['cart']->basket['cart_order_id'],
            'method'  => 'post',
            'target'  => '_self',
            'submit'  => 'auto',
        ];
        return $transfer;
    }

    public function call()
    {
        $GLOBALS['gui']->changeTemplateDir(dirname(__FILE__) . '/' . 'skin/');

        $GLOBALS['smarty']->assign('MODULE', $this->_module);

        $order = Order::getInstance();
        $cart_order_id = sanitizeVar($_GET['cart_order_id']);
        $orderSummary = $order->getSummary($cart_order_id);
        // $GLOBALS['smarty']->assign('ORDERID', $cart_order_id);

        $GLOBALS['smarty']->display('redirect.tpl');
    }

    public function process()
    {
        if (empty($_GET['orderid']) && empty($_GET['orderid']) && empty($_GET['transactionid']) && empty($_GET['transactionid'])) {
            $GLOBALS['main']->errorMessage($GLOBALS['language']->lunar_text['idsmissing']);
            httpredir(currentPage(['_g', 'type', 'cmd', 'module', 'transactionid', 'orderid'], ['_a' => 'checkout']));
            return false;
        }

        $orderid = sanitizeVar($_GET['orderid']);

        $transactionid = sanitizeVar($_GET['transactionid']);

        $order = Order::getInstance();
        $orderSummary = $order->getSummary($orderid);

        // txn log
        $transData = [];
        $transData['status'] = 'Pending';
        $transData['trans_id'] = $transactionid;
        $transData['order_id'] = $orderid;
        $transData['amount'] = sprintf("%.2f", $orderSummary["total"]);
        $transData['customer_id'] = $orderSummary["customer_id"];
        //$transData['gateway'] = $this->_module['name'];
        $transData['gateway'] = "Lunar_Payments";
        $transData['notes'] = [];

        $confirmed = false;
        if ($orderSummary['status'] == '1') {

            // set app key
            $appKey = $this->_module['app_key'];

            if (!$appKey) {
                return;
            }

            $apiClient = new \Lunar\Lunar($appKey, null, !!$_COOKIE['lunar_testmode']);

            // fetch transaction
            $apiResponse = false;
            try {
                $apiResponse = $apiClient->payments()->fetch($transactionid);
            } catch (\Lunar\Exception\ApiException $e) {
                // Unknown api error
                $transData['notes'][] = $e->getMessage();
            }

            if (empty($apiResponse)) {
                $transData['notes'][] = $this->_lang['invalidtxn'];
            }
            if (isset($apiResponse['error'])) {
                $transData['notes'][] = $this->_lang['confirmerror'];
            }

            if (!isset($apiResponse['successful'])) {
                $transData['notes'][] = $this->_lang['notsuccessful'];
            }

            // if ('amount mismatch') {
            //   $transData['notes'][] = $this->_lang['amountmismatch'];
            // }

            if (empty($transData['notes'])) {
                // payment successful, set order&payment status
                $order->paymentStatus(Order::PAYMENT_SUCCESS, $orderid);
                $order->orderStatus(Order::ORDER_PROCESS, $orderid);
                $transData['notes'][] = $this->_lang['paysuccess'];
                if ($apiResponse['pendingAmount']) {
                    $transData['status'] = 'Authorized';
                }

                // instant capture
                if ($this->_module['capture_mode'] == 'instant') {
                    try {
                        $cap = $transactions->capture($transactionid, [
                            'amount' => [
                                'currency' => $orderSummary["currency"],
                                'decimal' => (string) $orderSummary["total"],
                            ]
                        ]);
                    } catch (\Lunar\Exception\ApiException $e) {
                        // Unknown api error
                        $transData['notes'][] = $e->getMessage();
                    }

                    if ($cap['successful'] && $cap['capturedAmount']) {
                        $order->orderStatus(Order::ORDER_COMPLETE, $orderid);
                        $transData['status'] = 'Captured';
                        $transData['notes'][] = $this->_lang['captured'];
                    }
                }

                $confirmed = true;
            }

            // Let user know, if errors on payment
            if (!$confirmed) {
                $GLOBALS['main']->errorMessage($this->_lang['user_payment_error']);
            }
        }

        // transactionid already exists?
        $trans_id  = $GLOBALS['db']->select('CubeCart_transactions', ['id'], ['trans_id' => $transactionid]);
        if ($trans_id) {
            $transData['notes'][]  = $this->_lang['txn_exists'];
        }

        // Log transaction
        $order->logTransaction($transData);

        // Everythings good, continue to page 'complete'
        if ($confirmed) {
            httpredir(currentPage(['_g', 'type', 'cmd', 'module', 'transactionid', 'orderid'], ['_a' => 'complete']));
            return true;
        }

        // Unknown errors
        $GLOBALS['main']->errorMessage($GLOBALS['language']->lunar_text['error_unknown']);
        httpredir(currentPage(['_g', 'type', 'cmd', 'module', 'transactionid', 'orderid'], ['_a' => 'checkout']));
        return false;
    }

    public function form()
    {
        return false;
    }
}
