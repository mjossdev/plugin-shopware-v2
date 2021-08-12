{extends file='parent:frontend/index/index.tpl'}
{block name="frontend_index_before_page" append}{$report_script}{/block}

{block name="frontend_index_header_meta_tags" append}
    <meta name="bx_debug" content="{$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}" />
{/block}

{block name="frontend_index_header_javascript_jquery_lib"}
    {$smarty.block.parent}
    {if $bxApiConfig}
        {if $bxApiConfig['ac']}
            <div data-bx-api-ac="true"></div>
        {/if}
        <script>
            window.rtuxAutocomplete = {
                "apiPreferentialAccount": "{$bxApiConfig['apiAccountJs']}",
                "apiPreferentialKey": "{$bxApiConfig['apiKeyJs']}",
                "dev": '{$bxApiConfig["dev"]}'=='1' ? true : false,
                "test": '{$bxApiConfig["test"]}'=='1' ? true : false,
                "widget": '{$bxApiConfig["widget"]}',
                "suggestionsCount":{$bxApiConfig['suggestionsCount']},
                "productsCount":{$bxApiConfig['productsCount']},
                "language": "{$Shop->getLocale()->toString()}",
                "filterById": '{$Shop->getId()}',
                "suggestLink": "{url controller="search"}?sSearch=",
                "currency" : '{$Shop->getCurrency()->getCurrency()}',
                "noSearchResultsMessage": '{s namespace="frontend/search/ajax" name="SearchAjaxNoResults"}{/s}',
                "seeAllSearchResultsMessage": '{s namespace="frontend/search/ajax" name="SearchAjaxLinkAllResults"}{/s}',
                "seeAllSearchResultLabel" : ' {s namespace="frontend/search/ajax" name="SearchAjaxInfoResults"}{/s}'
            };
        </script>
    {/if}

    <script>
        document.asyncReady(function() {
            $(document).ready(function () {
                $.subscribe('plugin/swProductSlider/onLoadItemsSuccess', function(e,o,r) {
                    if(r.length === 0) {
                        if ($(o.$el).closest('.emotion--element').length > 0) {
                            $(o.$el).closest('.emotion--element').remove();
                        } else {
                            $(o.$el).parent().parent().remove();
                        }
                    }
                    var replace = $(o.$el).find('.bx_replace');
                    if(replace.length > 0) {
                        $(o.$el).prev('.panel--title.is--underline.product-slider--title').text(replace.text());
                        replace.remove();
                    }
                    var bxAttributes = $(o.$el).find('.bx_attributes');
                    if(bxAttributes.length > 0) {
                        var el = $(o.$el).prev('.product-slider--container').prevObject[0];
                        el.classList.add("bx-narrative");
                        el.setAttribute("data-bx-variant-uuid", bxAttributes[0].dataset['bxVariantUuid']);
                        el.setAttribute("data-bx-narative-group-by", bxAttributes[0].dataset['bxNarrativeGroupBy']);
                        bxAttributes.remove();
                    }
                });
            });
        });
    </script>
{/block}


