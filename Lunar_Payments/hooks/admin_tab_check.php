<?php

if (!defined('CC_DS')) die('Access Denied');

$g = isset($GLOBALS['_GET']['_g']) ? $GLOBALS['_GET']['_g'] : null;
$action = isset($GLOBALS['_GET']['action']) ? $GLOBALS['_GET']['action'] : null;
$order_id = isset($GLOBALS['_GET']['order_id']) ? $GLOBALS['_GET']['order_id'] : null;

$displayLunar = true;

// order edit page
if ($g != 'orders' && $action != 'edit' && !$order_id) {
    $displayLunar = false;
}

$txns = $GLOBALS['db']->select('CubeCart_transactions', false, ['order_id' => $order_id, 'gateway' => 'Lunar_Payments'], ['time' => 'DESC']);

if (empty($txns)) {
    $displayLunar = false;
}

if ($displayLunar) {
    $modlang = $GLOBALS['language']->getStrings('lunar_text');
}