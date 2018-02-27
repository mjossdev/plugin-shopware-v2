<div class="bx-portfolio-wrapper">
    {block name="anchor_links"}
        <div class="bx-portfolio-anchor">
            <ol>
                {foreach $Data.portfolio as $portfolio}
                    <li class="bx_anchorlink-{$portfolio.name[$Data.lang]}">
                        <a title="{$portfolio.name[$Data.lang]}" href="#{$portfolio.name[$Data.lang]}_title" >{$portfolio.name[$Data.lang]}</a>
                    </li>
                {/foreach}
            </ol>
        </div>
    {/block}
    {block name="content"}
        <div class="bx-portfolio-content">
            {foreach $Data.portfolio as $portfolio}
                <div id="bx-portfolio-{$portfolio.name[$Data.lang]}">
                    <span id="{$portfolio.name[$Data.lang]}_title" style="display:block; height:85px; margin-top: -85px; visibility: hidden"></span>
                    <div id="{$portfolio.name[$Data.lang]}" class="ph-listing-content-top">
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
                                <div class="bx-rebuy-title">
                                    <span>{$portfolio.rebuy.title.{$Data.lang}}</span>
                                </div>
                                <div class="bx-re-buy emotion--product-slider panel" >
                                    {include file="frontend/_includes/product_slider.tpl"
                                    sliderAjaxCtrlUrl={url controller=RecommendationSlider action=portfoliorecommendation bxChoiceId=rebuy bxCount=10 category_id=$portfolio.context_parameter['category_id'] account_id=$portfolio.account_id}
                                    sliderAjaxCategoryID={'rebuy-'|cat:$portfolio.name[$Data.lang]}
                                    productSliderCls="product-slider--content"
                                    sliderMode={'ajax'}
                                    sliderArrowControls={''}
                                    sliderAnimationSpeed=500
                                    sliderAutoSlideSpeed={''}
                                    sliderAutoSlide={''}
                                    productBoxLayout="emotion"
                                    fixedImageSize="true"
                                    withAddToBasket="true"}
                                </div>
                            </div>
                            <div class="bx-slider-content">
                                <div class="bx-newbuy-title">
                                    <span>{$portfolio.rebuy["alternative_title"].{$Data.lang}}</span>
                                </div>
                                <div class="bx-re-buy emotion--product-slider panel" >
                                    {include file="frontend/_includes/product_slider.tpl"
                                    sliderAjaxCtrlUrl={url controller=RecommendationSlider action=portfoliorecommendation bxChoiceId=newbuy bxCount=10 category_id=$portfolio.context_parameter['category_id']}
                                    sliderAjaxCategoryID={'newbuy-'|cat:$portfolio.name[$Data.lang]}
                                    productSliderCls="product-slider--content"
                                    sliderMode={'ajax'}
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
                                sliderAjaxCtrlUrl={url controller=RecommendationSlider action=portfoliorecommendation bxChoiceId=reorient bxCount=10 category_id=$portfolio.context_parameter['category_id']}
                                productSliderCls="product-slider--content"
                                articles=$articles
                                sliderAjaxCategoryID={'reorient-'|cat:$portfolio.name[$Data.lang]}
                                sliderMode={'ajax'}
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
                        <div class="bx-blog-rec">
                            <div class="bx-reorient-title">
                                <span>Passende Blog-Beiträge</span>
                            </div>
                            <div class="bx-blog emotion--product-slider panel" >
                                {include file="frontend/_includes/product_slider.tpl"
                                sliderAjaxCtrlUrl={url controller=RecommendationSlider action=blogrecommendation category_id=$portfolio.context_parameter['category_id']}
                                productSliderCls="product-slider--content"
                                sliderAjaxCategoryID={'blog-'|cat:$portfolio.name[$Data.lang]}
                                sliderMode={'ajax'}
                                sliderArrowControls={''}
                                sliderAnimationSpeed=500
                                sliderAutoSlideSpeed={''}
                                sliderAutoSlide={''}
                                productBoxLayout="emotion"
                                fixedImageSize="true"}
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <br>
            {/foreach}
        </div>

        <script>

            document.asyncReady(function() {
                StateManager.updatePlugin('*[data-add-article="true"]','swAddArticle');
                var groupSize = parseInt('{$Data.portfolio|count}');
                console.log(groupSize);
                var loadedRec = [];
                $(document).ready(function() {

                    $.subscribe('plugin/swProductSlider/onLoadItemsSuccess', function (m,r,i) {
                        var el = r.$el;
                        var v = r.opts.ajaxCategoryID.split("-");
                        var type = v[0];
                        if(type === 'rebuy'){
                            var count = r.itemsCount;
                            if(count === 0) {
                                console.log("destory rec", v[1]);
                                r.destroy();
                                var parent = $(el).parent();
                                parent.prev().remove(); //remove rebuy title
                                parent.remove(); //remove rebuy content
                            }
                            //remove newbuy rec
                            $(el).parent().parent().next().remove();

                            var identifier = v[1];
                            {literal}
                            loadedRec.push({id:identifier, count:count, elementID: '#bx-portfolio-' + identifier});
                            {/literal}
                            loadedRec.sort(function(a,b) {
                                return parseInt(b.count) - parseInt(a.count);
                            });

                            if(loadedRec.length === groupSize) {
                                console.log(loadedRec);
                                var l = loadedRec.length;
                                for(var x = 0; x < l; x++) {
                                    if(x + 1 < l) {
                                        var before = loadedRec[x].elementID;
                                        var after = loadedRec[x + 1].elementID;
                                        console.log("before", before, "after", after);
                                        $(before).insertBefore(after);

                                        var anchorBefore = '.bx_anchorlink-' + loadedRec[x].id;
                                        var anchorAfter = '.bx_anchorlink-' + loadedRec[x + 1].id;
                                        $(anchorBefore).insertBefore(anchorAfter);
                                    }

                                }
                                $('.bx-portfolio-wrapper').show();
                            }
                        }

                    });
                });
            });
        </script>
    {/block}
</div>
