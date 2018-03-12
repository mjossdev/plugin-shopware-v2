{extends file='parent:frontend/listing/product-box/box-basic.tpl'}


{block name='frontend_listing_box_article_price_info'}
    {if $isFinder == 'true'}
        <h2 class="bxScore" style="text-align:center;">score: {$sArticle.bx_score}</h2>
        <button class="bxCommentButton_{$sArticle.articleID}">comment</button>
        <div class="bxComment_{$sArticle.articleID}" style="display:none">{$sArticle.comment}</div>
    {/if}
    {$smarty.block.parent}
{/block}

