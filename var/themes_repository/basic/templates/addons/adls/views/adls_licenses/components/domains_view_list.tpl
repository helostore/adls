{strip}
    {if !empty($domains)}
        <ul class="adls-domains-view list">
            {foreach from=$domains item="domain" name="domains"}
                {$name = $domain}
                {if is_array($domain)}
                    {$name = $domain.name}
                {/if}
                <li title="{$name}">{$name}</li>
            {/foreach}
        </ul>
    {/if}
{/strip}