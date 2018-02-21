<div id="content_adls_sources" class="hidden">
    {include file="common/subheader.tpl" title=__("adls.sources") target="#acc_adls_sources"}
    <div id="acc_adls_sources" class="collapsed in">








        <table class="table ty-table ty-releases-search">
            <thead>
            <tr>
                {tableRowHeader key="id" label="id" sort_sign=$sort_sign search=$search}
                {tableRowHeader key="platform" label="platform" sort_sign=$sort_sign search=$search}
                {tableRowHeader key="sourcePath" label="sourcePath" sort_sign=$sort_sign search=$search}
                {tableRowHeader key="releasePath" label="releasePath" sort_sign=$sort_sign search=$search}
                <th></th>
            </tr>
            </thead>
            {foreach from=$adls_sources item="source"}
                <tr>
                    <td>{$source->getId()}</td>
                    <td>{$source->getExtra('platform$name')}</td>
                    <td>{$source->getSourcePath()}</td>
                    <td>{$source->getReleasePath()}</td>
                    <td>

                    </td>
                </tr>
                {foreachelse}
                <tr class="ty-table__no-items">
                    <td colspan="7"><p class="ty-no-items">{__("no_items")}</p></td>
                </tr>
            {/foreach}
        </table>





        {capture name="add_source"}
            {include file="addons/adls/views/sources/update.tpl" platforms=$adls_platforms}
        {/capture}



        {include file="common/popupbox.tpl"
        id="add_adls_source"
        text=__("adls.source.add")
        link_text=__("adls.source.add")
        content=$smarty.capture.add_source
        act="general"
        icon="icon-plus"}

    </div>

<!--content_adls_sources--></div>