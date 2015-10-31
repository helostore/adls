<div id="content_adls" class="hidden">
	{include file="common/subheader.tpl" title=__("adls") target="#acc_adls"}
	<div id="acc_adls" class="collapsed in">

		<div class="control-group">
			<label class="control-label" for="product_weight">{__("adls.addons")}:</label>
			<div class="controls">
				<select>
					<option></option>
					{foreach from=$adls_addons item="addon" key="id"}
						<option value="{$id}">{$addon.name} {$addon.version}</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

<!--content_adls--></div>