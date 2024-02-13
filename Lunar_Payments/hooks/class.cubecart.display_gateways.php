<?php

if (!defined('CC_DS')) die('Access Denied');

$lunarConfig = $GLOBALS['config']->get('Lunar_Payments');

if ($lunarConfig['status']) {

    $gatewayData = [
        'plugin' => true,
        'base_folder' => 'Lunar_Payments',
        'folder' => 'Lunar_Payments',
        // 'desc' => $lunarConfig['checkout_name'],
        'desc' => '<div style="display:flex;margin-left:1rem;align-items: center;">'.$lunarConfig['checkout_name']
                    .'<img style="margin-left:0.3rem;" src="/modules/plugins/Lunar_Payments/skin/images/mastercard.svg" alt="mastercard logo">'
                    .'<img style="margin-left:0.3rem;" src="/modules/plugins/Lunar_Payments/skin/images/visa.svg" alt="visa logo">'
                    .'<img style="margin-left:0.3rem;" src="/modules/plugins/Lunar_Payments/skin/images/maestro.svg" alt="maestro logo"></div>',
        // // mobilepay
        // '<div style="display:flex;height:2rem"><span>'.$lunarConfig['checkout_name']
        //         .'</span>'.'<div style="margin-left:1rem;"><img style="height:2rem;" src="/modules/plugins/Lunar_Payments/skin/images/mobilepay-logo.png" alt="mobilepay logo"></div></div>'
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
