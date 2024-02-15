<?php

global $order_id, $txns, $displayLunar, $modLang;

// We extract order summary here, because $summary var isn't available
$orderSummary = $GLOBALS['db']->select('CubeCart_order_summary', 'gateway', ['cart_order_id' => $order_id]);

if (!strstr($orderSummary[0]['gateway'], 'lunar_')) {
    $displayLunar = false;
}

if ($displayLunar) {
    // display void tab only when = Authorized
    if ($txns[0]['status'] == 'Authorized') {
        $voidTab = array(
            'name' => $modLang['void'],
            'target' => '#lunar_void',
            'url' => '',
            'accesskey' => '',
            'notify' => 0,
            'a_target' => '_self',
            'tab_id' => 'tab_lunar_void'
        );

        $tabs[] = $voidTab;
    }

    // display refund tab only when = Captured
    if ($txns[0]['status'] == 'Captured') {
        $refundTab = array(
            'name' => $modLang['refund'],
            'target' => '#lunar_refund',
            'url' => '',
            'accesskey' => '',
            'notify' => 0,
            'a_target' => '_self',
            'tab_id' => 'tab_lunar_refund'
        );

        $tabs[] = $refundTab;
    }

    // clear cache when Lunar settings are saved
    if ($g == 'plugins') {
        if (isset($_GET['module'])) {
            if ($_GET['module'] == 'LunarPayments') {
                if (isset($_POST['module']['status'])) {
                    $GLOBALS['cache']->clear();
                }
            }
        }
    }
}
