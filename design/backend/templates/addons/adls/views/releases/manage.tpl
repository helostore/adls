<style>
	.table tbody tr:hover > td, .table tbody tr:hover > th {
		background-color: rgba(0, 0, 0, 0.1);
	}
	.has_unreleased_version td {
		background-color: rgba(0, 127, 255, 0.3);
	}
</style>
{capture name="mainbox"}
	<h4>Releases</h4>
    {include file="addons/adls/views/releases/components/table.tpl" releases=$product.releases2}
{/capture}

{capture name="adv_buttons"}
    {if $product.has_unreleased_version}
        {include file="common/tools.tpl" tool_href="releases.add?addonId=`$product.adls_addon_id`" prefix="top" hide_tools="true" title=__("adls.release.new.title") icon="icon-plus"}
    {/if}
{/capture}

{include file="common/mainbox.tpl"
    title=__("adls.releases")
    content=$smarty.capture.mainbox
    adv_buttons=$smarty.capture.adv_buttons}