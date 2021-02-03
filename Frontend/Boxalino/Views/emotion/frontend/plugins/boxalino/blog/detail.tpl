{extends name="parent:frontend/blog/detail.tpl"}

{block name='frontend_blog_detail_crossselling'}
    {$smarty.block.parent}
    {if $bxProductRecommendation}
        <div class="bx-blog-product-rec block bx-narrative" data-bx-narrative-name="products-list" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-group-by="{$bx_request_groupby}">
            <div class="bx-blog-product-rec--title">
                <h2 class="panel--title is--underline product-slider--title">{$bxRecTitle}</h2>
            </div>
            {include file="frontend/_includes/product_slider.tpl"
            articles=$bxProductRecommendation
            sliderAjaxCtrlUrl=''
            sliderAjaxCategoryID=''
            productSliderCls="product-slider--content"
            sliderMode={''}
            sliderAjaxMaxShow=$bxProductRecommendation|@count
            sliderArrowControls={'1'}
            sliderAnimationSpeed=500
            sliderAutoSlideSpeed={''}
            sliderAutoSlide={''}
            productBoxLayout="emotion"
            fixedImageSize="true"}
        </div>
    {/if}
{/block}