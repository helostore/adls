<fieldset>
	<div class="control-group">
		<label for="product_option_type_{$id}" class="control-label">{__("adls.product_option_type")}</label>
		<div class="controls">
			<select id="product_option_type_{$id}" name="option_data[adls_option_type]">
				<option></option>
				{foreach from=$adls_option_types item="label" key="key"}
					{$selected = ''}
					{if !empty($option_data) && $option_data.adls_option_type == $key}
						{$selected = 'selected="selected"'}
					{/if}
					<option value="{$key}" {$selected}>{$label}</option>
				{/foreach}
			</select>
		</div>
	</div>
</fieldset>