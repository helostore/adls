<td class="ty-subscriptions-search__item">
    {include file="addons/adls/views/adls_licenses/components/key.tpl" license=$subscription->getLicense()}
    <br>
    <span class="adls-license-status status-{$subscription->getLicense()->getStatus()|strtolower}">
        {$subscription->getLicense()->getStatus()|fn_adls_get_license_status_label}
    </span>
</td>
<td class="ty-subscriptions-search__item">
    {include file="addons/adls/views/adls_licenses/components/domains_view_list.tpl" domains=$subscription->getLicense()->getDomains()}
</td>