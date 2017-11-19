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
            <td>{$release->getFileName()}</td>
            <td>{fn_adls_format_size($release->getFileSize())}</td>
            <td>
                <a class="ty-btn ty-btn__primary ty-btn" href="{"adls_releases.download?hash={$release->getHash()}"|fn_url}">Download</a>
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