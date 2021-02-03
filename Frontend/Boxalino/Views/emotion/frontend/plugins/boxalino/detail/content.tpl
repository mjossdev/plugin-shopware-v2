{extends name="parent:frontend/detail/content.tpl"}

{block name='frontend_index_content_inner'}
    {$smarty.block.parent}
    {block name='detail_blog_recommendation'}
        {if $sBlogArticles}
            <div class="bx-detail-blog bx-narrative" style="background-color: #ffffff" data-bx-narrative-group-by="{$sBlogArticlesTracking.bx_request_groupby}" data-bx-narrative-name="blog-list" data-bx-variant-uuid=""{$sBlogArticlesTracking.bx_request_uuid}"">
                <div class="bx-detail-blog--title">
                    <h2 style="text-align:center;">{$sBlogTitle}</h2>
                </div>
                    {include file="frontend/_includes/product_slider.tpl" productBoxLayout="emotion" fixedImage=true articles=$sBlogArticles bxBlogRecommendation=true}
            </div>
        {/if}
    {/block}
{/block}