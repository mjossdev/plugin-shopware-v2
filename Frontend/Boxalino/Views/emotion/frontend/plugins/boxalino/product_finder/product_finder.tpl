<div class="boxalino-finder-fn" data-bx-finder="true">
    <div class="bx-finder-init-json" style="display:none">{$data.json_facets}</div>
    <div class="bx-finder-init-alert" style="display:none">"{s namespace="boxalino/intelligence" name="productfinder/alertString" default="Bitte beide Fragen beantworten"}{/s}"</div>
    <div class="bx-finder-init-highlighted" style="display:none">{$data.highlighted}</div>
    <div class="bx-finder-init-url" style="display:none">{url controller=cat sCategory=$data.cpo_finder_link}</div>
    <div class="bx-finder-init-max-score" style="display:none">{$data.max_score}</div>
    <div class="bx-finder-init-language" style="display:none">{$data.locale}</div>

    {block name="frontend_product_finder_script_templates"}
        {block name="frontend_product_finder_script_templates_experts_list"}
            <div class="bx-finder-template-expertListHtml" style="display:none">
                <div class="cpo-finder-expert cpo-finder-answer" id="%%ExpertFirstName%%%%ExpertLastName%%_button">
                    <div class="cpo-finder-expert-img-list"><img src="https://%%ExpertSelectionImage%%"></div>
                    <div class="cpo-finder-expert-name"><h4>%%ExpertFirstName%% %%ExpertLastName%%</h4></div>
                    <div class="cpo-finder-expert-characteristics">
                        <ul><li><div class="cpo-finder-expert-description">%%ExpertDescription%%</div></li></ul>
                    <div class="cpo-finder-expert-button">
                        <button type="button" name="button">{s namespace='boxalino/intelligence' name='productfinder/choose'}choose{/s}</button>
                    </div>
                </div>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_button_additional"}
            <div class="bx-finder-template-additionalButton" style="display:none">
                <button id="%%ID%%" type="button" name="additionalButton">{s namespace='boxalino/intelligence' name='filter/morevalues'}more values{/s}</button>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_button_fewer"}
            <div class="bx-finder-template-fewerButton" style="display:none">
                <button id="%%ID%%" style="display: none;" type="button" name="fewerButton">{s namespace='boxalino/intelligence' name='filter/lessvalues'}less values{/s}</button>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_button_back"}
            <div class="bx-finder-template-backButton" style="display:none">
                <button id="%%ID%%" type="button" name="backButton">{s namespace='boxalino/intelligence' name='productfinder/back'}back{/s}</button>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_button_results"}
            <div class="bx-finder-template-resultsButton" style="display:none">
                <button id="%%ID%%" type="button" name="resultsButton">{s namespace='boxalino/intelligence' name='productfinder/advance'}advance{/s}</button>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_button_skip"}
            <div class="bx-finder-template-skipButton" style="display:none">
                <button id="%%ID%%"  type="button" name="skipButton">{s namespace='boxalino/intelligence' name='productfinder/skip'}skip{/s}</button>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_button_show_products"}
            <div class="bx-finder-template-showProductsButton" style="display:none">
                <button id="%%ID%%" style="">{s namespace='boxalino/intelligence' name='productfinder/showresultsuntil'}Show results until {/s} %%CurrentScore%% %</button>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_show_products"}
            <div class="bx-finder-template-showProducts" style="display:none">
                <p style="">{s namespace='boxalino/intelligence' name='productfinder/showresultsuntil'}Your matching products{/s}</p>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_configurator_message"}
            <div class="bx-finder-template-configuratorMessage" style="display:none">
                <p class="bx-finder-configurator-notice">{s namespace='boxalino/intelligence' name='productfinder/configuratormessage'}Please go to the product page for more options{/s}</p>
            </div>
        {/block}
        {block name="frontend_product_finder_script_templates_notification"}
            <div class="bx-finder-template-notification" style="display:none">
                <p class="bx-finder-notification">
                <div class="alert is--info is--rounded">
                    <div class="alert--icon">
                        <i class="icon--element icon--check"></i>
                    </div>
                    <div class="alert--content">
                        {s namespace='boxalino/intelligence' name='productfinder/notification'}Scroll down to discover more products{/s}
                    </div>
                </div>
                </p>
            </div>
        {/block}
    {/block}

    {block name="frontend_product_finder_content"}
        <div class="cpo-finder-wrapper loaded" style="display:none">
            {block name="frontend_product_finder_content_left"}
                <div class="cpo-finder-left">
                    <div class="cpo-finder-left-content"></div>
                </div>
            {/block}

            <div class="cpo-finder-center">
                <div class="cpo-finder-center-content">
                    <div class="cpo-finder-center-content-notification"></div>
                    <div class="cpo-finder-center-content-header"></div>
                    <div class="cpo-finder-center-content-header-question cpo-finder-center-content-header-question-first"></div>
                    <div class="cpo-finder-center-content-container"></div>
                    <div class="cpo-finder-center-current-question-options"></div>
                    <div class="cpo-finder-center-content-header-question cpo-finder-center-content-header-question-second"></div>
                    <div class="cpo-finder-center-current-question-options-second"></div>
                    <div class="cpo-finder-center-show-more-less"></div>
                </div>
                <div class="cpo-finder-button-container"></div>
                <div class="listingBlock cpo-finder-listing-container bx-narrative" {if $narrative_bx_request_uuid} data-bx-variant-uuid="{$narrative_bx_request_uuid}" data-bx-narrative-name="products-list"{/if}
                        {if $narrative_bx_request_group_by} data-bx-narrative-group-by="{$narrative_bx_request_group_by}"{/if}>
                    <div class="cpo-finder-listing-wrapper">
                        {block name="frontend_cpo_finder_listing_present"}
                            <div class="cpo-finder-listing bx-present product--details" data-ajax-wishlist="true" data-compare-ajax="true">
                                {foreach $Data.highlighted_articles as $sArticle}
                                    {include file="frontend/detail/content/header.tpl" isFinder='true'}
                                    {* Variable for tracking active user variant selection *}
                                    {$activeConfiguratorSelection = true}
                                    {if $sArticle.sConfigurator && ($sArticle.sConfiguratorSettings.type == 1 || $sArticle.sConfiguratorSettings.type == 2)}
                                        {* If user has no selection in this group set it to false *}
                                        {foreach $sArticle.sConfigurator as $configuratorGroup}
                                            {if !$configuratorGroup.selected_value}
                                                {$activeConfiguratorSelection = false}
                                            {/if}
                                        {/foreach}
                                    {/if}

                                    <div class="content product--details product--detail-upper block-group bx-narrative-item" data-bx-item-id="{$sArticle.articleID}" data-ajax-wishlist="true" data-compare-ajax="true"{if $theme.ajaxVariantSwitch} data-ajax-bx-product-finder={$sArticle.articleID}{/if}>
                                        {* Product image *}
                                        {block name='frontend_detail_index_image_container'}
                                            <div class="product--image-container">
                                                <span class="cpo-finder-listing-score">{s namespace="boxalino/intelligence" name="productfinder/score"}{/s} {$sArticle.bx_score}%</span>
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

                                                {include file="frontend/listing/product-box/product-image.tpl" isFinder='true'}
                                            </div>
                                        {/block}

                                        {* "Buy now" box container *}
                                        {include file="frontend/detail/content/buy_container.tpl" Shop = $Data.shop isFinder='true'}
                                        {block name='frontend_detail_actions'}{/block}
                                    </div>
                                {/foreach}
                                {block name="frontend_cpo_finder_listing_present_after"}{/block}
                            </div>
                        {/block}
                        {block name="frontend_cpo_finder_listing_listing"}
                            {* {if $Data.highlighted_articles} *}
                            <div class="cpo-finder-listing bx-listing-emotion">
                                {foreach $Data.sArticles as $sArticle}
                                    <div class="bx-narrative-item" data-bx-item-id="{$sArticle.articleID}">
                                        {include file="frontend/listing/box_article.tpl" productBoxLayout='image' isFinder='true'}
                                    </div>
                                {/foreach}
                            </div>
                            {* {/if} *}
                        {/block}
                    </div>
                </div>
            </div>

            <div class="cpo-finder-right">
                {block name="frontend_product_finder_content_right_main"}
                    <div class="cpo-finder-right-content">
                        <div class="cpo-finder-right-title">{s namespace='boxalino/intelligence' name='productfinder/yourchoice'}Your choice{/s}</div>
                        <div class="cpo-finder-right-criteria"></div>
                        {block name="frontend_product_finder_content_right"}{/block}
                    </div>
                {/block}
            </div>

            {block name="frontend_product_finder_content_below"}
                <div class="cpo-finder-button-container-below"></div>
            {/block}
        </div>
    {/block}
    {block name="frontend_product_finder_fallback"}
        <div class="cpo-finder-wrapper-fallback" style="display:none">
            {block name="frontend_product_finder_script_templates_fallback"}
                <div class="bx-finder-template-fallback">
                    <p class="bx-finder-fallback-header">{s namespace='boxalino/intelligence' name='productfinder/fallbackheader'}Apologies, we are having troubles displaying your content{/s}</p>
                    <p class="bx-finder-fallback">{s namespace='boxalino/intelligence' name='productfinder/fallback'}To continue, reload or go to another page. If you are seeing this frequently, contact us.{/s}</p>
                </div>
            {/block}
        </div>
    {/block}
</div>