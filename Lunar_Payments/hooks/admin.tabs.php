<?php
if (!defined('CC_DS')) die('Access Denied');

$g = isset($GLOBALS['_GET']['_g']) ? $GLOBALS['_GET']['_g'] : '';
$action = isset($GLOBALS['_GET']['action']) ? $GLOBALS['_GET']['action'] : '';
$orderId = isset($GLOBALS['_GET']['order_id']) ? $GLOBALS['_GET']['order_id'] : '';

// on order edit page
if ($g != 'orders' && $action != 'edit' && !$orderId) {
    return;
}

// paid with Lunar
$orderSummary = $GLOBALS['db']->select('CubeCart_order_summary', 'gateway', array('cart_order_id' => $orderId));

if ($orderSummary[0]['gateway'] != 'Lunar Payments' || $orderSummary[0]['gateway'] != 'Lunar_Payments') {
    return;
}

// display only if Captured
$txns = $GLOBALS['db']->select('CubeCart_transactions', false, ['order_id' => $orderId, 'gateway' => 'Lunar_Payments'], ['time' => 'DESC']);

if (!$txns) {
    return;
}

$modLang = $GLOBALS['language']->getStrings('lunar_text');

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
