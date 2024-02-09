<?php

if (!defined('CC_DS')) die('Access Denied');

$lunarConfig = $GLOBALS['config']->get('Lunar_Payments');

if ($lunarConfig['status']) {

    $gatewayData = [
        'plugin' => true,
        'base_folder' => 'Lunar_Payments',
        'folder' => 'Lunar_Payments',
        'desc' => $lunarConfig['checkout_name'],
    ];
    
    if (isset($_POST['gateway']) || !empty($name)) {
        $base_folder = isset($_POST['gateway']) ? $_POST['gateway'] : $name;
        if ($base_folder == 'Lunar_Payments') {
            $gateways[0] = $gatewayData;
        }
    } else {
        $gateways[199] = $gatewayData;
        $gateways[199]['default'] = isset($lunarConfig['default']) ? (bool) $lunarConfig['default'] : true;
    }
}
