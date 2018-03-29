<div class="control-group">
    <label class="control-label" for="elm_usergroup_release_status_{$id}">{__("adls.usergroup.release_status_access")}</label>
    <div class="controls">
        {$rstatuses = fn_adls_get_release_statuses()}
        {foreach from=$rstatuses item="label" key="key"}
            <label class="radio inline" for="elm_usergroup_release_status_{$id}_{$key}">
                <input id="elm_usergroup_release_status_{$id}_{$key}" type="checkbox" name="usergroup_data[release_status][]" value="{$key}" {if !empty($usergroup.release_status) && in_array($key, $usergroup.release_status)}checked="checked"{/if} />
                {$label}
            </label>
        {/foreach}
    </div>
</div>
