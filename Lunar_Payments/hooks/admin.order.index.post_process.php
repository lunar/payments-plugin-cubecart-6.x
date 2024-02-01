<?php
if (!defined('CC_DS')) die('Access Denied');

/* Capture block for authorized payments */
// orderid exists
if ($record['cart_order_id']) {
    // when order status set to complete
    if (isset($_POST['order']['status']) && $_POST['order']['status'] == '3') {
        // get latest transaction status, authorized|captured
        $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id' => $record['cart_order_id'], 'gateway' => 'Lunar_Payments'), array('time' => 'DESC'));
        if ($txns) {
            // if authorized, attempt to capture
            if ($txns[0]['status'] == 'Authorized') {
                // we have txnid
                if ($txns[0]['trans_id']) {
                    // load module vars
                    $modcfg = $GLOBALS['config']->get('Lunar_Payments');
                    $modlang = $GLOBALS['language']->getStrings('lunar_text');

                    // create a new transaction log
                    $newlog = $txns[0];
                    unset($newlog['id']);
                    $newlog['notes'] = [];

                    // set app key
                    $appkey = $modcfg['app_key'];

                    // init Lunar
                    require_once(CC_ROOT_DIR . '/modules/plugins/Lunar_Payments/api/init.php');
                    $apiClient = new \Paylike\Paylike($appkey);
                    $transactions = $apiClient->transactions();
                    $order = Order::getInstance();
                    try {
                        $res = $transactions->capture(
                            $txns[0]['trans_id'],
                            [
                                'amount' => [
                                    'currency' => $order->getSummary($record['cart_order_id'])["currency"],
                                    'decimal' => (string) $txns[0]['amount'],
                                ]
                            ]
                        );
                    } catch (\Paylike\Exception\ApiException $e) {
                        // Unknown api error
                        $newlog['notes'][] = $e->getMessage();
                    }

                    if ($res['successful']) {
                        $newlog['notes'][] = $modlang['captured'];
                        $GLOBALS['main']->successMessage($modlang['captured']);
                        $newlog['status'] = 'Captured';
                    }

                    //save new log
                    $order->logTransaction($newlog);
                }
            }
        }
    }
}

/* Void block */
// void request posted
if (isset($GLOBALS['_POST']['confirm_lunar_void']) && $GLOBALS['_POST']['confirm_lunar_void']) {
    // get latest transaction status, authorized|captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id' => $record['cart_order_id'], 'gateway' => 'Lunar_Payments'), array('time' => 'DESC'));
    if ($txns) {
        // attempt to refund only when = Authorized
        if ($txns[0]['status'] == 'Authorized') {
            // load module vars
            $modcfg = $GLOBALS['config']->get('Lunar_Payments');
            $modlang = $GLOBALS['language']->getStrings('lunar_text');

            // create a new transaction log
            $newlog = $txns[0];
            unset($newlog['id']);
            $newlog['notes'] = [];

            // set app key
            $appkey = $modcfg['app_key'];

            // init Lunar
            require_once(CC_ROOT_DIR . '/modules/plugins/Lunar_Payments/api/init.php');
            $apiClient = new \Paylike\Paylike($appkey);
            $transactions = $apiClient->transactions();
            $order = Order::getInstance();
            try {
                $void = $transactions->void(
                    $txns[0]['trans_id'],
                    [
                        'amount' => [
                            'currency' => $order->getSummary($record['cart_order_id'])["currency"],
                            'decimal' => (string) $txns[0]['amount'],
                        ]
                    ]
                );
            } catch (\Paylike\Exception\ApiException $e) {
                // Unknown api error
                $newlog['notes'][] = $e->getMessage();
            }

            if ($void['successful']) {
                $newlog['notes'][] = $modlang['voided'];
                $GLOBALS['main']->successMessage($modlang['voided']);
                $newlog['status'] = 'Voided';

                // set new status on order that has been voided
                $order->orderStatus(Order::ORDER_CANCELLED, $record['cart_order_id']);
            }

            //save new log
            $order->logTransaction($newlog);
        }
    }
}

/* Refund block */
// refund request posted
if (isset($GLOBALS['_POST']['confirm_lunar_refund']) && $GLOBALS['_POST']['confirm_lunar_refund']) {
    // get latest transaction status, authorized|captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, array('order_id' => $record['cart_order_id'], 'gateway' => 'Lunar_Payments'), array('time' => 'DESC'));
    if ($txns) {
        // attempt to refund only when = Captured
        if ($txns[0]['status'] == 'Captured') {
            // load module vars
            $modcfg = $GLOBALS['config']->get('Lunar_Payments');
            $modlang = $GLOBALS['language']->getStrings('lunar_text');

            // create a new transaction log
            $newlog = $txns[0];
            unset($newlog['id']);
            $newlog['notes'] = [];

            // set app key
            $appkey = $modcfg['app_key'];

            // init Lunar
            require_once(CC_ROOT_DIR . '/modules/plugins/Lunar_Payments/api/init.php');
            $apiClient = new \Paylike\Paylike($appkey);

            $transactions = $apiClient->transactions();
            $order = Order::getInstance();
            try {
                $rfd = $transactions->refund(
                    $txns[0]['trans_id'],
                    [
                        'amount' => [
                            'currency' => $order->getSummary($record['cart_order_id'])["currency"],
                            'decimal' => (string) $txns[0]['amount'],
                        ]
                    ]
                );
            } catch (\Paylike\Exception\ApiException $e) {
                // Unknown api error
                $newlog['notes'][] = $e->getMessage();
            }

            if ($rfd['successful']) {
                $newlog['notes'][] = $modlang['refunded'];
                $GLOBALS['main']->successMessage($modlang['refunded']);
                $newlog['status'] = 'Refunded';

                // set new status on order that has been refunded
                // 70 is an arbitrarily chosen order status ID, so as not to interfere with any other status.
                $order->orderStatus(70, $record['cart_order_id']);
            }

            //save new log
            $order->logTransaction($newlog);
        }
    }
}
