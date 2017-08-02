{block name="frontend_quick_search_header"}
    <h3>Brauchen Sie Hilfe das passende Produkt zu finden? In nur paar Schritten das Richtige finden.</h3>
{/block}
{block name="frontend_quick_search_content"}
    <div class="wrapper">
        <div class="quick-search-wrapper">
            <div id="quick-search">

            </div>
            <div class="button-wrapper">
            </div>
        </div>
    </div>
{/block}
{block name="frontend_quick_search_bottom"}
{/block}
{block name="frontend_quick_search_script"}
<script>
    {block name="frontend_quick_search_script_init"}
        var json = {$Data.json_facets},
            lang  = '{$Data.locale}',
            facets = new bxFacets(),
            selectedValues = {};
        facets.init(json);
    {/block}
    {block name="frontend_quick_search_script_create_fields"}
        createFields();
        createButton();

        function createFields() {
            var facetNames = facets.getQuickSearchFacets(),
                container = $('<div class="quick-search-container"></div>');
            facetNames.forEach(function(facetName, i) {
                var display = i > 0 ? 'none' : '';
                var fieldContainer = $('<div class="field-container" style="display:'+display+'"></div>'),
                    facetValues = facets.getFacetValues(facetName)[facetName],
                    visualisation = facets.getFacetExtraInfo(facetName, 'visualisation');
                fieldContainer.append(createField(facetName, visualisation, facetValues));
                container.append(fieldContainer);
            });
            $('#quick-search').append(container);
            facetNames.forEach(function(fieldName) {
                createFieldListener(fieldName);
            });
        }

        function createField(field, elementType, values) {
            var element = null,
                facetLabel = facets.getFacetExtraInfo(field, 'finderQuestion');
            switch(elementType) {
                case 'checkbox':
                    element = $('<div><div>');
                    values.forEach(function(value) {
                        var icon = facets.getFacetValueIcon(field, value);
                        var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                        element.append( $("<span>"+value+"</span><div class='"+icon+"'></div>" +
                            "<input class='"+field+"' "+checked+" type='checkbox' name='"+value+"' value='"+value+"'>"));
                    });
                    break;
                case 'dropdown':
                    element = $('<select class='+field+' name='+field+'></select>'),
                        selectedValue = selectedValues.hasOwnProperty(field) ? selectedValues[field] : null;
                    values.forEach(function(value) {
                        var optionElement = $('<option></option>').attr("value", value).text(value);
                        if(facets.isFacetValueSelected(field, value)) {
                            optionElement.attr('selected', 'selected');
                        }
                        element.append(optionElement);
                    });
                    element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
                    break;
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
                var show = false;
                $('.quick-search-container').find('.field-container').each(function (i,e) {
                    if(show === false && $(e).is(":visible") === false){
                        show = true;
                        $(e).show();
                    }
                })
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
            $('.button-wrapper').append($('<button id="b-find">FIND!</button>'));
            $('#b-find').on('click', function (e) {
                var urlString = '{url controller=cat sCategory=$Data.cpo_finder_link}',
                    params = facets.getFacetParameters();
                params.forEach(function(param, index) {
                    if(index > 0) {
                        urlString += '&';
                    } else {
                        urlString += '?'
                    }
                    if(param.indexOf('=') === -1){
                        urlString += param + '=100';
                    }
                });
                urlString = urlString.lastIndexOf('&') > -1 ? urlString + "&bx_cat=" + {$Data.category_id} : urlString + "?bx_cat=" + {$Data.category_id};
                window.location = urlString;
            });
        }
    {/block}
</script>
{/block}

