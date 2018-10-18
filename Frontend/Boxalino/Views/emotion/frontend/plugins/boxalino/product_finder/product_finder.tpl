{block name="frontend_product_finder_content"}
    <div class="cpo-finder-wrapper">

        <div class="cpo-finder-left">

            <div class="cpo-finder-left-content">

            </div>

        </div>

        <div class="cpo-finder-center">

            <div class="cpo-finder-center-content">

                <div class="cpo-finder-center-content-header">

                </div>

                <div class="cpo-finder-center-content-header-question cpo-finder-center-content-header-question-first">

                </div>

                <div class="cpo-finder-center-content-container">

                </div>

                <div class="cpo-finder-center-current-question-options">

                </div>

                <div class="cpo-finder-center-content-header-question cpo-finder-center-content-header-question-second">

                </div>

                <div class="cpo-finder-center-current-question-options-second">

                </div>

                <div class="cpo-finder-center-show-more-less">

                </div>

            </div>

            <div class="cpo-finder-button-container">

            </div>

            <div class="listingBlock cpo-finder-listing-container">

                <div class="cpo-finder-listing-wrapper">
                    {block name="frontend_cpo_finder_listing_present"}
                        <div class="cpo-finder-listing bx-present">
                            {foreach $Data.highlighted_articles as $sArticle}

                                {include file="frontend/detail/content/header.tpl"}

                                <div class="product--detail-upper block-group">
                                    {* Product image *}
                                    {block name='frontend_detail_index_image_container'}
                                        <div class="product--image-container image-slider{if $sArticle.image && {config name=sUSEZOOMPLUS}} product--image-zoom{/if}"
                                                {if $sArticle.image}
                                            data-image-slider="true"
                                            data-image-gallery="true"
                                            data-maxZoom="{$theme.lightboxZoomFactor}"
                                            data-thumbnails=".image--thumbnails"
                                                {/if}>
                                                <h2 class="cpo-finder-listing-score bxScore" style="text-align:center;">Score: {$sArticle.bx_score}</h2>
                                                {if !empty($sArticle.comment)}
                                                <button class="cpo-finder-listing-comment-button bxCommentButton_{$sArticle.articleID}">comment</button>
                                                <div class="cpo-finder-listing-comment" style="display:none">
                                                  <div class="cpo-finder-listing-comment-text bxComment_{$sArticle.articleID}" style="">{$sArticle.comment}</div>
                                                    {if !empty({$sArticle.description})}
                                                    <div class="cpo-finder-listing-comment-description bxComment_{$sArticle.articleID}" style="">{$sArticle.description}</div>
                                                    {/if}
                                                </div>
                                                {/if}
                                            {include file="frontend/detail/image.tpl"}
                                        </div>
                                    {/block}

                                </div>

                                {* "Buy now" box container *}
                                {include file="frontend/detail/content/buy_container.tpl" Shop = $Data.shop}
                            {/foreach}
                        </div>
                    {/block}
                    {block name="frontend_cpo_finder_listing_listing"}
                        {* {if $Data.highlighted_articles} *}
                        <div class="cpo-finder-listing bx-listing-emotion">
                            {foreach $Data.sArticles as $sArticle}
                                {include file="frontend/listing/box_article.tpl" productBoxLayout='image' isFinder='true'}
                            {/foreach}
                        </div>
                        {* {/if} *}
                    {/block}
                </div>

            </div>

        </div>

        <div class="cpo-finder-right">

            <div class="cpo-finder-right-content">

                <div class="cpo-finder-right-title">{s namespace='boxalino/intelligence' name='productfinder/yourchoice'}Your choice{/s}</div>

                <div class="cpo-finder-right-criteria">

                </div>

            </div>

        </div>

        <div class="cpo-finder-button-container-below">

        </div>

    </div>

{/block}

