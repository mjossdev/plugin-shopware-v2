;(function ($, StateManager, window) {
    'use strict';

    function _getApiRequestData(value) {
        var otherParameters = {
            'acQueriesHitCount': window.rtuxAutocomplete['suggestionsCount'],
            'acHighlight': true,                                                  // highlight matching sections
            'acHighlightPre':"<em>",                                              //textual suggestion highlight start
            'acHighlightPost':"</em>",                                            //textual suggestion highlight end
            'query':value,
            'filters': $.getRtuxApiAcFilters()
        };
        return window.rtuxApiHelper.getApiRequestData(
            window.rtuxAutocomplete['apiPreferentialAccount'],
            window.rtuxAutocomplete['apiPreferentialKey'],
            window.rtuxAutocomplete['widget'],
            window.rtuxAutocomplete['language'],
            'products_group_id',
            window.rtuxAutocomplete['productsCount'],
            window.rtuxAutocomplete['dev'],
            window.rtuxAutocomplete['test'],
            otherParameters
        );
    }

    /**
     * Replace the ajax search with Boxalino API
     */
    $.overridePlugin('swSearch', {
        triggerSearchRequest: function(searchTerm) {
            var el = $('*[data-bx-api-ac="true"]');
            var me = this;

            if(el.length === 0) {
                me.superclass.triggerSearchRequest.apply(this, arguments);
                return;
            }

            me.$loader.fadeIn(me.opts.animationSpeed);
            me.lastSearchTerm = $.trim(searchTerm);

            $.publish('plugin/swSearch/onSearchRequest', [me, searchTerm]);

            if (me.lastSearchAjax) {
                me.lastSearchAjax.abort('searchTermChanged');
            }

            var requestUrl = window.rtuxApiHelper.getApiRequestUrl();
            var apiRequestData = _getApiRequestData(me.lastSearchTerm);
            var apiAcRenderer = $.getRtuxApiAcRenderer();

            me.lastSearchAjax = $.ajax({
                url: requestUrl,
                type: "POST",
                data: JSON.stringify(apiRequestData),
                contentType: "application/json",
                dataType: "json",
                ignoreCSRFHeader: true,
                success: function (response) {
                    var htmlResponse = apiAcRenderer.getHtml(JSON.stringify(response), me.lastSearchTerm);
                    if(apiRequestData.test) { console.log(JSON.stringify(response)); console.log(htmlResponse);}

                    me.showResult(htmlResponse);
                    $.publish('plugin/swSearch/onRtuxApiSearchResponse', [ this, searchTerm, response ]);
                    $.publish('plugin/swSearch/onSearchResponse', [me, searchTerm, htmlResponse]);
                },
                error: function (response, statusText) {
                    if (statusText === 'searchTermChanged') {
                        return;
                    }
                    //fallback to Shopware5 default ajaxSearch event
                    console.log(response.status + ": " + response.statusText + ": " + response.responseText);
                    me.superclass.triggerSearchRequest.call(me, searchTerm);
                }
            });
        }
    });

})(jQuery, StateManager, window);
