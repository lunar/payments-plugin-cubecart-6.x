<?php

if (!class_exists('\\LunarPaymentsBase')) {
    require_once(CC_ROOT_DIR.'/modules/plugins/LunarPayments/lunar_base.class.php');
}

class Gateway extends LunarPaymentsBase
{
    protected $paymentMethod = 'card';
    protected $moduleCode = 'lunar_card';
}
