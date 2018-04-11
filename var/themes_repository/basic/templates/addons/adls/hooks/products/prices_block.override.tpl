{if $show_price_values && $show_price}
    {if $product.price|floatval || $product.zero_price_action == "P" || ($hide_add_to_cart_button == "Y" && $product.zero_price_action == "A")}

    {elseif $product.zero_price_action == "A" && $show_add_to_cart}

    {elseif $product.zero_price_action == "R"}
        {if !empty($product.has_beta_testing_program)}
            <span class="ty-no-price adls-beta-test">{__("adls.product.beta_test_group_short")}</span>
        {else}
            <span class="ty-no-price adls-not-released">{__("adls.product.not_released_yet_short")}</span>
        {/if}
    {/if}
{/if}
