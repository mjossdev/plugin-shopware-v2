{extends name="parent:frontend/detail/content.tpl"}

{block name='frontend_index_content_inner'}
    {$smarty.block.parent}
    {block name='detail_blog_recommendation'}
        {if $sBlogArticles}
            <h2>Blog Recommendation</h2>
            {include file="frontend/_includes/product_slider.tpl" productBoxLayout="emotion" fixedImage=true articles=$sBlogArticles bxBlogRecommendation=true}
        {/if}
    {/block}
{/block}