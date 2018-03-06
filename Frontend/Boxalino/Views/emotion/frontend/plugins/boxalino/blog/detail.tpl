{extends name="parent:frontend/blog/detail.tpl"}

{block name='frontend_blog_detail_crossselling'}
    {$smarty.block.parent}
    {if $bxProductRecommendation}
        <div class="bx-blog-product-rec">
            <div class="bx-blog-product-rec--title">
                <h2 style="text-align:center;">{$bxRecTitle}</h2>
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