<?php
if (!defined('CC_DS')) die('Access Denied');

$g = isset($GLOBALS['_GET']['_g']) ? $GLOBALS['_GET']['_g'] : '';
$action = isset($GLOBALS['_GET']['action']) ? $GLOBALS['_GET']['action'] : '';
$order_id = isset($GLOBALS['_GET']['order_id']) ? $GLOBALS['_GET']['order_id'] : '';

// order edit page
if ($g != 'orders' && $action != 'edit' && !$order_id) {
    return;
}

// paid with lunar
if ($summary[0]['gateway'] != 'Lunar Payments' || $summary[0]['gateway'] != 'Lunar_Payments') {
    return;
}
// display only if = Captured
$txns = $GLOBALS['db']->select('CubeCart_transactions', false, ['order_id' => $order_id, 'gateway' => 'Lunar_Payments'], ['time' => 'DESC']);

if (!$txns) {
    return;
}

// init module lang
$modlang = $GLOBALS['language']->getStrings('lunar_text');

// Void when only Authorized
if ($txns[0]['status'] == 'Authorized') {
    $tabcontent = '
        <div id="lunar_void" class="tab_content">
        <h3>' . $modlang['void_title'] . '</h3>
        <table>
            <tbody>
            <tr>
                <td>
                <span>
                    <input type="hidden" name="confirm_lunar_void" id="confirm_lunar_void" class="toggle" value="0" original="0">
                </span>
                </td>
                <td>
                <label for="confirm_lunar_void" style="color:red;">' . $modlang['void_confirm'] . '</label>
                </td>
            </tr>
            </tbody>
        </table>
        </div>';
    $smarty_data['plugin_tabs'][] = $tabcontent;
}

// Refund when only Captured
if ($txns[0]['status'] == 'Captured') {
    $tabcontent = '
        <div id="lunar_refund" class="tab_content">
            <h3>' . $modlang['refund_title'] . '</h3>
            <table>
            <tbody>
                <tr>
                <td>
                    <span>
                    <input type="hidden" name="confirm_lunar_refund" id="confirm_lunar_refund" class="toggle" value="0" original="0">
                    </span>
                </td>
                <td>
                    <label for="confirm_lunar_refund" style="color:red;">' . $modlang['refund_confirm'] . '</label>
                </td>
                </tr>
            </tbody>
            </table>
        </div>';
    $smarty_data['plugin_tabs'][] = $tabcontent;
}
