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

<table class="ty-table ty-releases-search">
    <thead>
    <tr>
        {tableRowHeader key="product" label="product" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="version" label="version" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="" label="adls.compatibility" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="createdAt" label="date" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="fileName" label="fileName" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="fileSize" label="fileSize" sort_sign=$sort_sign search=$search}

        {hook name="releases:manage_header"}{/hook}

        <th>Download</th>
    </tr>
    </thead>
    {foreach from=$releases item="release"}
        <tr>
            <td><strong>{$release->getExtra('product$name')}</strong></td>
            <td>{$release->getVersion()}</td>
            <td>
                {$compatiblity = $release->getCompatibility()}
                {if !empty($compatiblity)}
                    {$firstTwo = ""}
                    {if count($compatiblity) >= 2}
                        {$firstTwo = implode("<br />", array_slice($compatiblity, 0, 2))}
                    {/if}

                    {$htmlId = "rc-`$release->getId()`"}
                    {if count($compatiblity) > 2}
                        <a class="cm-combination cm-save-state cm-ss-reverse pull-left" id="sw_{$htmlId}">
                            <span class="ty-section__switch ty-section_switch_on">
                                View more<i class="ty-section__arrow ty-icon-down-open"></i><br/>
                                {$firstTwo nofilter}
                                <br>...
                            </span>
                            <span class="ty-section__switch ty-section_switch_off">
                                View less <i class="ty-section__arrow ty-icon-up-open"></i> <br/>
                            </span>
                        </a>
                        <br/>
                        <div id="{$htmlId}" class="hidden">
                            {implode('<br />', $compatiblity) nofilter}
                        </div>
                    {else}
                        {$firstTwo nofilter}
                        <br/>
                    {/if}
                {else}
                    n/a
                {/if}
            </td>
            <td>{$release->getCreatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}</td>
            <td>{$release->getFileName()}</td>
            <td>{fn_adls_format_size($release->getFileSize())}</td>
            <td>
                {include file="addons/adls/views/adls_releases/components/download_button.tpl" release=$release}

                {if !isset($smarty.request.product_id)}
                    <a class="ty-btn ty-btn__secondary ty-btn"
                       href="{"adls_releases.view?product_id={$release->getProductId()}"|fn_url}">View all</a>
                {/if}
            </td>
        </tr>
        {foreachelse}
        <tr class="ty-table__no-items">
            <td colspan="7"><p class="ty-no-items">{__("no_items")}</p></td>
        </tr>
    {/foreach}
</table>

{include file="common/pagination.tpl"}

{capture name="mainbox_title"}
    {__("adls.releases")}
    {if !empty($product)}
        &mdash; {$product.product}
    {/if}

{/capture}