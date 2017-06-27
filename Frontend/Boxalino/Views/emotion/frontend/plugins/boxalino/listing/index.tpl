{extends file='frontend/index/index.tpl'}
{block name="frontend_index_header_javascript_jquery_lib"}
    {$smarty.block.parent}
    <script>
        $(document).ready(function() {
            var facetOptions = {$facetOptions|json_encode};
            if($('.filter--trigger').hasClass('is--active')){
                expandFacets(facetOptions);
            }
            $.subscribe('plugin/swListingActions/onOpenFilterPanel', function() {
                expandFacets(facetOptions);
            });
            $.overridePlugin('swFilterComponent',{
                open: function(closeSiblings) {
                    var me = this;
                    me.$el.addClass(me.opts.collapseCls);
                    $.publish('plugin/swFilterComponent/onOpen', [ me ]);
                }
            });
            $.overridePlugin('swListingActions', {
                onBodyClick: function(event) {
                    var me = this,
                        $target = $(event.target);
                    $.publish('plugin/swListingActions/onBodyClick', [ me, event ]);
                }
            });
            StateManager.destroyPlugin('*[data-listing-actions="true"]','swListingActions');
            $('.filter--active-container').empty();
            StateManager.destroyPlugin('*[data-filter-type]','swFilterComponent');
            StateManager.updatePlugin('*[data-listing-actions="true"]','swListingActions');
            StateManager.updatePlugin('*[data-filter-type]','swFilterComponent');
            if(jQuery().ttFis){
                $.overridePlugin('ttFis', {
                    directSubmit: function(){
                        var me = this;

                        me.$filterForm.find('.filter-panel--content input').off('change');
                        me.$filterForm.find('.filter--btn-apply').hide();

                        me.$filterForm.find('input').on("change",function(){
                            if(!$(this).hasClass('bx--facet-search')) {
                                $.loadingIndicator.open();
                                me.$filterForm.submit();
                            }
                        });

                        $('.filter--active-container :not(.is--disabled) .filter--active').on('click',function(){
                            $.loadingIndicator.open();
                        });
                    }
                });
                StateManager.destroyPlugin('.tab10-filter-in-sidebar', 'ttFis');
                StateManager.addPlugin('.tab10-filter-in-sidebar', 'ttFis', ['m', 'l', 'xl']);
            }
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
                    var regMatch = new RegExp(escapeRegExp(text), 'gi');
                    $(this).parent().next().find('.show-more-values').hide();
                    options.each(function(i, e) {
                        var label = $(e).find('label').text();
                        var match = label.match(regMatch);
                        if(match) {
                            $(e).find('label').html(label.replace(match[0], '<strong>'+match[0]+'</strong>'));
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
                return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
            }
            function expandFacets(facetOptions) {
                var filters = $('#filter').find('.filter-panel');
                setTimeout(function(filters, facetOptions){
                    filters.each(function(i, e) {
                        var fieldName = $.trim($(e).find('.filter-panel--title').text());
                        if(facetOptions.hasOwnProperty(fieldName) && facetOptions[fieldName]['expanded'] === true) {
                            $(this).addClass("is--collapsed");
                        }
                    });
                }, 1, filters, facetOptions);
            }
            function toggleSearchIcon(iconElement) {
                iconElement.toggleClass('icon--search');
                iconElement.toggleClass('icon--cross');
            }
        });
    </script>
{/block}