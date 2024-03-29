<div id="content_adls" class="hidden">
    {include file="common/subheader.tpl" title=__("adls") target="#acc_adls"}
    <div id="acc_adls" class="collapsed in">

        <div class="control-group">
            <label class="control-label" for="adls_addon_id">{__("adls.addon")}:</label>
            <div class="controls">
                <select name="product_data[adls_addon_id]">
                    <option></option>
                    {foreach from=$adls_addons item="addon" key="id"}
                        {$selected = ''}
                        {if !empty($product_data) && $product_data.adls_addon_id == $id}
                            {$selected = 'selected="selected"'}
                        {/if}
                        <option value="{$id}" {$selected}>{$addon.name} {$addon.version}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="adls_addon_id">{__("adls.product.slug")}:</label>
            <div class="controls">
                <input type="text" name="product_data[adls_slug]" value="{$product_data.adls_slug|default:$product_data.adls_addon_id}"/>
            </div>
        </div>


        <div class="control-group">
            <label class="control-label" for="product_type">{__("adls.product_type")}:</label>
            <div class="controls">
                <select name="product_data[product_type]">
                    {foreach from=$adls_product_types item="label" key="key"}
                        {$selected = ''}
                        {if !empty($product_data) && $product_data.product_type == $key}
                            {$selected = 'selected="selected"'}
                        {/if}
                        <option value="{$key}" {$selected}>{$label}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="subscription_type">{__("adls.subscription_type")}:</label>
            <div class="controls">
                <select name="product_data[adls_subscription_id]">
                    <option></option>
                    {foreach from=$adls_subscriptions item="label" key="key"}
                        {$selected = ''}
                        {if !empty($product_data) && $product_data.adls_subscription_id == $key}
                            {$selected = 'selected="selected"'}
                        {/if}
                        <option value="{$key}" {$selected}>{$label}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="adls_licenseable">{__("adls.licenseable")}:</label>
            <div class="controls">
                <label class="checkbox">
                    <input type="hidden" name="product_data[adls_licenseable]" value="0" />
                    <input type="checkbox" name="product_data[adls_licenseable]" value="1" {if $product_data.adls_licenseable == "1"}checked="checked"{/if}/>
                </label>
            </div>
        </div>

        {if !empty($smarty.request.showUsage)}
            <h3>Usage</h3>
            {include file="addons/adls/views/adls/components/usage.tpl" usage=$usage}
            {include file="addons/adls/views/adls/components/usage_product_versions.tpl" usage=$usageProductVersions}
            <p>
                <a class="btn" href="{"products.update?product_id=`$smarty.request.product_id`&selected_section=adls"|fn_url}">Hide Usage</a>
            </p>
        {else}
            <p>
                <a class="btn" href="{"products.update?product_id=`$smarty.request.product_id`&selected_section=adls&showUsage=1"|fn_url}">Show Usage</a>
            </p>
        {/if}
    </div>

    <!--content_adls--></div>
