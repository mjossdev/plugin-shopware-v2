{extends file='parent:frontend/checkout/cart.tpl'}

{block name="frontend_checkout_cart_premium" prepend}
    <div class="bx-cart-recommendation bx-narrative" {if $bx_request_uuid} data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-name="products-list"{/if}
            {if $bx_request_groupby} data-bx-narrative-group-by="{$bx_request_groupby}"{/if}>
        {include file="frontend/_includes/product_slider.tpl"
        Data=$Bx
        articles=$sRecommendations
        sliderMode={''}
        sliderArrowControls={''}
        sliderAnimationSpeed=500
        sliderAutoSlideSpeed={''}
        sliderAutoSlide={''}
        productBoxLayout="emotion"
        fixedImageSize="true"}
    </div>
{/block}