<?php

if (!defined('CC_DS')) die('Access Denied');

$orderId = isset($record['cart_order_id']) ? $record['cart_order_id'] : null;

$lastLunarTransaction = null;
if ($orderId) {
    $lastLunarTransaction = $GLOBALS['db']->select('CubeCart_transactions', false,
        [
            'order_id' => $orderId,
            'gateway' => 'Lunar_Payments'
        ],
        [
            'time' => 'DESC'
        ]
    );
}

/* Capture block for authorized payments */
// when order status set to complete
if ($lastLunarTransaction && isset($_POST['order']['status']) && $_POST['order']['status'] == '3') {

    if ($lastLunarTransaction['status'] === 'Authorized') {
        // load module vars
        $modcfg = $GLOBALS['config']->get('Lunar_Payments');
        $modlang = $GLOBALS['language']->getStrings('lunar_text');

        // create a new transaction log
        $newlog = $lastLunarTransaction;
        unset($newlog['id']);

        $newlog['notes'] = [];

        $apiClient = new \Lunar\Lunar($modcfg['app_key'], null, !!$_COOKIE['lunar_testmode']);

        $order = Order::getInstance();
        try {
            $apiResponse = $apiClient->payments()->capture(
                $lastLunarTransaction['trans_id'],
                [
                    'amount' => [
                        'currency' => $order->getSummary($record['cart_order_id'])['currency'],
                        'decimal' => (string) $lastLunarTransaction['amount'],
                    ]
                ]
            );
        } catch (\Lunar\Exception\ApiException $e) {
            $newlog['notes'][] = $e->getMessage();
        }

        if (isset($apiResponse['captureState']) && 'completed' === $apiResponse['captureState']) {
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
if ($lastLunarTransaction && !empty($GLOBALS['_POST']['confirm_lunar_void'])) {

    if ($lastLunarTransaction['status'] === 'Authorized') {

        // load module vars
        $modcfg = $GLOBALS['config']->get('Lunar_Payments');
        $modlang = $GLOBALS['language']->getStrings('lunar_text');

        // create a new transaction log
        $newlog = $lastLunarTransaction;
        unset($newlog['id']);

        $newlog['notes'] = [];

        $apiClient = new \Lunar\Lunar($modcfg['app_key'], null, !!$_COOKIE['lunar_testmode']);

        $order = Order::getInstance();
        try {
            $apiResponse = $apiClient->payments()->cancel(
                $lastLunarTransaction['trans_id'],
                [
                    'amount' => [
                        'currency' => $order->getSummary($record['cart_order_id'])['currency'],
                        'decimal' => (string) $lastLunarTransaction['amount'],
                    ]
                ]
            );
        } catch (\Lunar\Exception\ApiException $e) {
            // Unknown api error
            $newlog['notes'][] = $e->getMessage();
        }

        if (isset($apiResponse['cancelState']) && 'completed' === $apiResponse['cancelState']) {
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

/* Refund block */
// refund request posted
if ($lastLunarTransaction && !empty($GLOBALS['_POST']['confirm_lunar_refund'])) {

    if ($lastLunarTransaction['status'] === 'Captured') {

        // load module vars
        $modcfg = $GLOBALS['config']->get('Lunar_Payments');
        $modlang = $GLOBALS['language']->getStrings('lunar_text');

        // create a new transaction log
        $newlog = $lastLunarTransaction;
        unset($newlog['id']);

        $newlog['notes'] = [];

        $apiClient = new \Lunar\Lunar($modcfg['app_key'], null, !!$_COOKIE['lunar_testmode']);

        $order = Order::getInstance();
        try {
            $apiResponse = $apiClient->payments()->refund(
                $lastLunarTransaction['trans_id'],
                [
                    'amount' => [
                        'currency' => $order->getSummary($record['cart_order_id'])['currency'],
                        'decimal' => (string) $lastLunarTransaction['amount'],
                    ]
                ]
            );
        } catch (\Lunar\Exception\ApiException $e) {
            // Unknown api error
            $newlog['notes'][] = $e->getMessage();
        }

        if (isset($apiResponse['refundState']) && 'completed' === $apiResponse['refundState']) {
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
