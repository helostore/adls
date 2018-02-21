{** block-description:adls.compatibility **}

{if !empty($product) && !empty($product.compatibility)}
    {foreach from=$product.compatibility item=entry}
        <p>{$entry.min} &mdash; {$entry.max}</p>
    {/foreach}
{/if}