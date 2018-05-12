{if !empty($product) && !$product.extra.parent && (!empty($product.license) || !empty($product.releases))}
    {$colSpan = 4}
    {if $order_info.use_discount}
        {$colSpan = $colSpan + 1}
    {/if}
    {if $order_info.taxes && $settings.General.tax_calculation != "subtotal"}
        {$colSpan = $colSpan + 1}
    {/if}
    <tr class="ty-valign-top adls-order-item-license">
        <td colspan="{$colSpan}">
            {if !empty($product.license)}
                <div class="ty-control-group clearfix">
                    <label class="ty-product-options__title">{__('adls.license')}</label>
                    <span class="adls-highlight clearfix">
                        {include file="addons/adls/views/adls_licenses/components/key.tpl" license=$product.license}
                        {if !$product.license->hasDomains()}
                            &mdash; {$product.license->getStatus()|fn_adls_get_license_status_label}
                        {/if}
                    </span>
                    {*<div class="adls-license-status status-{$product.license.status|strtolower}">{$product.license.status|fn_adls_get_license_status_label}</div>*}
                </div>
                {if $product.license->hasDomains()}
                    <div class="ty-control-group clearfix">
                        <label class="ty-product-options__title">{__('adls.domains')}</label>
                        <div class="adls-license-domains" id="adls_license_domains_{$product.product_id}">
                            <form class="cm-ajax cm-ajax-full-render" action="{""|fn_url}" method="post"
                                  name="adls_domains_update_{$product.product_id}">
                                <input type="hidden" name="redirect_url" value="{$config.current_url}"/>
                                <input type="hidden" name="result_ids" value="adls_license_domains_*"/>
                                <input type="hidden" name="order_id" value="{$order_info.order_id}"/>
                                {foreach from=$product.license->getDomains() item=domain}
                                    {$isDomainLicenseDisabled = fn_adls_license_is_disabled($domain.status)}
                                    {*{if !$isDomainLicenseDisabled}*}
                                    {if !$product.license->isDisabled()}
                                        <div class="adls-license-status status-{$domain.status|strtolower}">
                                            <input name="licenses[{$product.license->getId()}][domains][{$domain.id}]"
                                                   value="{$domain.name}" class="ty-input-text adls-hostname"
                                                   type="text" size="36"/>
                                            &mdash; {$domain.status|fn_adls_get_license_status_label}
                                        </div>
                                    {else}
                                        {if !empty($domain.name)}
                                            {$domain.name|default:""} &mdash; {$domain.status|fn_adls_get_license_status_label}
                                        {/if}
                                    {/if}
                                {/foreach}
                                <div class="adls-license-status">
                                    {if !fn_adls_license_is_disabled($product.license->getStatus()) && empty($product.license->hasAllDomainsDisabled())}
                                        {include file="buttons/button.tpl" but_text=__("adls.domains.update") but_id="adls_domains_update_button_`$product.product_id`" but_meta="ty-btn__secondary" but_name="dispatch[orders.details]" but_role="action" obj_id=$product.product_id}
                                    {/if}
                                </div>
                            </form>
                            <!--adls_license_domains_{$product.product_id}--></div>
                    </div>
                {/if}
            {/if}
            {include file="addons/adls/views/adls_releases/components/product.tpl"}
        </td>
    </tr>
    {* If there's a subscription attached to this product, let the subscription add-on to take care of retrieving the releases *}
    {* Else, dump them here *}
    {if empty($product.subscription)}
    {/if}
{/if}
