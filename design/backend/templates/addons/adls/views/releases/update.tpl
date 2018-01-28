<style>
    .compatibility {
        display: flex;
        flex-direction: column;
        max-width: 800px;
        max-height: 400px;
        flex-wrap: wrap;
    }
    .compatibility-version {
        display: inline-block;
        margin-bottom: 5px;
        white-space: nowrap;
        flex-grow: 1;
    }
</style>

<script>

    $('.select-all').click(function(e){
        var checked = e.currentTarget.checked;
        $('.list-item-checkbox').prop('checked', checked);
        countChecked((checked) ? 20 : 0);
    });

    var lastChecked = null;
    $('.list-item-checkbox').click(function(e){
        var selectAllChecked = $('.select-all:checked').length ? true : false;

        if (selectAllChecked) {
            var itemsTotal = $('.list-item-checkbox').length;
            var uncheckedItemsTotal = itemsTotal - checkedItemsTotal();
            var selected = 20 - uncheckedItemsTotal;
            countChecked(selected);
        } else {
            countChecked();
        }

        if(!lastChecked) {
            lastChecked = this;
            return;
        }

        if(e.shiftKey) {
            var from = $('.list-item-checkbox').index(this);
            var to = $('.list-item-checkbox').index(lastChecked);

            var start = Math.min(from, to);
            var end = Math.max(from, to) + 1;

            $('.list-item-checkbox').slice(start, end)
                .filter(':not(:disabled)')
                .prop('checked', lastChecked.checked);
            countChecked();
        }
        lastChecked = this;

        if(e.altKey){

            $('.list-item-checkbox')
                .filter(':not(:disabled)')
                .each(function () {
                    var $checkbox = $(this);
                    $checkbox.prop('checked', !$checkbox.is(':checked'));
                    countChecked();

                });
        }

    });
    function countChecked(number){
        number = number ? number : checkedItemsTotal();
        $('#counter-selected').html(number);
    }

    function checkedItemsTotal(){
        return $('.list-item-checkbox:checked').length;
    }
</script>
{if !empty($release)}
    {$title = __('adls.release.edit.title', ['%id%' => $release->getId()])}
    {$submitButtonText = __('adls.release.edit.submit')}
{else}
    {$title = __('adls.release.new.title')}
    {$submitButtonText = __('adls.release.new.submit')}
{/if}


{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" class="form-horizontal form-edit" name="release_update_form">
        {if !empty($release)}
            <input type="hidden" name="release_id" value="{$release->getId()}"/>
        {/if}

        <div class="control-group">
            <label class="control-label">Development Version</label>
            <div class="controls">{$product.version}</div>
        </div>
        <div class="control-group">
            <label class="control-label">Latest published version</label>
            <div class="controls">{$product.adls_release_version}</div>
        </div>
        <hr>

        <div class="control-group">
            <label class="control-label">Add-on ID:</label>
            <div class="controls">
                <input type="text" name="addon_id" readonly value="{$product.adls_addon_id}" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">{__("adls.release.version")}:</label>
            <div class="controls">
                <input type="text" readonly value="{if !empty($release)}{$release->getVersion()}{/if}" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">{__("adls.release.created_at")}:</label>
            <div class="controls">
                <input type="text" readonly value="{if !empty($release)}{$release->getCreatedAt()->format('Y-m-d H:i:s')}{/if}" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">{__("adls.release.file.name")}:</label>
            <div class="controls">
                <input type="text" readonly value="{if !empty($release)}{$release->getFileName()}{/if}" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">{__("adls.release.hash")}:</label>
            <div class="controls">
                <input type="text" readonly value="{if !empty($release)}{$release->getHash()}{/if}" />
            </div>
        </div>


        <div class="control-group">
            <label class="control-label">{__("adls.compatibility")}:</label>
            <div class="controls compatibility">

                {foreach from=$availableVersions item="version"}
                    {$checked = false}
                    {if in_array($version->getId(), $compatiblePlatformVersionIds)}
                        {$checked = true}
                    {/if}
                    <label class="checkbox compatibility-version">
                        <input type="checkbox" value="{$version->getId()}" name="compatibility[]" {if $checked}checked="checked"{/if} class="list-item-checkbox">
                        {$version->getExtra('platform$name')}
                        {$version->getExtra('edition$name')}
                        {$version->getVersion()}
                        ({$version->getReleaseDate()->format('Y-m-d')})
                    </label>
                {/foreach}

            </div>
        </div>


        {include file="buttons/button.tpl" but_text=$submitButtonText but_role="submit" but_name="dispatch[releases.update]"}

        <a href="{"releases.manage?id=`$product.adls_addon_id`"|fn_url}">{__('go_back')}</a>
    </form>
{/capture}
{include file="common/mainbox.tpl" title=$title content=$smarty.capture.mainbox}