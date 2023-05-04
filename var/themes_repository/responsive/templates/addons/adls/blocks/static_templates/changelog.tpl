{** block-description:adls.changelog **}
{if !empty($product) && !empty($product.releases)}{strip}
    {foreach from=$product.releases item="release"}
        <pre><strong><span style="display:inline-block; min-width: 60px">{$release->getVersion()}</span></strong><small> -  {$release->getCreatedAt()->getTimestamp()|date_format:"`$settings.Appearance.date_format`"}</small></pre>
        {if $release->hasChangeLog()}
            {$changeLogLines = explode("\r\n", $release->getChangeLog())}
            <pre>
                 {foreach from=$changeLogLines item="line"}
                     &bull; {$line}{$smarty.const.PHP_EOL}
                {/foreach}
            </pre>
        {/if}
    {/foreach}
{/strip}{/if}
