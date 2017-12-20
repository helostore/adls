
{capture name="mainbox"}

	{capture name="sidebar"}
		{*{include file="common/saved_search.tpl" dispatch="adls_licenses.manage" view_type="licenses"}*}
		{include file="addons/adls/views/licenses/components/search_form.tpl" dispatch="licenses.manage"}
	{/capture}

	<form action="{""|fn_url}" method="post" target="_self" name="licenses_list_form">
		{include file="addons/adls/views/licenses/components/table.tpl"}


		{capture name="adv_buttons"}
		{/capture}

	</form>
{/capture}


{capture name="buttons"}
	{capture name="tools_list"}

	{/capture}
	{dropdown content=$smarty.capture.tools_list}
{/capture}

{include file="common/mainbox.tpl" title=__('adls.licenses') sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_licenses"}
