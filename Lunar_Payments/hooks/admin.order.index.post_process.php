<?php
if (!defined('CC_DS')) die('Access Denied');

/* Capture block for authorized payments */
// orderid exists
if ($record['cart_order_id']) {
    // when order status set to complete
    if (isset($_POST['order']['status']) && $_POST['order']['status'] == '3') {
        // get latest transaction status, authorized|captured
        $txns = $GLOBALS['db']->select('CubeCart_transactions', false, ['order_id' => $record['cart_order_id'], 'gateway' => 'Lunar_Payments'], ['time' => 'DESC']);

        if (empty($txns) || $txns[0]['status'] !== 'Authorized') {
            return;
        }

        if (empty($txns[0]['trans_id'])) {
            return;
        }

        // load module vars
        $modcfg = $GLOBALS['config']->get('Lunar_Payments');
        $modlang = $GLOBALS['language']->getStrings('lunar_text');

        // create a new transaction log
        $newlog = $txns[0];
        unset($newlog['id']);

        $newlog['notes'] = [];

        // set app key
        $appkey = $modcfg['app_key'];

        $apiClient = new \Lunar\Lunar($appkey, null, !!$_COOKIE['lunar_testmode']);

        $order = Order::getInstance();
        try {
            $res = $apiClient->payments()->capture(
                $txns[0]['trans_id'],
                [
                    'amount' => [
                        'currency' => $order->getSummary($record['cart_order_id'])["currency"],
                        'decimal' => (string) $txns[0]['amount'],
                    ]
                ]
            );
        } catch (\Lunar\Exception\ApiException $e) {
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

/* Void block */
// void request posted
if (isset($GLOBALS['_POST']['confirm_lunar_void']) && $GLOBALS['_POST']['confirm_lunar_void']) {
    // get latest transaction status, authorized|captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, ['order_id' => $record['cart_order_id'], 'gateway' => 'Lunar_Payments'], ['time' => 'DESC']);

    // attempt to refund only when = Authorized
    if (empty($txns) || $txns[0]['status'] !== 'Authorized') {
        return;
    }

    // load module vars
    $modcfg = $GLOBALS['config']->get('Lunar_Payments');
    $modlang = $GLOBALS['language']->getStrings('lunar_text');

    // create a new transaction log
    $newlog = $txns[0];
    unset($newlog['id']);

    $newlog['notes'] = [];

    // set app key
    $appkey = $modcfg['app_key'];

    $apiClient = new \Lunar\Lunar($appkey, null, !!$_COOKIE['lunar_testmode']);

    $order = Order::getInstance();
    try {
        $void = $apiClient->payments()->void(
            $txns[0]['trans_id'],
            [
                'amount' => [
                    'currency' => $order->getSummary($record['cart_order_id'])["currency"],
                    'decimal' => (string) $txns[0]['amount'],
                ]
            ]
        );
    } catch (\Lunar\Exception\ApiException $e) {
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

/* Refund block */
// refund request posted
if (isset($GLOBALS['_POST']['confirm_lunar_refund']) && $GLOBALS['_POST']['confirm_lunar_refund']) {
    // get latest transaction status, authorized|captured
    $txns = $GLOBALS['db']->select('CubeCart_transactions', false, ['order_id' => $record['cart_order_id'], 'gateway' => 'Lunar_Payments'], ['time' => 'DESC']);

    // attempt to refund only when = Captured
    if (empty($txns) || $txns[0]['status'] !== 'Captured') {
        return;
    }

    // load module vars
    $modcfg = $GLOBALS['config']->get('Lunar_Payments');
    $modlang = $GLOBALS['language']->getStrings('lunar_text');

    // create a new transaction log
    $newlog = $txns[0];
    unset($newlog['id']);

    $newlog['notes'] = [];

    // set app key
    $appkey = $modcfg['app_key'];

    $apiClient = new \Lunar\Lunar($appkey, null, !!$_COOKIE['lunar_testmode']);

    $order = Order::getInstance();
    try {
        $rfd = $apiClient->payments()->refund(
            $txns[0]['trans_id'],
            [
                'amount' => [
                    'currency' => $order->getSummary($record['cart_order_id'])["currency"],
                    'decimal' => (string) $txns[0]['amount'],
                ]
            ]
        );
    } catch (\Lunar\Exception\ApiException $e) {
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
