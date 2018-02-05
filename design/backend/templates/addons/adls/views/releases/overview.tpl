<style>
	.table tbody tr:hover > td, .table tbody tr:hover > th {
		background-color: rgba(0, 0, 0, 0.1);
	}
	.has_unreleased_version td {
		background-color: rgba(0, 127, 255, 0.3);
	}
</style>
{capture name="mainbox"}

	{capture name="sidebar"}
	{/capture}

	{if !empty($platform)}
        {include file="addons/adls/views/releases/platform/overview.tpl" platform=$platform products=$products}
    {else}
		<p>{__('adls.platforms')}</p>
		<ul>
			{foreach from=$platforms item="platform"}
				<li><a href="{"releases.overview?platformId=`$platform->getId()`"|fn_url}">{$platform->getName()}</a><br/></li>
			{/foreach}
		</ul>
    {/if}

{/capture}

{capture name="adv_buttons"}
    <div class="buttons-container">

    </div>
{/capture}
{capture name="buttons"}
    {capture name="tools_list"}
	{/capture}
    {dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=__("adls.releases") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}