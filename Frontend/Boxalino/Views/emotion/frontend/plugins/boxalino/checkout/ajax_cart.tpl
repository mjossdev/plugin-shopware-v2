{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container_inner' append}
    {if $sRecommendations}
        <div class="bx-narrative" {if $bx_request_uuid} data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-name="products-list"{/if}
                {if $bx_request_groupby} data-bx-narrative-group-by="{$bx_request_groupby}"{/if}>
            {foreach $sRecommendations as $sArticleSub}
                {include file="frontend/plugins/boxalino/checkout/article_compact.tpl" sArticle=$sArticleSub}
            {/foreach}
        </div>
    {/if}
{/block}
