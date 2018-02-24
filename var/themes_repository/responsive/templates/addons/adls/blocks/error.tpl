<span class="alert alert-error" style="display: inline-block; padding: 8px;">
    {if !empty($compact)}
        <i class='ty-icon-ban-circle'></i> {$shortText nofilter}
        Please <a href="https://helostore.com/contact">contact us</a>.
    {else}
        {$longText|default:"Something went wrong." nofilter}
        Please <a href="https://helostore.com/contact">contact us</a> so we can help you.
    {/if}
</span>