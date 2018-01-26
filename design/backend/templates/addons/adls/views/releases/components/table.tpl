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

{$release_statuses = fn_adls_get_release_statuses()}

{include file="common/pagination.tpl"}

<table class="table ty-table ty-releases-search">
    <thead>
    <tr>
        {*{tableRowHeader key="product" label="product" sort_sign=$sort_sign search=$search}*}
        {tableRowHeader key="id" label="id" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="version" label="version" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="createdAt" label="date" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="fileName" label="fileName" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="fileSize" label="fileSize" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="userCount" label="user_count" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="" label="adls.compatibility" sort_sign=$sort_sign search=$search}
        {tableRowHeader key="status" label="status" sort_sign=$sort_sign search=$search}
        {hook name="releases:manage_header"}{/hook}

        <th></th>
    </tr>
    </thead>
    {foreach from=$releases item="release"}
        <tr>
            {*<td><strong>{$release->getExtra('product$name')}</strong></td>*}
            <td>{$release->getId()}</td>
            <td>{$release->getVersion()}</td>
            <td>{$release->getCreatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}</td>
            <td>{$release->getFileName()}</td>
            <td>{fn_adls_format_size($release->getFileSize())}</td>
            <td>{$release->getExtra('releaseAccess$userCount')}</td>
            <td>
                {if !empty($release->getCompatibility())}
                    {$htmlId = "rc-`$release->getId()`"}
                    <button data-toggle="collapse" data-target="#{$htmlId}">View</button>
                    <div id="{$htmlId}" class="collapse">
                        {implode('<br />', $release->getCompatibility()) nofilter}
                    </div>
                {else}
                    n/a
                {/if}
            </td>
            <td>
                {include file="common/select_popup.tpl" items_status=$release_statuses popup_additional_class="dropleft" id=$release->getId() status=$release->getStatus() hidden=true object_id_name="id" table="adls_releases"}
            </td>
            <td>
                {include
                file="buttons/button.tpl"
                but_role="action"
                but_text=__("adls.release.download")
                but_href=fn_url("releases.download?release_id=`$release->getId()`")
                but_meta=""}
                {include
                file="buttons/button.tpl"
                but_role="action"
                but_text=__("adls.release.publish")
                but_href=fn_url("releases.publish?release_id=`$release->getId()`")
                but_meta="cm-ajax"}

                {include
                file="buttons/button.tpl"
                but_role="action"
                but_text=__("adls.release.unpublish")
                but_href=fn_url("releases.unpublish?release_id=`$release->getId()`")
                but_meta="cm-ajax"}

                {include
                file="buttons/button.tpl"
                but_role="action"
                but_text=__("delete")
                but_href=fn_url("releases.delete?release_id=`$release->getId()`")
                but_meta="cm-confirm"}

                {include
                file="buttons/button.tpl"
                but_role="action"
                but_text=__("edit")
                but_href=fn_url("releases.update?release_id=`$release->getId()`")
                but_meta=""}
            </td>
        </tr>
    {foreachelse}
        <tr class="ty-table__no-items">
            <td colspan="7"><p class="ty-no-items">{__("no_items")}</p></td>
        </tr>
    {/foreach}
</table>

{include file="common/pagination.tpl"}