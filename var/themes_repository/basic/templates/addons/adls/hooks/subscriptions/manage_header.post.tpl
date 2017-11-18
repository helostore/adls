{function tableRowHeader label="" key="" search="" sort_sign=""}
    <th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=`$key`&sort_order=`$search.sort_order_rev`"|fn_url}"
           data-ca-target-id="pagination_contents">{__($label)}</a>{if $search.sort_by == $key}{$sort_sign nofilter}{/if}
    </th>
{/function}

{tableRowHeader key="licenseKey" label="adls.license" sort_sign=$sort_sign search=$search}
{tableRowHeader key="licenseDomains" label="adls.domains" sort_sign=$sort_sign search=$search}
