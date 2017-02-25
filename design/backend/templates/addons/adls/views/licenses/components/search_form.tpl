{assign var="status_descr" value=fn_adls_get_license_statuses()}

{if $in_popup}
<div class="adv-search">
    <div class="group">
        {else}
        <div class="sidebar-row">
            <h6>{__("search")}</h6>
            {/if}

            <form action="{""|fn_url}" name="licenses_search_form" method="get" class="{$form_meta}">
                {capture name="simple_search"}

                    {if $smarty.request.redirect_url}
                        <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
                    {/if}
                    {if $selected_section != ""}
                        <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
                    {/if}

                    {$extra nofilter}

                    <div class="sidebar-field">
                        <label for="customerName">{__("customer")}</label>
                        <input type="text" name="customerName" id="customerName" value="{$search.customerName}" size="30" />
                    </div>

                    <div class="sidebar-field">
                        <label for="email">{__("email")}</label>
                        <input type="text" name="email" id="email" value="{$search.email}" size="30"/>
                    </div>

                    <div class="sidebar-field">
                        <label for="orderId">{__("order")}</label>
                        <input type="text" name="orderId" id="orderId" value="{$search.orderId}" size="30" />
                    </div>

                    <div class="sidebar-field">
                        <label for="issuer">{__("adls.license.key")}</label>
                        <input type="text" name="licenseKey" id="licenseKey" value="{$search.licenseKey}" size="30" />
                    </div>

                {/capture}

                {capture name="advanced_search"}

                    {hook name="licenses:advanced_search"}

                        <div class="group">
                            <div class="control-group">
                                <label class="control-label">{__("order_status")}</label>
                                <div class="controls checkbox-list">
                                    {include file="common/status.tpl" order_status_descr=$status_descr status=$search.status display="checkboxes" name="status" columns=5}
                                </div>
                            </div>
                        </div>

                        <div class="group">
                            <div class="control-group">
                                <label class="control-label">{__("ordered_products")}</label>
                                <div class="controls ">
                                    {include file="common/products_to_search.tpl" placement="right"}
                                </div>
                            </div>
                        </div>
                    {/hook}

                    <div class="group">
                        <div class="control-group">
                            {hook name="licenses:search_form"}
                            {/hook}
                        </div>
                    </div>

                {/capture}

                {include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="licenses" in_popup=$in_popup}

            </form>

            {if $in_popup}
        </div></div>
    {else}
</div><hr>
{/if}