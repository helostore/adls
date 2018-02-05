{include file="common/pagination.tpl"}

<h3>{$platform->getName()}</h3>
{if $products}
    <table class="table adls-table">
        <thead>
        <tr>
            <th rowspan="2" width="20%">{__("product")}</th>

            <th colspan="2">Latest development build</th>
            <th colspan="3">Latest release</th>

            <th rowspan="2">{__("action")}</th>
            <th rowspan="2">{__("notes")}</th>
        </tr>
        <tr>
            <th>{__("adls.version")}</th>
            <th>{__("adls.date")}</th>
            <th>{__("adls.version")}</th>
            <th>{__("adls.date")}</th>
            <th>{__("adls.commits")}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$products item="product"}
            {$class = ""}
            {if $product.has_unreleased_version}
                {$class = "`$class` has_unreleased_version"}
            {/if}

            <tr class="{$class}">
                <td>
                    {if !empty($product.product_id)}
                        <a href="{"products.update?product_id=`$product.product_id`"|fn_url}" target="_blank">{$product['productDesc$name']}</a>
                    {else}
                        {$product.name}
                    {/if}
                </td>


                {* Development Info *}
                <td>
                    {if !empty($product.latestBuild)}
                        {$product.latestBuild.version}
                    {else}
                        &mdash;
                    {/if}
                </td>
                <td>
                    {if !empty($product.latestBuild)}
                        {$product.latestBuild.date->format('d.m.Y')}
                    {else}
                        &mdash;
                    {/if}
                </td>


                {* Release Info *}
                <td>
                    {if !empty($product.latestRelease)}
                        {$product.latestRelease->getVersion()}
                    {else}
                        &mdash;
                    {/if}
                </td>

                <td>
                    {if !empty($product.latestRelease)}
                        {$product.latestRelease->getCreatedAt()->format('d.m.Y')}
                    {else}
                        &mdash;
                    {/if}
                </td>



                <td>
{*                    {if !empty($product.lastRelease.commits)}
                        {capture name="commits"}
                            {"<br>"|implode:$product.lastRelease.commits nofilter}
                        {/capture}
                        {include
                        file="common/popupbox.tpl"
                        id="product_commits_`$product.product_id`"
                        text=__("adls.commits")
                        act="link"
                        link_text=__("adls.view_commits", ["[count]" => $product.lastRelease.commits|count])
                        content=$smarty.capture.commits
                        no_icon_link=true
                        }

                    {else}
                        &dash;
                    {/if}*}
                </td>


                <td>
                    {include
                    file="buttons/button.tpl"
                    but_role="action"
                    but_text=__("adls.release_now")
                    but_href=fn_url("addons.pack?addon=`$product.adls_slug`")
                    but_meta=""}
                    {if !empty($product.latestRelease)}
                        {include
                        file="buttons/button.tpl"
                        but_role="action"
                        but_text=__("adls.release.publish")
                        but_href=fn_url("releases.publish?release_id=`$product.latestRelease->getId()`")
                        but_meta=""}
                    {/if}
                    {include
                    file="buttons/button.tpl"
                    but_role="action"
                    but_text="Manage"
                    but_href=fn_url("releases.manage?productId=`$product.product_id`&platformId=`$platform->getId()`")
                    but_meta=""}
                </td>
                <td>
                    {if $product.has_unreleased_version === true}
                        Contains unreleased versions
                    {/if}
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}