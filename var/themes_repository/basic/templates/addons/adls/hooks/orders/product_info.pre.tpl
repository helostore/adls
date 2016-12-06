{if !empty($product)}
	{if fn_is_adls_product($product) && !empty($product.license)}
		<div class="ty-control-group clearfix">
			<label class="ty-product-options__title">{__('adls.license')}</label>
			<span class="adls-highlight clearfix">
				{$htmlId = "license_`$product.license.license_id`"}
				<input value="{$product.license.license_key}" readonly="readonly" class="ty-input-text" type="text" id="{$htmlId}" size="36" />
				<button class="ty-btn ty-btn__secondary adls-clipboard" data-clipboard-target="#{$htmlId}" title="{__('adls.copy_to_clipboard')}">
					<i class="fa fa-clipboard"></i>
					<span class="icon svg hidden">
						<svg xmlns="http://www.w3.org/2000/svg" width="72px" height="72px">
							<g fill="none" stroke="#8EC343" stroke-width="2">
								<circle cx="36" cy="36" r="35" style="stroke-dasharray:240px, 240px; stroke-dashoffset: 480px;"></circle>
								<path d="M17.417,37.778l9.93,9.909l25.444-25.393" style="stroke-dasharray:50px, 50px; stroke-dashoffset: 0px;"></path>
							</g>
						</svg>
					</span>
				</button>
			</span>
			{*<div class="adls-license-status status-{$product.license.status|strtolower}">{$product.license.status|fn_adls_get_license_status_label}</div>*}
		</div>
		{if !empty($product.license.domains)}
			<div class="ty-control-group clearfix">
				<label class="ty-product-options__title">{__('adls.domains')}</label>
				<div class="adls-license-domains">
					{foreach from=$product.license.domains item=domain}
						<div class="adls-license-status status-{$domain.status|strtolower}">
							<input value="{$domain.domain}" class="ty-input-text" type="text" size="36" />
							&mdash; {$domain.status|fn_adls_get_license_status_label}
						</div>
					{/foreach}
				</div>
			</div>
		{/if}
	{/if}
{/if}