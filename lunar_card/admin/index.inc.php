<?php

if (!defined('CC_INI_SET')) die('Access Denied');

$lunarPluginPath = __FILE__;

$smarty->assign('lunarMethod', 'card');
$smarty->assign('lunarPluginCode', 'lunar_card');

require_once(CC_ROOT_DIR.'/modules/plugins/Lunar_Payments/admin/index.inc.php');