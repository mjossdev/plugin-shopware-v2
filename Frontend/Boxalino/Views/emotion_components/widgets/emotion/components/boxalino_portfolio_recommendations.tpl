{block name="anchor_links"}
    <div class="bx-portfolio-anchor">
        <ol>
            {foreach $Data.portfolio as $portfolio}
                <li>
                    <a title="{$portfolio.name}" href="#{$portfolio.name}_title" >{$portfolio.name}</a>
                </li>
            {/foreach}
        </ol>
    </div>
{/block}
{block name="content"}
    {foreach $Data.portfolio as $portfolio}
        <span id="{$portfolio.name}_title" style="display:block; height:85px; margin-top: -85px; visibility: hidden"></span>
        <div id="{$portfolio.name}" class="ph-listing-content-top">
            <h1 class="bx-portfolio-title panel--title is--underline">
                <div>{$portfolio.title.{$Data.lang}}</div>
            </h1>
        </div>
        <div class="bx-portfolio-content">
            <div class="top">
				<span class="image--media">
					<img srcset="{$portfolio.picture.{$Data.lang}}">
				</span>
                <div class="bx-slider-content">
                    <div class="bx-reorient-title">
                        <span>{$portfolio.rebuy.title.{$Data.lang}}</span>
                    </div>
                    <div class="bx-re-buy emotion--product-slider panel" >
                        {include file="frontend/_includes/product_slider.tpl"
                        articles=$portfolio.rebuy.sArticles
                        productSliderCls="product-slider--content"
                        sliderMode={''}
                        sliderArrowControls={''}
                        sliderAnimationSpeed=500
                        sliderAutoSlideSpeed={''}
                        sliderAutoSlide={''}
                        productBoxLayout="emotion"
                        fixedImageSize="true"
                        withAddToBasket="true"}
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div style="clear: both;"></div>
            <div class="bottom">
                <div class="bx-reorient-title">
                    <span>{$portfolio.reorient.title.{$Data.lang}}</span>
                </div>
                <div class="bx-re-orient emotion--product-slider panel" >
                    {include file="frontend/_includes/product_slider.tpl"
                    articles=$portfolio.reorient.sArticles
                    productSliderCls="product-slider--content"
                    sliderMode={''}
                    sliderArrowControls={''}
                    sliderAnimationSpeed=500
                    sliderAutoSlideSpeed={''}
                    sliderAutoSlide={''}
                    productBoxLayout="emotion"
                    fixedImageSize="true"
                    withAddToBasket="true"}
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
        <br>
        <br>
    {/foreach}
    <script>
        document.asyncReady(function() {
            StateManager.updatePlugin('*[data-add-article="true"]','swAddArticle');
        });
    </script>
{/block}