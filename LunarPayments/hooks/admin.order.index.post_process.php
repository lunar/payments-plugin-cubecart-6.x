<?php

global $lunarPluginCode;

$orderId = isset($record['cart_order_id']) ? $record['cart_order_id'] : null;

if (!empty($orderId)) {

    /* Capture block for authorized payments */
    // when order status set to complete
    if (isset($_POST['order']['status']) && $_POST['order']['status'] == '3') {
        if (!class_exists('lunarTransactions')) {
            require(CC_ROOT_DIR.'/modules/plugins/LunarPayments/helpers/lunar_transactions.php'); 
        }

        $lunarTransactions = new lunarTransactions($lunarPluginCode, $orderId);
        $lunarTransactions->captureTransaction();
    }

    /* Refund block */
    // refund request posted
    if (!empty($GLOBALS['_POST']['confirm_refund_'.$lunarPluginCode])) {
        if (!class_exists('lunarTransactions')) {
            require(CC_ROOT_DIR.'/modules/plugins/LunarPayments/helpers/lunar_transactions.php'); 
        }

        $lunarTransactions = new lunarTransactions($lunarPluginCode, $orderId);
        $lunarTransactions->refundTransaction();
    }

    /* Void block */
    // void request posted
    if (!empty($GLOBALS['_POST']['confirm_void_'.$lunarPluginCode])) {
        if (!class_exists('lunarTransactions')) {
            require(CC_ROOT_DIR.'/modules/plugins/LunarPayments/helpers/lunar_transactions.php'); 
        }

        $lunarTransactions = new lunarTransactions($lunarPluginCode, $orderId);
        $lunarTransactions->cancelTransaction();
    }
}