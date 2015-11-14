{capture name="mainbox"}

	{capture name="sidebar"}
		{include file="common/saved_search.tpl" dispatch="logs.manage" view_type="logs"}
		{include file="views/logs/components/logs_search_form.tpl"}
	{/capture}

	{include file="common/pagination.tpl"}

	{if $logs}
		<table class="table">
			<thead>
			<tr>
				<th>{__("id")}</th>
				<th>{__("type")}</th>
				<th>{__("object")}</th>
				<th>{__("action")}</th>
				<th>{__("date")}</th>
				<th>{__("user")}</th>
				<th>{__("ip")}</th>
				<th>{__("content")}</th>
				<th>{__("backtrace")}</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$logs item="log"}
				{assign var="_type" value="log_type_`$log.type`"}
				{assign var="_action" value="log_action_`$log.action`"}
				<tr>
					<td>{$log.log_id}</td>
					<td>{$log.type}</td>
					<td>{$log.object_type}</td>
					<td>{$log.object_action}</td>
					<td><span class="nowrap">{$log.timestamp|date_format:"`$settings.Appearance.date_format` `$settings.Appearance.time_format`"}</span></td>
					<td>
						{if $log.user_id}
							<a href="{"profiles.update?user_id=`$log.user_id`"|fn_url}">{$log.lastname}{if $log.firstname || $log.lastname}&nbsp;{/if}{$log.firstname}</a>
						{/if}
					</td>
					<td>{$log.ip}</td>
					<td class="wrap">{$log.content}</td>
					<td class="wrap">
						{if !empty($log.backtrace)}
						<p><a onclick="Tygh.$('#backtrace_{$log.log_id}').toggle(); return false;" class="underlined"><span>{__("backtrace")}&rsaquo;&rsaquo;</span></a></p>
						<div id="backtrace_{$log.log_id}" class="notice-box hidden">
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

{include file="common/mainbox.tpl" title=__("logs") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar}