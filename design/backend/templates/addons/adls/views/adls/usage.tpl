{capture name="mainbox"}
    {include file="addons/adls/views/adls/components/usage.tpl"}
{/capture}

{include file="common/mainbox.tpl" title="ADLS API Usage" content=$smarty.capture.mainbox}