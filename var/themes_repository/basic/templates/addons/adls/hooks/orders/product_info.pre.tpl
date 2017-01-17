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
				{if empty($product.license.domains)}
					&mdash; {$product.license.status|fn_adls_get_license_status_label}
				{/if}
			</span>
			{*<div class="adls-license-status status-{$product.license.status|strtolower}">{$product.license.status|fn_adls_get_license_status_label}</div>*}
		</div>
		{if !empty($product.license.domains)}
			<div class="ty-control-group clearfix">
				<label class="ty-product-options__title">{__('adls.domains')}</label>
				<div class="adls-license-domains" id="adls_license_domains_{$product.product_id}">
					<form class="cm-ajax cm-ajax-full-render" action="{""|fn_url}" method="post" name="adls_domains_update_{$product.product_id}">
						<input type="hidden" name="redirect_url" value="{$config.current_url}" />
						<input type="hidden" name="result_ids" value="adls_license_domains_*" />
						<input type="hidden" name="order_id" value="{$order_info.order_id}" />
						{foreach from=$product.license.domains item=domain}
							<div class="adls-license-status status-{$domain.status|strtolower}">
								{if !fn_adls_license_is_disabled($domain.status)}
								<input name="licenses[{$product.license.license_id}][domains][{$domain.domain_id}]" value="{$domain.name}" class="ty-input-text adls-hostname" type="text" size="36" />
								{else}
									{$domain.name}
								{/if}
								&mdash; {$domain.status|fn_adls_get_license_status_label}
							</div>
						{/foreach}
						<div class="adls-license-status">
							{if !fn_adls_license_is_disabled($product.license.status) && empty($product.license.domains_disabled)}
								{include file="buttons/button.tpl" but_text=__("update") but_id="adls_domains_update_button_`$product.product_id`" but_meta="ty-btn__secondary" but_name="dispatch[orders.details]" but_role="action" obj_id=$product.product_id}
							{/if}
						</div>
					</form>
				<!--adls_license_domains_{$product.product_id}--></div>
			</div>
		{/if}
	{/if}
{/if}