{block name="frontend_product_finder_script"}

    <script>

        var expertHtml ='<div class="cpo-finder-expert-img">' +
            '<img src="https://%%ExpertQuestionImage%%" />' +
            '</div>' +
            '<div class="cpo-finder-expert-text">' +
            '<div class="cpo-finder-expert-name">' +
            '<h4>%%ExpertFirstName%% %%ExpertLastName%%</h4>' +
            '</div>' +
            '<div class="cpo-finder-expert-persona">' +
            '<p>%%ExpertPersona%%</p>' +
            '</div>' +
            '</div>';


        var expertListHtml ='<div class="cpo-finder-expert" id="%%ExpertFirstName%%%%ExpertLastName%%_button">' +
            '<div class="cpo-finder-expert-img-list">' +
            '<img src="https://%%ExpertSelectionImage%%">' +
            '</div>' +
            '<div class="cpo-finder-expert-name">' +
            '<h4>%%ExpertFirstName%% %%ExpertLastName%%</h4>' +
            '</div>' +
            '<div class="cpo-finder-expert-characteristics">' +
            '<ul>' +
            '<li>' +
            '%%Characteristics0%%: %%Characteristics0Value%%' +
            '</li>'+
            '<li>' +
            '%%Characteristics1%%: %%Characteristics1Value%%' +
            '</li>'+
            '<li>' +
            '%%Characteristics2%%: %%Characteristics2Value%%' +
            '</li>' +
            '</ul>' +
            '<div class="cpo-finder-expert-button">' +
            '<button type="button" name="button">ausw&auml;hlen</button>' +
            '</div>' +
            '</div>';

        var expertLeftHtml ='<div class="cpo-finder-expert-img">' +
            '<img src="https://%%ExpertQuestionImage%%" />' +
            '</div>' +
            '<div class="cpo-finder-expert-text">'+
            '<div class="cpo-finder-expert-name">' +
            '<h4>%%ExpertFirstName%% %%ExpertLastName%%</h4>' +
            '</div>' +
            '<div class="cpo-finder-expert-extra">' +
            '<div class="cpo-finder-expert-persona">' +
            '<p>%%ExpertPersona%%</p>' +
            '</div>' +
            '<div class="cpo-finder-expert-expertise">' +
            '<div class="cpo-finder-expert-expertise-description">' +
            '<p>Expert in:</p>'+
            '<p>%%ExpertExpertise%%</p>'+
            '</div>' +
            '<div class="cpo-finder-expert-characteristics">' +
            '<ul>' +
            '<li>' +
            '%%Characteristics0%%: %%Characteristics0Value%%' +
            '</li>'+
            '<li>'+
            '%%Characteristics1%%: %%Characteristics1Value%%' +
            '</li>'+
            '<li>'+
            '%%Characteristics2%%: %%Characteristics2Value%%' +
            '</li>'+
            '</li>'+
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>';

        var checkboxImageWithLabel ='<div class="cpo-finder-answer">' +
            '<img class="cpo-finder-answer-image-with-label" src="https://%%AnswerImage%%">' +
            '<div class="cpo-finder-answer-text">' +
            '<input class="%%FieldValue%%_check cpo-finder-answer-multiselect" type="checkbox" name="%%AnswerCheckboxName%%" value="%%AnswerCheckboxValue%%" %%AnswerCheckboxChecked%%> <div></div><span>%%AnswerText%%</span>' +
            '</div>' +
            '</div>';

        var checkboxLabelWithoutImage = '<div class="cpo-finder-answer">' +
            '<div class="cpo-finder-answer-text">'+
            '<input class="%%FieldValue%%_check cpo-finder-answer-multiselect" type="checkbox"name="%%AnswerCheckboxName%%" value="%%AnswerCheckboxValue%%" %%AnswerCheckboxChecked%%> <div></div><span>%%AnswerText%%</span>' +
            '</div>' +
            '</div>';

        var radioImageWithLabel =   '<div class="cpo-finder-answer">' +
            '<img class="cpo-finder-answer-image-with-label" src="https://%%AnswerImage%%">' +
            '<div class="cpo-finder-answer-text">' +
            '<input class="%%FieldValue%%_check cpo-finder-answer-radio" type="radio"name="%%AnswerCheckboxName%%" value="%%AnswerCheckboxValue%%" %%AnswerCheckboxChecked%%> <div></div><span>%%AnswerText%%</span>' +
            '</div>' +
            '</div>';

        var radioLabelWithoutImage =   '<div class="cpo-finder-answer">' +
            '<div class="cpo-finder-answer-text">' +
            '<input class="%%FieldValue%%_check cpo-finder-answer-radio" type="radio"name="%%AnswerCheckboxName%%" value="%%AnswerCheckboxValue%%" %%AnswerCheckboxChecked%%> <div></div><span>%%AnswerText%%</span>' +
            '</div>' +
            '</div>';

        var additionalButton = '<button id="cpo-finder-additional" type="button" name="additionalButton">{s namespace='boxalino/intelligence' name='filter/morevalues'}more values{/s}</button>';

        var fewerButton = '<button id="cpo-finder-fewer" style="display: none;" type="button" name="fewerButton">{s namespace='boxalino/intelligence' name='filter/lessvalues'}less values{/s}</button>';

        var backButton = '<button id="cpo-finder-back" type="button" name="backButton">{s namespace='boxalino/intelligence' name='productfinder/back'}back{/s}</button>';

        var resultsButton = '<button id="cpo-finder-results" type="button" name="resultsButton">{s namespace='boxalino/intelligence' name='productfinder/advance'}advance{/s}</button>';

        var skipButton = '<button id="cpo-finder-skip"  type="button" name="backButton">{s namespace='boxalino/intelligence' name='productfinder/skip'}skip{/s}</button>';

        var showProductsButton = '<button id="cpo-finder-show-products" style="">{s namespace='boxalino/intelligence' name='productfinder/showresultsuntil'}Show results until %%CurrentScore%% %{/s}</button>';

        {include file="frontend/plugins/boxalino/product_finder/product_finder_js.tpl" Data = $Data}

    </script>
{/block}
