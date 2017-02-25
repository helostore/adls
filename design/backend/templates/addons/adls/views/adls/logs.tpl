{capture name="mainbox"}

	{capture name="sidebar"}
{*		{include file="common/saved_search.tpl" dispatch="logs.manage" view_type="logs"}
		{include file="views/logs/components/logs_search_form.tpl"}*}
	{/capture}

	{include file="common/pagination.tpl"}
    Total entries: {$result.total}

	{if $logs}
		<table class="table">
			<thead>
			<tr>
				<th>{__("id")}</th>
                <th>{__("date")}</th>
                <th>{__("type")}</th>
                <th>{__("object")}</th>
                <th>{__("action")}</th>
				<th>{__("user")}</th>
				<th>{__("product")}</th>
				<th>{__("country")}</th>
				<th>{__("ip")}</th>
				<th>{__("adls.hostname")}</th>
				<th>{__("content")}</th>
				<th>{__("action")}</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$logs item="log"}
				{assign var="_type" value="log_type_`$log.type`"}
				{assign var="_action" value="log_action_`$log.action`"}
				{$class = ""}
				{if fn_adls_log_type_is_error($log.type)}{$class = "alert alert-danger"}{/if}
				{if fn_adls_log_type_is_success($log.type)}{$class = "alert alert-success"}{/if}
				{if fn_adls_log_type_is_warning($log.type)}{$class = "alert alert-warning"}{/if}
				{if fn_adls_log_type_is_log($log.type)}{$class = ""}{/if}

				<tr class="{$class}">
					<td>#{$log.id}</td>
                    <td><span class="nowrap">{$log.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span></td>
                    <td>{$log.type|fn_adls_get_log_type}</td>
                    <td>{$log.objectType}</td>
                    <td>{$log.objectAction}</td>
					<td>
						{if $log.userId}
							<a title="User ID #{$log.userId}" href="{"profiles.update?user_id=`$log.userId`"|fn_url}">{$log.lastname}{if $log.firstname || $log.lastname}&nbsp;{/if}{$log.firstname}</a>
						{/if}
                        {if !empty($log.email)}
                            {$log.email}
                        {/if}
					</td>
                    <td>
                        {$log.product_code}
                    </td>
					<td>{$log.country_name}</td>
					<td>{$log.ip}</td>
					<td>{$log.hostname}</td>
					<td class="wrap">{$log.content}</td>
					<td>
						{include
						file="common/popupbox.tpl"
						id="log_view_`$log.id`"
						act="link"
						text="Log #`$log.id`"
						link_text=__("view")
						href=fn_url("adls.logs?log_id=`$log.id`")
						no_icon_link=true
						opener_ajax_class="cm-ajax"}

						{if !empty($log.backtrace)}
							<p><a onclick="Tygh.$('#backtrace_{$log.id}').toggle(); return false;" class="underlined"><span>{__("backtrace")}</span></a></p>
							<div id="backtrace_{$log.id}" class="notice-box hidden">
								{$log.backtrace|nl2br}
								{*
								{foreach from=$log.backtrace item="v"}
									{$v.file}{if $v.function}&nbsp;({$v.function }){/if}:&nbsp;{$v.line}<br />
								{/foreach}
								*}
							</div>
						{/if}

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
        {include file="buttons/button.tpl" but_text="Self exclude" but_role="action" but_href="adls.logs?self_exclude=1"  but_meta="btn-primary"}
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

{include file="common/mainbox.tpl" title=__("logs") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons}