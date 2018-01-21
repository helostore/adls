{** block-description:adls.compatibility **}

{if !empty($product) && !empty($product.compatibility)}
    {$product.compatibility.min} &mdash; {$product.compatibility.max}
{/if}