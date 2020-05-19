{if $usage}
	<h4>Usage: product platforms</h4>
	<table class="table">
		<thead>
		<tr>
			<th>Platform</th>
			<th>Edition</th>
			<th>Version</th>
			<th>Requests</th>
			<th>Hostname</th>
			<th>Product</th>
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
                            {foreach from=$entry.hostname key="hostname" item="productVersions"}
                                {$hostname}
								{if is_array($productVersions)}
									({", "|implode:$productVersions})
								{else}
									({$productVersions})
								{/if}
								<br>
                            {/foreach}
{*							{if is_array($entry.hostname.0)}

							{else}
                                {", "|implode:$entry.hostname}
                            {/if}*}
						</td>
						<td>
                            {if !empty($entry.productVersions)}
                                {foreach from=$entry.productVersions item="count" key="productVersion"}
                                    {$productVersion} (#{$count})
                                {/foreach}
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