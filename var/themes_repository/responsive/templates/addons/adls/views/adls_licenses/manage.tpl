{function tableRowHeader label="" key="" search="" sort_sign=""}
    <th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=`$key`&sort_order=`$search.sort_order_rev`"|fn_url}"
           data-ca-target-id="pagination_contents">{__($label)}</a>{if $search.sort_by == $key}{$sort_sign nofilter}{/if}
    </th>
{/function}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{if $search.sort_order == "asc"}
    {assign var="sort_sign" value="<i class=\"ty-icon-down-dir\"></i>"}
{else}
    {assign var="sort_sign" value="<i class=\"ty-icon-up-dir\"></i>"}
{/if}
{if !$config.tweaks.disable_dhtml}
    {assign var="ajax_class" value="cm-ajax"}
{/if}

{include file="common/pagination.tpl"}

<table class="ty-table ty-licenses-search">
    <thead>
    <tr>
        {*{tableRowHeader key="id" label="id" sort_sign=$sort_sign search=$search}*}
        {tableRowHeader key="productId" label="product" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="licenseKey" label="adls.license.key" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="domains" label="adls.domains" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="status" label="status" sort_sign=$sort_sign search=$search}
        {hook name="licenses:manage_header"}{/hook}
        {tableRowHeader key="order_id" label="order" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="createdAt" label="adls.license.createdAt" sort_sign=$sort_sign search=$search}
        {*{tableRowHeader key="updatedAt" label="adls.license.updatedAt" sort_sign=$sort_sign search=$search}*}
        <th>Download</th>
    </tr>
    </thead>
    {foreach from=$licenses item="license"}
        <tr>
            {*<td class="ty-licenses-search__item"><strong>#{$license->getId()}</strong></td>*}
            <td class="ty-licenses-search__item"><strong>{$license->extra['product$name']}</strong></td>
            <td class="ty-subscriptions-search__item">{include file="addons/adls/views/adls_licenses/components/key.tpl" license=$license}</td>
            <td class="ty-subscriptions-search__item">
                {include file="addons/adls/views/adls_licenses/components/domains_view_list.tpl" domains=$license->getDomains()}
            </td>
            <td class="ty-subscriptions-search__item">{$license->getStatus()|fn_adls_get_license_status_label}</td>
            {hook name="licenses:manage_data"}{/hook}
            <td class="ty-licenses-search__item"><a href="{"orders.details?order_id=`$license->getOrderId()`"|fn_url}">#{$license->getOrderId()}</a></td>
            <td class="ty-licenses-search__item">{$license->getCreatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}</td>
            {*<td class="ty-licenses-search__item">{$license->getUpdatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}</td>*}
            <td>
                {$hasLatest = false}
                {if !empty($license->latestRelease)}{$hasLatest = true}{/if}

                {$hasOther = false}
                {if !empty($license->otherReleases)}{$hasOther = true}{/if}

                <a class="ty-btn ty-btn__primary ty-btn {if $hasLatest}_cm-post _cm-ajax{else}ui-state-disabled{/if}" {if $hasLatest}href="{"adls_releases.download?hash={$license->latestRelease->getHash()}"|fn_url}"{/if}>Latest</a>
                <a class="ty-btn ty-btn__secondary ty-btn {if $hasOther}_cm-post _cm-ajax{else}ui-state-disabled{/if}" {if $hasOther}href="{"adls_releases.view?product_id={$license->getProductId()}"|fn_url}"{/if}>Other</a>
            </td>
        </tr>
    {foreachelse}
        <tr class="ty-table__no-items">
            <td colspan="7"><p class="ty-no-items">{__("no_items")}</p></td>
        </tr>
    {/foreach}
</table>

{include file="common/pagination.tpl"}

{capture name="mainbox_title"}{__("adls.licenses")}{/capture}