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
                    <label class="compatibility-version">
                        <input type="checkbox" value="{$version->getId()}" name="compatibility[]" {if $checked}checked="checked"{/if}>
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