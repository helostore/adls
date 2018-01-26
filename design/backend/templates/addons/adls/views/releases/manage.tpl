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

{capture name="mainbox"}
    {include file="addons/adls/views/releases/components/table.tpl" releases=$product.releases2}
    {if $product.has_unreleased_version}
        <p style="color: red;">Has unreleased version!</p>
    {/if}

    {include file="addons/adls/views/adls/components/usage.tpl" usage=$usage}
    {include file="addons/adls/views/adls/components/usage_product_versions.tpl" usage=$usageProductVersions}

{/capture}

{capture name="adv_buttons"}
    {if $product.has_unreleased_version}
        {include file="common/tools.tpl" tool_href="releases.add?addonId=`$product.adls_addon_id`" prefix="top" hide_tools="true" title=__("adls.release.new.title") icon="icon-plus"}
    {/if}
{/capture}

{include file="common/mainbox.tpl"
    title=$title
    content=$smarty.capture.mainbox
    adv_buttons=$smarty.capture.adv_buttons}