<?php

global $txns, $displayLunar, $modLang;

// Here the gateway name is without "_"
if (!strstr($summary[0]['gateway'], 'lunar ')) {
    $displayLunar = false;
}

if ($displayLunar) {

    if ($txns[0]['status'] == 'Authorized') {
        $tabContent = '
            <div id="lunar_void" class="tab_content">
            <h3>' . $modLang['void_title'] . '</h3>
            <table>
                <tbody>
                    <tr>
                        <td>
                        <span>
                            <input type="hidden" name="confirm_lunar_void" id="confirm_lunar_void" class="toggle" value="0" original="0">
                        </span>
                        </td>
                        <td>
                            <label for="confirm_lunar_void" style="color:red;">' . $modLang['void_confirm'] . '</label>
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
                <h3>' . $modLang['refund_title'] . '</h3>
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <span>
                                    <input type="hidden" name="confirm_lunar_refund" id="confirm_lunar_refund" class="toggle" value="0" original="0">
                                </span>
                            </td>
                            <td>
                                <label for="confirm_lunar_refund" style="color:red;">' . $modLang['refund_confirm'] . '</label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>';

        $smarty_data['plugin_tabs'][] = $tabContent;
    }
}