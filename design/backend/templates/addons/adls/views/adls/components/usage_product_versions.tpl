{if $usage}
	<h4>Usage: product versions</h4>
	<table class="table">
		<thead>
		<tr>
			<th>Version</th>
			<th>Occurrence</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$usage key="version" item="occurrence"}
			<tr>
				<td>{$version}</td>
				<td>{$occurrence}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{else}
	<p class="no-items">{__("no_data")}</p>
{/if}