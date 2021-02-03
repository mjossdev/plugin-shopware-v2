{extends file='parent:frontend/listing/product-box/box-emotion.tpl'}

{block name="frontend_listing_box_article_picture"}
    {if $sArticle.bxTransactionDate}
        <div class="bx-transaction-date">
            <span style="font-size: .9em;">{$sArticle.bxTransactionDate}</span>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
{block name='frontend_listing_box_article_price_info'}
    {if $isFinder == 'true'}
        <span class="cpo-finder-listing-score">{s namespace="boxalino/intelligence" name="productfinder/score"}{/s}{$sArticle.bx_score}%</span>
        <progress class="cpo-finder-listing-score-progress" value="{$sArticle.bx_score}" max="100"></progress>
        {if !empty($sArticle.comment)}
            <button class="cpo-finder-listing-comment-button bxCommentButton_{$sArticle.articleID}" articleid="{$sArticle.articleID}">{s namespace="boxalino/intelligence" name="productfinder/commenticon"}{/s}</button>
            <div class="cpo-finder-listing-comment cpo-finder-listing-comment-{$sArticle.articleID}" style="display:none">
                <div class="cpo-finder-listing-comment-text bxComment_{$sArticle.articleID}" style="">{$sArticle.comment}</div>
                {if !empty({$sArticle.description})}
                    <div class="cpo-finder-listing-comment-description bxComment_{$sArticle.articleID}" style="">{$sArticle.description}</div>
                {/if}
            </div>
        {/if}
    {/if}

    {$smarty.block.parent}
    {if $withAddToBasket == 'true'}
        <div class="bx-narrative-item" data-bx-item-id="{$sArticle.ordernumber}">
            <form name="sAddToBasket{$sArticle.ordernumber}" method="post" action="{url controller=checkout action=addArticle}" class="buybox--form" data-add-article="true" data-eventName="submit" {if $theme.offcanvasCart} data-showModal="false" data-addArticleUrl="{url controller=checkout action=ajaxAddArticleCart}"{/if}>
                <input type="hidden" name="sAdd"  value="{$sArticle.ordernumber}"/>
                <div class="buybox--button-container block-group">
                    <div class="buybox--quantity block bx-basket-quantity">
                        {$maxQuantity=$sArticle.maxpurchase+1}
                        {if $sArticle.laststock && $sArticle.instock < $sArticle.maxpurchase}
                            {$maxQuantity=$sArticle.instock+1}
                        {/if}
                        <div class="select-field">
                            <select id="sQuantity" name="sQuantity" class="quantity--select">
                                {section name="i" start=$sArticle.minpurchase loop=$maxQuantity step=$sArticle.purchasesteps}
                                    <option value="{$smarty.section.i.index}">{$smarty.section.i.index}{if $sArticle.packunit} {$sArticle.packunit}{/if}</option>
                                {/section}
                            </select>
                        </div>
                    </div>
                    <button class="buybox--button block btn is--primary is--icon-right is--center is--large bx-basket-add" name="{s name="DetailBuyActionAdd"}{/s}"{if $buy_box_display} style="{$buy_box_display}"{/if}>
                        <span class="bb-btn-text">In den Warenkorb</span>
                    </button>
                </div>
            </form>
        </div>
    {/if}
{/block}

{if $isFinder == 'true'}
    {block name="frontend_listing_product_box_button_detail_container"}
    {/block}

    {block name="frontend_listing_box_article_actions_compare"}
    {/block}
{/if}
