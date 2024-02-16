<?php

global $lunarPluginCode;

$orderId = isset($record['cart_order_id']) ? $record['cart_order_id'] : null;

if (!empty($orderId)) {

    // when order status set to Order Complete
    if (isset($_POST['order']['status']) && $_POST['order']['status'] == '3') {
        if (!class_exists('lunarTransactions')) {
            require(CC_ROOT_DIR.'/modules/plugins/LunarPayments/helpers/lunar_transactions.php'); 
        }

        $lunarTransactions = new lunarTransactions($lunarPluginCode, $orderId);
        $lunarTransactions->captureTransaction();
    }

    // void OR refund request when status set to Cancelled
    if (isset($_POST['order']['status']) && $_POST['order']['status'] == '6') {
        if (!class_exists('lunarTransactions')) {
            require(CC_ROOT_DIR.'/modules/plugins/LunarPayments/helpers/lunar_transactions.php'); 
        }

        $lunarTransactions = new lunarTransactions($lunarPluginCode, $orderId);
        $lunarTransactions->cancelTransaction();
    }
}