{extends file='parent:frontend/index/index.tpl'}
{block name="frontend_index_header_javascript"}
    {$smarty.block.parent}
    {* from listing/facets.tpl*}
    <script>
        document.asyncReady(function() {
            $(document).ready(function() {
                var facetOptions = {$facetOptions|json_encode};
                if($('.panel--paging').length) {
                    $('.panel--paging').addClass('listing--paging');
                }
                if($('.sidebar-filter--content').length) {
                    expandFacets(facetOptions);
                }
                $.subscribe('plugin/swListingActions/onOpenFilterPanel', function() {
                    expandFacets(facetOptions);
                });

                $.subscribe('plugin/swListingActions/onGetFilterResultFinished', function(m,r,i) {
                    if(facetOptions['mode'] === 'filter_ajax_reload'){
                        setTimeout(function() {
                            $('div.filter--active-container').children().each(function(i, e) {
                                var param = $(e).attr('data-filter-param');
                                if(param !== 'reset') {
                                    var label = $('.filter--facet-container').find("label[for='"+param+"']").html();
                                    var replaceHtml = $(e).find('span').prop('outerHTML') + label;
                                    $(e).html(replaceHtml);
                                }
                            });
                        }, 1);
                    }

                    var el = $('.bx-tab-article-count');
                    if(el.length){
                        el.text(i.totalCount);
                    }
                });
                $.overridePlugin('swFilterComponent',{
                    open: function(closeSiblings) {
                        var me = this;
                        me.$el.addClass(me.opts.collapseCls);
                        $.publish('plugin/swFilterComponent/onOpen', [ me ]);
                    },
                    updateValueList: function(data) {
                        var me = this, $elements, values, ids, activeIds, checkedIds;

                        $elements = me.convertToElementList(me.$inputs);
                        values = me.getValues(data, $elements);
                        values = me.convertValueIds(values);
                        ids = me.getValueIds(values);
                        activeIds = me.getActiveValueIds(values);
                        checkedIds = me.getElementValues(
                            me.getCheckedElements($elements)
                        );

                        if (me.validateComponentShouldBeDisabled(data, values, checkedIds)) {
                            me.disableAll($elements, values);
                            return;
                        }
                        $elements.each(function(index, $element) {
                            var val = $element.val() + '';
                            var value = me.findValue(val, values);
                            var disable = me.validateElementShouldBeDisabled($element, activeIds, ids, checkedIds, value);
                            me.disable($element, disable);
                            me.setDisabledClass($element.parents('.filter-panel--input'), disable);
                            if(!disable && value !== null) {
                                $element.parents('.filter-panel--input').next().text(value['label']);
                            }
                        });

                        me.disableComponent(me.allDisabled($elements));
                    }
                });
                StateManager.destroyPlugin('*[data-filter-type]','swFilterComponent');
                StateManager.updatePlugin('*[data-filter-type]','swFilterComponent');

                var snippetValues = {
                    "more": '{s namespace="boxalino/intelligence" name="filter/morevalues"}{/s}',
                    "less": '{s namespace="boxalino/intelligence" name="filter/lessvalues"}{/s}'
                };
                $(".show-more-values").on('click', function () {
                    var header = $(this);
                    var content = header.parent().find('.hidden-items');
                    content.slideToggle(500, function () {
                        header.text(function () {
                            return content.is(":visible") ? snippetValues['less'] : snippetValues['more'];
                        });
                    });
                });
                $('.search-remove').on('click', function() {
                    if($(this).hasClass('icon--cross')) {
                        var searchInput = $(this).prev();
                        if(searchInput.val() !== ''){
                            toggleSearchIcon($(this));
                        }
                        searchInput.val("");
                        $(this).parent().next().find('.show-more-values').show();
                        $(this).parent().next().find('.filter-panel--option').each(function(i, e) {
                            var label = $(e).find('label');
                            label.html(label.text());
                            if($(e).hasClass('hidden-items')){
                                $(e).hide();
                            } else {
                                $(e).show();
                            }
                        });
                    }
                });
                $(".bx--facet-search").on('keyup', function() {
                    var text = $(this).val(),
                        iconElement =  $(this).next();
                    if(text === ''){
                        iconElement.trigger('click');
                    } else {
                        var options = $(this).parent().next().find('.filter-panel--option');
                        var regMatch = new RegExp(escapeRegExp(text), 'gi'),
                            regMatch2 = new RegExp(escapeRegExp(text.slice(0, text.length-1)), 'gi');
                        $(this).parent().next().find('.show-more-values').hide();
                        options.each(function(i, e) {
                            var label = $(e).find('label').text(),
                                match = null,
                                m = label.match(regMatch),
                                m2 = label.match(/\'/g);
                            if(Array.isArray(m2)){
                                if(m){
                                    match = m;
                                }else {
                                    if(text.length > 1){
                                        var quoteMatches = [],
                                            reg =  /(\')/g;
                                        while((m2 = reg.exec(label)) !== null) {
                                            quoteMatches.push(m2);
                                        }
                                        var label2 = label.replace(reg, '');
                                        quoteMatches.forEach(function (m) {
                                            if(match === null){
                                                while((m2 = regMatch2.exec(label2)) !== null) {
                                                    if(m2.index < m.index && match === null){
                                                        match = label.slice(m2.index, m2.index + text.length+1);
                                                    }
                                                }
                                            }
                                        });
                                    }
                                }
                            } else {
                                if(m){
                                    match = m[0];
                                }
                            }
                            if(match) {
                                $(e).find('label').html(label.replace(match, '<strong>'+match+'</strong>'));
                                $(e).show();
                            } else {
                                $(e).hide();
                            }
                        });
                    }
                    if(text.length > 0 && iconElement.hasClass('icon--search')) {
                        toggleSearchIcon(iconElement);
                    } else if(text.length === 0 && iconElement.hasClass('icon--cross')) {
                        toggleSearchIcon(iconElement);
                    }
                });
                function escapeRegExp(text) {
                    text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
                    text = text.replace(/[a|\u00E0|\u00E1|\u00E2|\u00E3|\u00E4](?![^[]*\])/gi, '[a|ä|\u00E0|\u00E1|\u00E2|\u00E3|\u00E4]');
                    text = text.replace(/[(e|\u00E8|\u00E9|\u00EA|\u00EB)](?![^[]*\])/gi, '[e|é|\u00E8|\u00E9|\u00EA|\u00EB]');
                    text = text.replace(/[i|\u00EC|\u00ED|\u00EE|\u00EF](?![^[]*\])/gi, '[i|\u00EC|\u00ED|\u00EE|\u00EF]');
                    text = text.replace(/[o|\u00F2|\u00F3|\u00F4|\u00F5|\u00F6](?![^[]*\])/gi, '[o|\u00F2|\u00F3|\u00F4|\u00F5|\u00F6]');
                    text = text.replace(/[u|\u00F9|\u00FA|\u00FB|\u00FC](?![^[]*\])/gi, '[u|\u00F9|\u00FA|\u00FB|\u00FC]');
                    return text;
                }
                function expandFacets(facetOptions) {
                    var filters = $('#filter').find('.filter-panel');
                    setTimeout(function(filters, facetOptions){
                        filters.each(function(i, e) {
                            var fieldName = $.trim($(e).find('.filter-panel--title').text());
                            if(facetOptions != null || facetOptions != undefined){
                                if(facetOptions.hasOwnProperty(fieldName) && facetOptions[fieldName]['expanded'] === true) {
                                    $(this).addClass("is--collapsed");
                                }
                            }
                        });
                    }, 1, filters, facetOptions);
                }
                function toggleSearchIcon(iconElement) {
                    iconElement.toggleClass('icon--search');
                    iconElement.toggleClass('icon--cross');
                }
            });
        });
    </script>
    <script>
        document.asyncReady(function() {
            $(document).ready(function() {
                StateManager.updatePlugin('*[data-listing-actions="true"]', 'swListingActions');
                StateManager.updatePlugin('*[data-range-slider="true"]', 'swRangeSlider');
            });
        });
    </script>
    {* from listing/list.tpl*}
    <script>
        document.asyncReady(function() {
            $(document).ready(function() {
                StateManager.updatePlugin('*[data-listing-actions="true"]', 'swListingActions');
            });
        });
    </script>
    <script>
        document.asyncReady(function() {
            $(document).ready(function () {
                $('.sort--field.action--field').on('change', function(e) {
                    var selectValue = this.options[e.target.selectedIndex].value;
                    $('input[name=o]').each(function(i, el){
                        el.value = selectValue;
                        $("#filter").submit();
                    });

                });
            });
        });
    </script>
    {**}
    <script type="text/javascript">
        document.asyncReady(function() {
            initialJssorScale = 0;
            console.log({banner})
            {$banner.id}_slider_init = function() {
                var {$banner.id}_SlideoTransitions = {$banner.transition};
                var {$banner.id}_SlideoBreaks = {$banner.break};
                var {$banner.id}_SlideoControls = {$banner.control};
                var {$banner.id}_options = {$banner.options};
                var {$banner.id}_slider = new $JssorSlider$({$banner.id}, {$banner.id}_options);
                var MAX_WIDTH = {$banner.max_width};
                function ScaleSlider() {$banner.function}
                ScaleSlider();
                $Jssor$.$AddEvent(window, "load", ScaleSlider);
                $Jssor$.$AddEvent(window, "resize", ScaleSlider);
                $Jssor$.$AddEvent(window, "orientationchange", ScaleSlider);

            }
        });
    </script>
    <script type="text/javascript">
        document.asyncReady(function() {
            {$banner.id}_slider_init();
        });
    </script>
{/block}

{block name="frontend_product_finder_script"}
    <script>
        var json =  {$json_facets},
            lang  = '{$locale}',
            facets = new bxFacets(),
            selectedValues = {};
        facets.init(json);

        // Get the field name of the expert facet
        var expertFieldName =  facets.getDataOwnerFacet();

        // Returns all the experts
        var expertFacetValues = facets.getFacetValues(expertFieldName)[expertFieldName];

        // default expert
        var defaultExpert = null;
        expertFacetValues.forEach(function(value) {
            //checks each sommelier if set to default
            if(facets.getFacetValueExtraInfo(expertFieldName, value, 'is-initial')) {
                defaultExpert = value;
            }
        });

        // image for default expert & Intro
        var defaultExpertFirstName = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'first-name');
        var defaultExpertLastName  = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'last-name');
        var persona                = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'persona');
        var defaultExpertQuestionImage = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-img');
        var quickFinderIntro = facets.getFacetExtraInfo(expertFieldName, 'quickFinderIntro');

        // append default expert to template

        jQuery('.quickFinderContent').append('<img src="https://' + defaultExpertQuestionImage + '" alt="" style="width:90%;border-radius: 50%;right:0;left:0;margin-left:auto;margin-right:auto;"><div class="text"><p style="font-size: 1.2rem;text-align:center;">' + quickFinderIntro + '</p></div>');
        createButton();
        function createButton() {
            $('.quickFinderButton').append($('<button id="b-find" style="background-color: #993366; border:none; color:white; text-align:center;width:100%;font-size:1.2rem;">ZUM PRODUKTEFINDER</button>'));
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
                    } else {
                        urlString += param;
                    }
                });
                window.location = urlString;
            });
        }
    </script>
{/block}
