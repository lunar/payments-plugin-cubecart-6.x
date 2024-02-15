<?php

if (!defined('CC_INI_SET')) die('Access Denied');

$lunarPluginPath = __FILE__;

$smarty->assign('lunarMethod', 'mobilePay');
$smarty->assign('lunarPluginCode', 'lunar_mobilepay');

require_once(CC_ROOT_DIR.'/modules/plugins/LunarPayments/admin/index.inc.php');