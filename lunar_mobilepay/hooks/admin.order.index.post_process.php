<?php

if (!defined('CC_DS')) die('Access Denied');

$lunarPluginCode = 'lunar_mobilepay';

require(CC_ROOT_DIR.'/modules/plugins/LunarPayments/hooks/admin.order.index.post_process.php');