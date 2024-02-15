<?php

global $lunarPluginCode, $txns, $displayLunar, $modLang;

// The gateway name from global $summary is without "_"
// so, we manually extract it from DB
$orderSummary = $GLOBALS['db']->select('CubeCart_order_summary', 'gateway', ['cart_order_id' => $order_id]);

if ($orderSummary[0]['gateway'] !== $lunarPluginCode) {
    $displayLunar = false;
}

if ($displayLunar) {

    if ($txns[0]['status'] == 'Authorized') {
        $tabContent = '
            <div id="lunar_void" class="tab_content">
            <h3>' . $modLang['void_title'] . " ($lunarPluginCode)" . '</h3>
            <table>
                <tbody>
                    <tr>
                        <td>
                        <span>
                            <input type="hidden" name="confirm_void_'.$lunarPluginCode.'" id="confirm_void_'.$lunarPluginCode.'" class="toggle" value="0" original="0">
                        </span>
                        </td>
                        <td>
                            <label for="confirm_void_'.$lunarPluginCode.'" style="color:red;">' . $modLang['void_confirm'] . '</label>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>';
            
        $smarty_data['plugin_tabs'][] = $tabContent;
    }

    if ($txns[0]['status'] == 'Captured') {
        $tabContent = '
            <div id="lunar_refund" class="tab_content">
                <h3>' . $modLang['refund_title'] . " ($lunarPluginCode)" . '</h3>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <span>
                                    <input type="hidden" name="confirm_refund_'.$lunarPluginCode.'" id="confirm_refund_'.$lunarPluginCode.'" class="toggle" value="0" original="0">
                                </span>
                            </td>
                            <td>
                                <label for="confirm_refund_'.$lunarPluginCode.'" style="color:red;">' . $modLang['refund_confirm'] . '</label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>';

        $smarty_data['plugin_tabs'][] = $tabContent;
    }
}