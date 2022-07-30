{if !isset($showDetails)}
    {$showDetails = false}
{/if}

{if !empty($release) && $release->isFileFound()}
    <a href="{"adls_releases.download?hash=`$release->getHash()`"|fn_url}">
        <span class="ty-btn ty-btn__primary ty-btn">{$buttonText|default:"Download"}</span>
        {if !empty($showDetails)}
            &nbsp; {$release->getFileName()}{if ! $release->isProduction()} ({$release->getStatusLabel()}){/if}, released on {$release->getCreatedAt()->getTimestamp()|date_format:$settings.Appearance.date_format}
        {/if}
    </a>
{else}
    {include file="addons/adls/blocks/error.tpl" compact=!$showDetails shortText="Oops!" longText="Download not available due to an error."}
{/if}
