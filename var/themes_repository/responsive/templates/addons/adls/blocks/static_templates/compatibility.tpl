{** block-description:adls.compatibility **}

{if !empty($product) && !empty($product.compatibility)}
    {foreach from=$product.compatibility item=entry}
        <p>{$entry.max} &mdash; {$entry.min}</p>
    {/foreach}
{/if}
