{extends name="parent:frontend/_includes/product_slider_item.tpl"}

{block name="frontend_common_product_slider_item"}
   {if $bxBlogRecommendation}
       <div class="product-slider--item bxBlogRecommendationSlider {if $bx_request_uuid}bx-narrative" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-group-by="{$bx_request_groupby}"{else}"{/if}>
           {include file="frontend/plugins/boxalino/blog/recommendation.tpl" sArticle=$article}
       </div>
   {else}
       <div class="product-slider--item bxRecommendationSlider {if $bx_request_uuid}bx-narrative" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-group-by="{$bx_request_groupby}"{else}"{/if}>
           {include file="frontend/listing/box_article.tpl" sArticle=$article productBoxLayout=$productBoxLayout fixedImageSize=$fixedImageSize}
       </div>
   {/if}
{/block}