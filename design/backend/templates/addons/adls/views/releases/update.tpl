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

	<h4>Development</h4>
    {if $product.has_unreleased_version}

		<form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="release_update_form">

            <p>Add-on ID: <input type="text" readonly name="id" value="{$addonId}"/>
            </p>

            <p>Contains unreleased versions</p>
            Development Version: {$product.version}<br>
            Latest Release Version: {$product.adls_release_version}<br>

            <p>Compatibility</p>
            {foreach from=$availableVersions item="version"}
                <label>
                    <input type="checkbox" value="{$version->getId()}" name="compatibility[]">
                    {$version->getExtra('platform$name')}
                    {$version->getExtra('edition$name')}
                    {$version->getVersion()}
                    ({$version->getReleaseDate()->format('Y-m-d')})
                </label>
            {/foreach}

            {include file="buttons/button.tpl" but_text="Release" but_role="submit" but_name="dispatch[releases.update]"}
		</form>

{*        {include
        file="buttons/button.tpl"
        but_role="action"
        but_text="Pack development version as release (unpublished)"
        but_href=fn_url("addons.pack?addon=`$productCode`")
        but_meta=""}*}
    {/if}
{/capture}


{include file="common/mainbox.tpl" title=__("adls.releases") content=$smarty.capture.mainbox}