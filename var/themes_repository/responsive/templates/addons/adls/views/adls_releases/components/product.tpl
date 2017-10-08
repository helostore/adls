{if !empty($product.releases)}

    <div class="ty-control-group clearfix">
        <label class="ty-product-options__title">Releases</label>
        <div class="adls-product-releases">
            <ul>
                {foreach from=$product.releases item="release"}
                    <li>
                        <a href="{"adls_releases.download?orderId=`$product.order_id`&orderItemId=`$product.item_id`&id=`$release->getId()`"|fn_url}">
                            <span class="ty-btn">Download</span>
                            {$release->getFileName()}, released on {$release->getCreatedAt()->getTimestamp()|date_format:$settings.Appearance.date_format}
                        </a>
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>
{else}
    Downloads not ready, yet.
{/if}
