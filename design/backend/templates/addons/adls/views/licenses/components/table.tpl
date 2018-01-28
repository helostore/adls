{function tableHeadLink label='' sortBy=''}{strip}
    <a class="cm-ajax"
       href="{"`$c_url`&sort_by=`$sortBy`&sort_order=`$search.sort_order_rev`"|fn_url}"
       data-ca-target-id={$rev}>
        {__($label)}{if $search.sort_by == $sortBy}
            {$c_icon nofilter}
        {else}
            {$c_dummy nofilter}
        {/if}
    </a>
{/strip}{/function}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="c_icon" value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
{assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}

{assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

{assign var="page_title" value=__("adls.licenses")}
{assign var="get_additional_statuses" value=false}

{assign var="extra_status" value=$config.current_url|escape:"url"}
{$statuses = []}
{assign var="order_statuses" value=$smarty.const.STATUSES_ORDER|fn_get_statuses:$statuses:$get_additional_statuses:true}

{if $licenses}
    {strip}
        <table width="100%" class="table table-middle">
            <thead>
            <tr>
                <th class="left">
                    {include file="common/check_items.tpl" check_statuses=$order_status_descr}
                </th>
                <th width="5%">{tableHeadLink label="id" sortBy="id"}</th>
                <th width="5%">{tableHeadLink label="order" sortBy="orderId"}</th>
                <th width="17%">{tableHeadLink label="customer" sortBy="customer"}</th>
                <th width="17%">{tableHeadLink label="product" sortBy="product$name"}</th>
                <th width="17%">{tableHeadLink label="price" sortBy="price"}</th>
                <th width="10%">{tableHeadLink label="status" sortBy="status"}</th>
                <th width="10%">{tableHeadLink label="adls.license.key" sortBy="licenseKey"}</th>
                <th width="17%">{tableHeadLink label="adls.license.updatedAt" sortBy="updatedAt"}</th>
                <th width="17%">{tableHeadLink label="adls.license.createdAt" sortBy="createdAt"}</th>
                {*<th width="17%">{tableHeadLink label="adls.license.domains" sortBy=""}</th>*}

                {hook name="adls_licenses:manage_header"}{/hook}

                <th>&nbsp;</th>
            </tr>
            </thead>
            {foreach from=$licenses item="license"}
                {hook name="adls_licenses:license_row"}
                    <tr>
                        <td class="left">
                            <input type="checkbox" name="ids[]" value="{$license->getId()}" class="cm-item cm-item-status-{$license->getStatus()|lower}" /></td>
                        <td>
                            <a href="{"license.update?id=`$license->getId()`"|fn_url}" class="underlined">#{$license->getId()}</a>
                            {*{include file="views/companies/components/company_name.tpl" object=$license}*}
                        </td>

                        <td>
                            <a href='{fn_url("orders.details?order_id=`$license->getOrderId()`")}' target="_blank">
                                #{$license->getOrderId()}
                            </a>
                        </td>
                        <td>
                            <a href='{fn_url("profiles.update?user_id=`$license->extra['user$id']`")}' target="_blank">
                                {$license->extra['user$lastName']} {$license->extra['user$firstName']} (#{$license->extra['user$id']})
                            </a>
                        </td>
                        <td>
                            <a href='{fn_url("products.update?product_id=`$license->extra['product$id']`")}' target="_blank">
                                {$license->extra['product$name']} (#{$license->extra['product$id']})
                            </a>
                        </td>

                        <td>
                            {include file="common/price.tpl" value=$license->extra['orderItem$price']}
                        </td>
                        <td>
                            {if "MULTIVENDOR"|fn_allowed_for}
                                {assign var="notify_vendor" value=true}
                            {else}
                                {assign var="notify_vendor" value=false}
                            {/if}
                            {$license->getStatusLabel()}

                            {*{include file="common/select_popup.tpl" suffix="o" order_info=$license id=$license->getId() status=$license->getStatus() items_status=$order_status_descr update_controller="orders" notify=true notify_department=true notify_vendor=$notify_vendor status_target_id="orders_total,`$rev`" extra="&return_url=`$extra_status`" statuses=$order_statuses btn_meta="btn btn-info o-status-`$license->status` btn-small"|lower}*}
                        </td>

                        <td>
                            {$license->getLicenseKey()}
                        </td>
                        <td>
                            {$license->getUpdatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}
                        </td>
                        <td>
                            {$license->getCreatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}
                        </td>
                        {*								<td>
                                                            {foreach from=$license->getDomains() item=domain}
                                                                <div class="adls-license-status status-{$domain.status|strtolower}">
                                                                    {$domain.name}
                                                                    &mdash; {$domain.status}
                                                                </div>
                                                            {/foreach}
                                                            <div class="adls-license-status">
                                                            </div>
                                                        </td>*}
                        {*  <td>
                              {if $license->email}<a href="mailto:{$license->email|escape:url}">@</a> {/if}
                              {if $license->user_id}<a href="{"profiles.update?user_id=`$license->user_id`"|fn_url}">{/if}{$license->lastname} {$license->firstname}{if $license->user_id}</a>{/if}
                          </td>
                          <td>{$license->phone}</td>

                          {hook name="orders:manage_data"}{/hook}

                          <td width="5%" class="center">
                              {capture name="tools_items"}
                                  <li>{btn type="list" href="orders.details?order_id=`$license->order_id`" text={__("view")}}</li>
                                  {hook name="orders:list_extra_links"}
                                      <li>{btn type="list" href="order_management.edit?order_id=`$license->order_id`" text={__("edit")}}</li>
                                  {assign var="current_redirect_url" value=$config.current_url|escape:url}
                                      <li>{btn type="list" href="orders.delete?order_id=`$license->order_id`&redirect_url=`$current_redirect_url`" class="cm-confirm cm-post" text={__("delete")}}</li>
                                  {/hook}
                              {/capture}
                              <div class="hidden-tools">
                                  {dropdown content=$smarty.capture.tools_items}
                              </div>
                          </td>
                          <td class="right">
                              {include file="common/price.tpl" value=$license->total}
                          </td>*}
                    </tr>
                {/hook}
            {/foreach}
        </table>
    {/strip}
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl" div_id=$smarty.request.content_id}