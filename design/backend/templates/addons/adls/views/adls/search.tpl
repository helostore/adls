<div class="sidebar-row">
    <h6>{__("search")}</h6>
    <form action="{""|fn_url}" name="logs_form" method="get">
        <input type="hidden" name="object" value="{$smarty.request.object}">

        {capture name="simple_search"}
            {include file="common/period_selector.tpl" period=$search.period extra="" display="form" button="false"}
            <div class="control-group">
                <label class="control-label">Request pattern:</label>
                <div class="controls">
                    <input type="text" name="requestPattern" size="30" value="{$search.requestPattern}">
                </div>
            </div>

        {/capture}

        {capture name="advanced_search"}
        {/capture}

        {include file="common/advanced_search.tpl" advanced_search=$smarty.capture.advanced_search simple_search=$smarty.capture.simple_search dispatch="adls.logs" view_type="logs"}
    </form>
</div>