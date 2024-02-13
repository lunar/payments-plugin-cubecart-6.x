<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">

    <div id="Lunar_Form" class="tab_content">
        <h3>
            <span>{$TITLE}</span>
            <span style="margin-left:0.7rem;">
                {'Lunar '}{$lunarPluginCode|capitalize}
            </span>
        </h3>

        <fieldset>
            <legend>{$LANG.module.config_settings}</legend>
            <div>
                <label for="status">{$LANG.common.status}</label>
                <span>
                    <input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" />
                </span>
            </div>
            <div>
                <label for="position">{$LANG.module.position}</label>
                <span>
                    <input type="text" name="module[position]" id="position" class="textbox number" value="{$MODULE.position}"/>
                </span>
            </div>
            <div>
                <label for="scope">{$LANG.module.scope}</label>
                <span>
                <select name="module[scope]">
                    <option value="both" {$SELECT_scope_both}>{$LANG.module.both}</option>
                    <option value="main" {$SELECT_scope_main}>{$LANG.module.main}</option>
                    <option value="mobile" {$SELECT_scope_mobile}>{$LANG.module.mobile}</option>
                </select>
                </span>
            </div>
            <div>
                <label for="default">{$LANG.common.default}</label>
                <span>
                    <input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}"/>
                </span>
            </div>
        </fieldset>

        <fieldset>
            <legend>{$LANG.lunar_text.setup_lunar}</legend>
            <div>
                <label for="checkout_name">{$LANG.lunar_text.checkout_name}</label>
                <span>
                    <input name="module[checkout_name]" id="checkout_name" class="textbox" type="text" 
                        value="{if $MODULE.checkout_name}{$MODULE.checkout_name}{else}{$lunarPluginCode|capitalize}{/if}"/>
                </span>
            </div>
            <div>
                <label for="app_key">{$LANG.lunar_text.app_key}</label>
                <span>
                    <input type="text" name="module[app_key]" value="{$MODULE.app_key}" required class="textbox"/>
                </span>
            </div>
            <div>
                <label for="public_key">{$LANG.lunar_text.public_key}</label>
                <span>
                    <input type="text" name="module[public_key]" value="{$MODULE.public_key}" required class="textbox"/>
                </span>
            </div>
            <div>
                <label for="logo_url">{$LANG.lunar_text.logo_url}</label>
                <span>
                    <input type="text" name="module[logo_url]" value="{$MODULE.logo_url}" required class="textbox"/>
                </span>
            </div>

            {if $lunarPluginCode == 'mobilePay'}
            <div>
                <label for="configuration_id">{$LANG.lunar_text.configuration_id}</label>
                <span>
                    <input type="text" name="module[configuration_id]" value="{$MODULE.configuration_id}" required class="textbox"/>
                </span>
            </div>
            {/if}

            <div>
                <label for="shop_name">{$LANG.lunar_text.shop_name}</label>
                <span>
                    <input type="text" name="module[shop_name]" 
                        value="{if $MODULE.shop_name}{$MODULE.shop_name}{else}{$CONFIG['store_title']}{/if}" class="textbox"/>
                </span>
            </div>

            <div>
                <label for="scope">{$LANG.lunar_text.capture_mode}</label>
                <span>
                    <select name="module[capture_mode]">
                        <option value="delayed" {$SELECT_capture_mode_delayed}>{$LANG.lunar_text.delayed}</option>
                        <option value="instant" {$SELECT_capture_mode_instant}>{$LANG.lunar_text.instant}</option>
                    </select>
                </span>
            </div>

        </fieldset>
    </div>

    {$MODULE_ZONES}

    <div class="form_control"><input type="submit" name="save" value="{$LANG.common.save}" /></div>
    <input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>