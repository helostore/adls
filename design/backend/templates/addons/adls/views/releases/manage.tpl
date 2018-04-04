<style>
	.table tbody tr:hover > td, .table tbody tr:hover > th {
		background-color: rgba(0, 0, 0, 0.1);
	}
	.has_unreleased_version td {
		background-color: rgba(0, 127, 255, 0.3);
	}
</style>
{$title = __("adls.releases")}
{if !empty($product)}
    {$title = "$title - `$product.name`"}
{/if}
{if !empty($platform)}
    {$title = "$title - `$platform->getName()`"}
{/if}

{capture name="mainbox"}
    {if $product.has_unreleased_version}
        <p style="color: red;">Note: there is at least one un-released version:
            {if !empty($product.latestBuild)}
                {$product.latestBuild.version}
            {else}
                n/a
            {/if}
        </p>
    {/if}
    {include file="addons/adls/views/releases/components/table.tpl" releases=$product.releases}
    {include file="addons/adls/views/adls/components/usage.tpl" usage=$usage}
    {include file="addons/adls/views/adls/components/usage_product_versions.tpl" usage=$usageProductVersions}

    <p><a href="{"products.update?product_id=`$product.product_id`"|fn_url}">Update product in store</a></p>

{/capture}

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="releases.add?productId=`$product.product_id`&platformId=`$platform->getId()`" prefix="top" hide_tools="true" title=__("adls.release.new.title") icon="icon-plus"}
    {*{if $product.has_unreleased_version}*}
    {*{/if}*}
{/capture}

{include file="common/mainbox.tpl"
    title=$title
    content=$smarty.capture.mainbox
    adv_buttons=$smarty.capture.adv_buttons}
