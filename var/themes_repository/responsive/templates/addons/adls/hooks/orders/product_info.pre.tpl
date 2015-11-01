{if !empty($product)}
	{if fn_is_adls_product($product) && !empty($product.license)}
		<div class="ty-control-group clearfix">
			<label class="ty-product-options__title">{__('adls.license')}</label>
			<span class="adls-highlight clearfix">
				{$htmlId = "license_`$product.license.license_id`"}
				<input value="{$product.license.license_key}" readonly="readonly" class="ty-input-text" id="{$htmlId}" size="36" />
				<button class="ty-btn ty-btn__secondary adls-clipboard" data-clipboard-target="#{$htmlId}" title="{__('adls.copy_to_clipboard')}">
					<i class="fa fa-clipboard"></i>
				</button>
			</span>
		</div>
	{/if}
{/if}