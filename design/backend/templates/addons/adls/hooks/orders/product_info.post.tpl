{$product = null}
{if !empty($oi)}
    {$product = $oi}
{/if}

{if !empty($product) && fn_is_adls_product($product) && !empty($product.license)}
    <div class="ty-control-group clearfix">
        <label class="ty-product-options__title">{__('adls.license')}</label>
        <span class="adls-highlight clearfix">
            {$htmlId = "license_`$product.license->getId()`"}
            <input value="{$product.license->getLicenseKey()}" readonly="readonly" class="ty-input-text" type="text" id="{$htmlId}" size="36" />

            &mdash; {$product.license->getStatus()|fn_adls_get_license_status_label}

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
    {if $product.license->hasDomains()}
        <div class="ty-control-group clearfix">
            <label class="ty-product-options__title">{__('adls.domains')}</label>
            <div class="adls-license-domains" id="adls_license_domains_{$product.product_id}">
                <form class="cm-ajax cm-ajax-full-render" action="{""|fn_url}" method="post" name="adls_domains_update_{$product.product_id}">
                    <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                    <input type="hidden" name="result_ids" value="adls_license_domains_*" />
                    <input type="hidden" name="order_id" value="{$order_info.order_id}" />
                    {foreach from=$product.license->getDomains() item=domain}
                        <div class="adls-license-status status-{$domain.status|strtolower}">
                            <input name="licenses[{$product.license->getId()}][domains][{$domain.id}]" value="{$domain.name}" class="ty-input-text adls-hostname" type="text" size="36" />
                            &mdash; {$domain.status|fn_adls_get_license_status_label}
                        </div>
                    {/foreach}
                    <div class="adls-license-status">
                        {include file="buttons/button.tpl" but_text=__("update") but_id="adls_domains_update_button_`$product.product_id`" but_meta="ty-btn__secondary" but_name="dispatch[orders.details]" but_role="action" obj_id=$product.product_id}
                    </div>
                </form>
                <!--adls_license_domains_{$product.product_id}--></div>
        </div>
    {/if}
{/if}
