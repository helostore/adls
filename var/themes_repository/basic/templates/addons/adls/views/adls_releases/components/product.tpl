{if !empty($product.releases)}
    <div class="ty-control-group clearfix">
        <label class="ty-product-options__title">Releases</label>
        <div class="adls-product-releases">
            <ul>
                {foreach from=$product.releases item="release"}
                    <li>
                        <a href="{"adls_releases.download?hash=`$release->getHash()`"|fn_url}">
                            <span class="ty-btn">Download latest release</span>
                            &nbsp; {$release->getFileName()}, released on {$release->getCreatedAt()->getTimestamp()|date_format:$settings.Appearance.date_format}
                        </a>
                    </li>
                {/foreach}
            </ul>
        </div>
        <p>Or <a class="ty-btn ty-tertiary" href="{"adls_releases.view?product_id=`$product.product_id`"|fn_url}">view detailed releases</a> (as compatibility with various CS-Cart versions)</p>
    </div>
{else}
    Downloads not ready, yet.
{/if}
