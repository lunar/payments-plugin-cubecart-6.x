<?php

$lunarConfig = $GLOBALS['config']->get($lunarPluginCode);

if ($lunarConfig['status']) {

    if ('lunar_mobilepay' == $lunarPluginCode) {
        $description = '<div style="display:flex;height:2rem"><span>'.$lunarConfig['checkout_name']
                .'</span>'.'<div style="margin-left:1rem;"><img style="height:2rem;" src="/modules/plugins/LunarPayments/skin/images/mobilepay-logo.png" alt="mobilepay logo"></div></div>';
    } else {
        $description = '<div style="display:flex;align-items: center;">'.$lunarConfig['checkout_name']
        .'<img style="margin-left:0.3rem;" src="/modules/plugins/LunarPayments/skin/images/mastercard.svg" alt="mastercard logo">'
        .'<img style="margin-left:0.3rem;" src="/modules/plugins/LunarPayments/skin/images/visa.svg" alt="visa logo">'
        .'<img style="margin-left:0.3rem;" src="/modules/plugins/LunarPayments/skin/images/maestro.svg" alt="maestro logo"></div>';
    }

    $gatewayData = [
        'plugin' => true,
        'base_folder' => $lunarPluginCode,
        'folder' => $lunarPluginCode,
        'desc' => $description,
    ];
    
    if (isset($_POST['gateway']) || !empty($name)) {
        $base_folder = isset($_POST['gateway']) ? $_POST['gateway'] : $name;
        if ($base_folder == $lunarPluginCode) {
            $gateways[0] = $gatewayData;
        }
    } else {
        $gatewayData['default'] = isset($lunarConfig['default']) ? (bool) $lunarConfig['default'] : true;
        $gateways[] = $gatewayData;
    }
}
