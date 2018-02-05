<form method="post">
    <div class="control-group">
        <label class="control-label" for="elm_source_product">{__("product")}</label>
        <div class="controls">
            <input type="hidden" name="source_data[productId]" value="{$product_data.product_id}"/>
            <input type="text" readonly value="{$product_data.product}"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_source_platform">{__("adls.platform")}</label>
        <div class="controls">
            <select class="span3" name="source_data[platformId]" id="elm_source_platform">
                {foreach from=$adls_platforms item="platform"}
                    <option value="{$platform->getId()}">{$platform->getName()}</option>
                {/foreach}
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_source_path">{__("adls.source.sourcePath")}</label>
        <div class="controls">
            <input type="text" name="source_data[sourcePath]" value="" id="elm_source_path"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_source_release_path">{__("adls.source.releasePath")}</label>
        <div class="controls">
            <input type="text" name="source_data[releasePath]" value="" id="elm_source_release_path" />
        </div>
    </div>

    <div class="buttons-container">
        {include file="buttons/save.tpl" but_name="dispatch[sources.update]" cancel_action="close" extra="" hide_first_button=$hide_first_button save=$id}
    </div>

</form>