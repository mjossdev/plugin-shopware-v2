{extends name="parent:frontend/_includes/product_slider_item.tpl"}

{block name="frontend_common_product_slider_item"}
   {if $bxBlogRecommendation}
       <div class="product-slider--item bxBlogRecommendationSlider">
           {include file="frontend/plugins/boxalino/blog/recommendation.tpl" sArticle=$article}
       </div>
   {else}
       {include file="frontend/listing/box_article.tpl" sArticle=$article productBoxLayout=$productBoxLayout fixedImageSize=$fixedImageSize}
   {/if}
{/block}