{if !empty($product.releases)}
    <div class="ty-control-group clearfix">
        <label class="ty-product-options__title">Releases</label>
        <div class="adls-product-releases">
            <ul>
                {foreach from=$product.releases item="release"}
                    <li>
                        {include file="addons/adls/views/adls_releases/components/download_button.tpl" release=$release showDetails=true buttonText="Download latest release"}
                    </li>
                {/foreach}
            </ul>
        </div>
        <p>Or <a class="ty-btn ty-tertiary" href="{"adls_releases.view?product_id=`$product.product_id`"|fn_url}">view detailed releases</a></p>
    </div>
{else}
    Downloads not ready, yet.
{/if}
