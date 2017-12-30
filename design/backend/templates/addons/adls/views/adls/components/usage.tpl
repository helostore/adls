{if $usage}
	<table class="table">
		<thead>
		<tr>
			<th>Platform</th>
			<th>Edition</th>
			<th>Version</th>
			<th>Requests</th>
			<th>Hostname</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$usage key="platform" item="editions"}
			{foreach from=$editions key="edition" item="versions"}
				{foreach from=$versions key="version" item="entry"}
					<tr>
						<td>{$platform}</td>
						<td>{$edition}</td>
						<td>{$version}</td>
						<td>{$entry.requests}</td>
						<td>
							{if is_array($entry.hostname)}
                                {foreach from=$entry.hostname key="hostname" item="productVersions"}
									{$hostname} ({", "|implode:$productVersions})
								{/foreach}
							{else}
                                {", "|implode:$entry.hostname}
                            {/if}
						</td>
					</tr>
				{/foreach}
			{/foreach}
		{/foreach}
		</tbody>
	</table>
{else}
	<p class="no-items">{__("no_data")}</p>
{/if}