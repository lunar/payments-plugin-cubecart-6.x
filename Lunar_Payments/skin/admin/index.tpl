<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
    <input type="hidden" name="module[name]" value="Lunar" />

    <div id="Lunar_Form" class="tab_content">
        <h3>{$TITLE}</h3>

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
                <label for="app_key">{$LANG.lunar_text.app_key}</label>
                <span>
                    <input type="text" name="module[app_key]" value="{$MODULE.app_key}" required class="textbox" size="30"/>
                </span>
            </div>
            <div>
                <label for="public_key">{$LANG.lunar_text.public_key}</label>
                <span>
                    <input type="text" name="module[public_key]" value="{$MODULE.public_key}" required class="textbox" size="30"/>
                </span>
            </div>
            <div>
                <label for="logo_url">{$LANG.lunar_text.logo_url}</label>
                <span>
                    <input type="text" name="module[logo_url]" value="{$MODULE.logo_url}" required class="textbox" size="30"/>
                </span>
            </div>

            <div>
                <label for="configuration_id">{$LANG.lunar_text.configuration_id}</label>
                <span>
                    <!-- @TODO adjust check for required attr -->
                    <input type="text" name="module[configuration_id]" value="{$MODULE.configuration_id}" {if false}required{/if} class="textbox" size="30"/>
                </span>
            </div>

            <div>
                <label for="shopname">{$LANG.lunar_text.shopname}</label>
                <span>
                    <input type="text" name="module[shopname]" value="{$MODULE.shopname}" class="textbox" size="30"/>
                </span>
            </div>

            <div>
                <label for="scope">{$LANG.lunar_text.capturemode}</label>
                <span>
                    <select name="module[capturemode]">
                        <option value="instant" {$SELECT_capturemode_instant}>{$LANG.lunar_text.instant}</option>
                        <option value="delayed" {$SELECT_capturemode_delayed}>{$LANG.lunar_text.delayed}</option>
                    </select>
                </span>
            </div>

            <div>
                <label for="description">{$LANG.common.description}</label>
                <span>
                    <input name="module[description]" id="description" class="textbox" type="text" value="{$MODULE.description}"/>
                </span>
            </div>
        </fieldset>
    </div>

    {$MODULE_ZONES}

    <div class="form_control"><input type="submit" name="save" value="{$LANG.common.save}" /></div>
    <input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>
