<?php

// small hack to have loaded definitions from one place
$GLOBALS['language']->loadDefinitions('lunar_text', CC_ROOT_DIR.'/modules/plugins/Lunar_Payments/language', 'module.definitions.xml');

global $lunarPluginPath;

$module = new Module($lunarPluginPath, $_GET['module'], CC_ROOT_DIR.'/modules/plugins/Lunar_Payments/skin/admin/index.tpl', true);

$page_content = $module->display();
