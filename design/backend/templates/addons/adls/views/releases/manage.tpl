<style>
	.table tbody tr:hover > td, .table tbody tr:hover > th {
		background-color: rgba(0, 0, 0, 0.1);
	}
</style>
{capture name="mainbox"}

	{capture name="sidebar"}
	{/capture}

	{include file="common/pagination.tpl"}

	{if $products}
		<table class="table adls-table">
			<thead>
			<tr>
				<th rowspan="2" width="20%">{__("product")}</th>

				<th colspan="2">{__('adls.releases')}</th>
				<th colspan="3">{__('adls.development')}</th>

				<th rowspan="2">{__("action")}</th>
			</tr>
			<tr>
				<th>{__("adls.version")}</th>
				<th>{__("adls.date")}</th>
				<th>{__("adls.version")}</th>
				<th>{__("adls.date")}</th>
				<th>{__("adls.commits")}</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$products item="product" key="productCode"}
				{$class = ""}

				<tr class="{$class}">
					<td>
						{if !empty($product.product_id)}
							<a href="{"products.update?product_id=`$product.product_id`"|fn_url}" target="_blank">{$product.name}</a>
						{else}
							{$product.name}
						{/if}
					</td>

					{* Release Info *}
					<td>
						{$product.adls_release_version|default:'&dash;' nofilter}
					</td>

					<td>
						{if !empty($product.adls_release_date)}
							{$product.adls_release_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
						{else}
							&dash;
						{/if}
					</td>


					{* Development Info *}
					<td>
						{$product.version}
					</td>
					<td>
						{if !empty($product.lastRelease)}
							{$product.lastRelease.releaseTimestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
						{else}
							&dash;
						{/if}
					</td>
					<td>
						{if !empty($product.lastRelease.commits)}
							{capture name="commits"}
								{"<br>"|implode:$product.lastRelease.commits nofilter}
							{/capture}
							{include
							file="common/popupbox.tpl"
							id="product_commits_`$productCode`"
							text=__("adls.commits")
							act="link"
							link_text=__("adls.view_commits", ["[count]" => $product.lastRelease.commits|count])
							content=$smarty.capture.commits
							no_icon_link=true
							}

						{else}
							&dash;
						{/if}
					</td>


					<td>
						{include
						file="buttons/button.tpl"
						but_role="action"
						but_text=__("adls.release_now")
						but_href=fn_url("addons.pack?addon=`$productCode`")
						but_meta="cm-ajax"}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{else}
		<p class="no-items">{__("no_data")}</p>
	{/if}

	{include file="common/pagination.tpl"}
{/capture}

{capture name="adv_buttons"}
    <div class="buttons-container">

    </div>
{/capture}
{capture name="buttons"}
    {capture name="tools_list"}
    {hook name="logs:tools"}
    <li>{btn type="list" text=__("settings") href="settings.manage?section_id=Logging"}</li>
    <li>{btn type="list" target="_blank" text=__("phpinfo") href="tools.phpinfo"}</li>
    <li>{btn type="list" text=__("backup_restore") href="datakeeper.manage"}</li>
    <li>{btn type="list" text=__("clean_logs") href="logs.clean" class="cm-confirm cm-post"}</li>
{/hook}
{/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=__("adls.releases") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}