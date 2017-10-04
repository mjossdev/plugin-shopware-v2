{block name="frontend_quick_search_content"}
     <div class="cpo-finder-facet-wrapper" style="border: 1px solid; padding: .5em .5em .5em .5em; height:280px; width:98%;">

         {if $Data.finderMode == 'present'}
             <h2>Wir haben genau das Richtige für Sie gefunden.</h2>
         {elseif $Data.finderMode == 'listing'}
             <h2>Auswahl an Produkten die zu Ihnen passen würde.</h2>
         {else}
             <h2>Mit der Beantwortung der Fragen unten, können wir das Richtige für Sie finden.</h2>
         {/if}
        <div class="quick-search-wrapper" style="{if $Data.highlighted_articles}display:none;{/if}">
            {*<h3>CPO Finder</h3>*}
            <div id="quick-search" style="border: 1px solid; width:100%; float:left">

            </div>
            <div id="additional" style="border: 1px solid; width:100%; float:left; height: 100px">

            </div>
            <div id="button" style="float:right; width:100%">
            </div>
        </div>
    </div>
{/block}
{block name="frontend_product_finder_listing"}
    <button id="cpo-finder-show-products">show products</button>
    <button id="show-questions">Fragen anzeigen.</button>
    <button id="show-additional-questions">Mehr Fragen anzeigen.</button>
    <div class="cpo-finder-listing-wrapper">
        {block name="frontend_cpo_finder_listing_present"}
            {if $Data.top_match}
                <div class="cpo-finder-listing bx-present">
                    {foreach $Data.top_match as $sArticle}
                        <span><strong>SCORE: 100%</strong></span>
                            {include file="frontend/listing/box_article.tpl" productBoxLayout='image'}
                    {/foreach}
                </div>
            {/if}
        {/block}
        {block name="frontend_cpo_finder_listing_listing"}
            {if $Data.highlighted_articles}
                {if $Data.top_match}
                    <div class="cpo-finder-listing bx-listing" style="display:none;">
                        {foreach $Data.highlighted_articles as $sArticle}
                            {include file="frontend/listing/box_article.tpl" productBoxLayout='emotion'}
                        {/foreach}
                    </div>
                {else}
                    <div class="cpo-finder-listing bx-listing" style="height:400px;">
                        {include file="widgets/emotion/components/component_article_slider.tpl" Data = $Data.slider_data}
                    </div>
                {/if}
            {/if}
        {/block}
        {block name="frontend_cpo_finder_listing_question"}
            <div class="cpo-finder-listing" style="display:none;width:100%;float:left">
                {foreach $Data.sArticles as $sArticle}
                    {include file="frontend/listing/box_article.tpl" productBoxLayout='minimal'}
                {/foreach}
            </div>
        {/block}
    </div>
{/block}
{block name="frontend_product_finder_script"}
    <script>
        console.log("script element");
        {block name="frontend_quick_search_script_init"}
            var json =  {$Data.json_facets},
                lang  = '{$Data.locale}',
                facets = new bxFacets(),
                selectedValues = {};
            facets.init(json);
        {/block}
        {block name="frontend_product_finder_script_create_fields"}
            createFields();
            createButton();
            update();
            test();
            window.StateManager.addPlugin('*[data-range-slider="true"]', 'swRangeSlider');
            $.subscribe('plugin/swRangeSlider/onUpdateMinInput', function(event, plugin) {
                priceSelect(plugin);
            });
            $.subscribe('plugin/swRangeSlider/onUpdateMaxInput', function(event, plugin) {
                priceSelect(plugin);
            });
            function createFields() {
                var facetNames = facets.getQuickSearchFacets(),
                    container = $('<div class="quick-search-container"></div>');
                facetNames.forEach(function(facetName) {
                    var fieldContainer = $('<div class="field-contaner"></div>'),
                        facetValues = facets.getFacetValues(facetName)[facetName],
                        visualisation = facets.getFacetExtraInfo(facetName, 'visualisation');
                    fieldContainer.append(createField(facetName, visualisation, facetValues));
                    container.append(fieldContainer);
                });
                $('#quick-search').append(container);
                facetNames.forEach(function(fieldName) {
                    createFieldListener(fieldName);
                });
                fieldNames = facets.getAdditionalFacets();
                container = $('<div class="additional-facets-container" style="display:none;"></div>');
                fieldNames.forEach(function(facetName) {
                    var fieldContainer = $('<div class="field-contaner"></div>'),
                        facetValues = facets.getFacetValues(facetName)[facetName],
                        visualisation = facets.getFacetExtraInfo(facetName, 'visualisation');
                    fieldContainer.append(createField(facetName, visualisation, facetValues));
                    container.append(fieldContainer);
                });
                $('#additional').append(container);
                var height = fieldNames.length * 85;
                $('#additional').css("height", height+"px");
                facets.getFacets().forEach(function(fieldName) {
                    createFieldListener(fieldName);
                });
            }
            function createField(field, elementType, values) {
                var element = null,
                    facetLabel = facets.getFacetExtraInfo(field, 'finderQuestion');
                switch(elementType) {
                    case 'checkbox':
                    case 'multiselect':
                        element = $('<div><div><strong>'+facetLabel+'</strong></div><div>');
                        values.forEach(function(value) {
                            var icon = facets.getFacetValueIcon(field, value, lang);
                            var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                            element.append($("<span>"+value+"</span><br><div class='"+icon+"'></div>" +
                                "<input class='"+field+"' "+checked+" type='checkbox' name='"
                                +value+"' value='"+value+"'>"));
                        });
                        break;
                    case 'dropdown':
                        element = $('<select class='+field+' name='+field+'></select>'),
                            selectedValue = selectedValues.hasOwnProperty(field) ? selectedValues[field] : null;
                        element.append($('<option value="" disabled selected >Bitte wählen Sie aus.</option>'));
                        values.forEach(function(value) {
                            var optionElement = $('<option></option>').attr("value", value).text(value);
                            if(facets.isFacetValueSelected(field, value)) {
                                optionElement.attr('selected', 'selected');
                            }
                            element.append(optionElement);
                        });
                        element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
                        break;
                    case 'slider':
                        var res = values[0].split("-"),
                            selectedValue = facets.getCurrentSelects(field) === null ? res : facets.getCurrentSelects(field)[0].split("-");
                        var activeMin = selectedValue[0],
                            activeMax = selectedValue[1];
                        element = $('<div class="range-slider" data-range-slider="true" data-roundPretty="false" data-labelFormat="" data-stepCount="100" data-stepCurve="linear" data-startMin="'+activeMin+'" data-startMax="'+activeMax+'" data-rangeMin="'+res[0]+'" data-rangeMax="'+res[1]+'"></div>');
                        element.append($('<input type="hidden" id="min" name="min" data-range-input="min" value="'+res[0]+'"/>'));
                        element.append($('<input type="hidden" id="max" name="max" data-range-input="max" value="'+res[1]+'"/>'));
                        element.append($('<div class="filter-panel--range-info"> <span class="range-info--min">{s name="ListingFilterRangeFrom" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="min" data-range-label="min"> '+activeMin+'</label><span class="range-info--max"> {s name="ListingFilterRangeTo" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="max" data-range-label="max"> '+activeMax+'</label></div>'));
                    case 'enumeration':
                        element = $('<div><div>');
                        values.forEach(function(value) {
                            var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                            element.append( $("<label>"+value+"</label><input class='"+field+"' "+checked+" type='radio' name='"+field+"' value='"+value+"'>"));

                        });
                        element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
                    default:
                        break;
                }
                return element;
            }
            function createFieldListener(field) {
                var type = facets.getFacetExtraInfo(field, 'visualisation');
                $("." + field).on('change', function() {
                    if(type == 'checkbox') {
                        if($(this).is(':checked')) {
                            facets.addSelect(field, $(this).val());
                        } else {
                            facets.removeSelect(field);
                        }
                    } else if (type == 'multiselect') {
                        if($(this).is(':selected')) {
                            facets.addSelect(field, $(this).val());
                        } else {
                            facets.removeSelect(field, $(this).val());
                        }
                    } else {
                        facets.removeSelect(field);
                        facets.addSelect(field, $(this).val());
                    }
                    update();
                });
            }
            function update() {
                var fields = facets.getUpdatedValues();
                for(var fieldName in fields) {
                    var select = $('.'+fieldName).empty();
                    var optionValues = fields[fieldName];
                    optionValues.forEach(function(optionValue) {
                        select.append($('<option></option>').attr("value", optionValue).text(optionValue));
                    });
                }
            }
            function createButton() {
                $('#button').append($('<button id="b-find">FIND!</button>'));
                $('#b-find').on('click', function (e) {
                    var urlString = '{url controller=cat sCategory=$Data.cpo_finder_link}',
                        params = facets.getFacetParameters(),
                        paramString = '',
                        prefix = facets.getParameterPrefix();
                        contextPrefix = facets.getContextParameterPrefix();
                    params.forEach(function(param, index) {
                        if(index > 0) {
                            paramString += '&';
                        } else {
                            paramString += '?'
                        }
                        if(param.indexOf('=') === -1){
                            paramString += param + '=100';
                        } else {
                            paramString += param;
                        }
                    });
                    window.location.search.substr(1).split("&").forEach(function(param, index) {
                        if(param.indexOf(prefix) !== 0 && param.indexOf(contextPrefix) !== 0) {
                            console.log("match with param");
                            bind = ((paramString === '') ? (index > 0 ? '&' : '?') : '&');
                            paramString += bind + param;
                        }
                    });
                    window.location = urlString + paramString;
                });
            }
            function test() {
                $('#cpo-finder-show-products').on('click', function (e) {
                    console.log("show products");
                    console.log(  $('.cpo-finder-listing-wrapper').find('.cpo-finder-listing'));
                    $('.cpo-finder-listing-wrapper').find('.cpo-finder-listing').each(function (i, e) {
                        $(e).show();
                    });
                });
                console.log("jquery", jQuery,  $);
                $('#show-questions').on('click', function (e) {
                    console.log("show questions");
                    $('.quick-search-wrapper').show();
                });
                $('#show-additional-questions').on('click', function (e) {
                    $('#additional').find('.additional-facets-container').each(function (i, e) {
                        $(e).show();
                    });
                });
            }
            $('#cpo-finder-show-products').on('click', function (e) {
                console.log("show products");
                console.log(  $('.cpo-finder-listing-wrapper').find('.cpo-finder-listing'));
                $('.cpo-finder-listing-wrapper').find('.cpo-finder-listing').each(function (i, e) {
                    $(e).show();
                });
            });
            console.log("jquery", jQuery,  $);
            $('#show-questions').on('click', function (e) {
                console.log("show questions");
                $('.quick-search-wrapper').show();
            });
            $('#show-additional-questions').on('click', function (e) {
                $('#additional').find('.additional-facets-container').each(function (i, e) {
                    $(e).show();
                });
            });
            function priceSelect(plugin) {
                var min = plugin.formatValue(plugin.minValue), max = plugin.formatValue(plugin.maxValue);
                facets.removeSelect('discountedPrice');
                facets.addSelect('discountedPrice', [min, max].join("-"));
            }
        {/block}
    </script>
{/block}

