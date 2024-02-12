<?php

require_once(dirname(__FILE__).'/admin_tab_check.php');

// we extract order summary here, because $summary var isn't available
$orderSummary = $GLOBALS['db']->select('CubeCart_order_summary', 'gateway', ['cart_order_id' => $orderId]);

if (!in_array($orderSummary[0]['gateway'], ['Lunar Payments', 'Lunar_Payments'])) {
    $displayLunar = false;
}

if ($displayLunar) {
    // display void tab only when = Authorized
    if ($txns[0]['status'] == 'Authorized') {
        $voidtab = array(
            'name' => $modLang['void'],
            'target' => '#lunar_void',
            'url' => '',
            'accesskey' => '',
            'notify' => 0,
            'a_target' => '_self',
            'tab_id' => 'tab_lunar_void'
        );

        $tabs[] = $voidtab;
    }

    // display refund tab only when = Captured
    if ($txns[0]['status'] == 'Captured') {
        $refundtab = array(
            'name' => $modLang['refund'],
            'target' => '#lunar_refund',
            'url' => '',
            'accesskey' => '',
            'notify' => 0,
            'a_target' => '_self',
            'tab_id' => 'tab_lunar_refund'
        );

        $tabs[] = $refundtab;
    }

    // clear cache when Lunar settings are saved
    if ($g == 'plugins') {
        if (isset($_GET['module'])) {
            if ($_GET['module'] == 'Lunar_Payments') {
                if (isset($_POST['module']['status'])) {
                    $GLOBALS['cache']->clear();
                }
            }
        }
    }
}
