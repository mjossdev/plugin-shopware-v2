(function($, window, document) {
    'use strict';

    /**
     *
     * @type {{getHtml: (function(*=, *): string), getSuggestionListHtml: (function(*): string), getProductListHtml: (function(*): string), getSuggestionItemHtml: (function(*): string), toHtml: toHtml, getSeeAllHtml: (function(): string), getProductItemHtml: (function(*): string), getWrapperHtml: (function(*): string)}}
     */
    var rtuxApiAcRenderer = {

        /**
         * Generic helper function to parse the response JSON and to render all blocks
         * by calling the function matching the "callback" parameter of the visual block
         *
         * @public
         * @param response
         * @param value
         * @returns {string}
         */
        getHtml:function(response, value) {
            this._value = value;
            var responseData = JSON.parse(response);
            this.groupBy= responseData['advanced'][0]['_bx_group_by'];   // required by API JS tracker
            this.uuid = responseData['advanced'][0]['_bx_variant_uuid'];  // required by API JS tracker
            var html = '';
            responseData['blocks'].forEach(function(block) {
                if(block['callback']) {
                    html += this.toHtml(block['callback'][0], block);
                }
            }.bind(this));

            return html;
        },

        /**
         * Generic helper function to render the template by calling the function set as visual block property "callback"
         *
         * @public
         * @param name
         * @param block
         * @returns {string|*}
         */
        toHtml:function(name, block) {
            try {
                return this[name](block);
            } catch (e) {
                return '<li></li>';
            }
        },

        /**
         * Based on a default autocomplete-js layout, the origin is a "wrapper" element with:
         * -> suggestion list (rendered in getSuggestionListHtml(block){})
         * -> product list (rendered in getProductListHtml(block){})
         * -> see all link (rendered in getSeeAllHtml(block){})
         *
         * @public
         * @param bxblock
         * @returns {string}
         */
        getWrapperHtml:function(bxblock) {
            var html = '<ul class="results--list">';
            bxblock['blocks'].forEach(function (block) {
                if (block['callback']) {
                    html += this.toHtml(block['callback'][0], block);
                }
            }.bind(this));
            html += '</ul>';

            return html;
        },

        /**
         * Renders every bx-hit (product) element via getProductItemHtml(block)
         *
         * HTML mark-up requirement:
         * The bx-narrative class and data-bx-variant-uuid, data-bx-narrative-name, data-bx-narrative-group-by
         * must be part of the template
         *
         * @public
         * @param bxblock
         * @returns {string}
         */
        getProductListHtml:function(bxblock) {
            var html = '<ul class="bx-narrative" data-bx-variant-uuid="' + this.uuid +'" data-bx-narrative-name="products-list" data-bx-narrative-group-by="' + this.groupBy +'">';
            this._totalProductsFound = bxblock['bx-hits']['totalHitCount'];
            bxblock['blocks'].forEach(function(block){
                if(block['callback']) {
                    html += this.getProductItemHtml(block);
                }
            }.bind(this));
            html +='</ul>';

            return html;
        },

        /**
         * Template for how a product recommendation is displayed
         *
         * HTML mark-up requirement:
         * The bx-narrative-item and data-bx-item-id must be part of the template
         *
         * @public
         * @param bxblock
         * @returns {string}
         */
        getProductItemHtml:function(bxblock) {
            var html = '<li class="list--entry block-group result--item bx-narrative-item" data-bx-item-id="' + bxblock['bx-hit']['id'] +'">';
            html += '<a href="' + bxblock['bx-hit']['products_url'][0] +'" title="' +
                bxblock['bx-hit']['products_title'] + '" class="search-result--link">';
            html += '<span class="entry--media block">'+
                '<img src="' + bxblock['bx-hit']['products_image'][0] + '" ' +
                'srcset="'+ bxblock['bx-hit']['products_image'][0] +'" class="media--image" ' +
                'alt="'+ bxblock['bx-hit']['title'] +'"/></span>';
            html += '<span class="entry--name block">'+ bxblock['bx-hit']['title'] +'</span>';
            html += '<span class="entry--price block"><br><small class="search-suggest-product-reference-price">' +
                bxblock['bx-hit']['discountedPrice'] + '&nbsp;' + window.rtuxAutocomplete['currency'] +
                '</small></span></a></li>';

            return html;
        },

        /**
         * Renders every bx-acQuery (textual suggestion) element via getSuggestionItemHtml(block)
         *
         * @public
         * @param bxblock
         * @returns {string}
         */
        getSuggestionListHtml:function(bxblock) {
            var html ='';
            if(bxblock['blocks'].length > 0 ){
                if(bxblock['blocks'][0]['accessor'] !== 'accessor'){
                    return html;
                }
                html += '<ul class="results--list">';
                bxblock['blocks'].forEach(function(block){
                    if(block['callback']) {
                        html += this.getSuggestionItemHtml(block);
                    }
                }.bind(this));
                html += '</ul>';

                return html;
            }

            return html;
        },

        /**
         * @public
         * @param bxblock
         * @returns {string}
         */
        getSuggestionItemHtml:function(bxblock) {
            var html ='';
            if(bxblock['accessor'] === 'accessor') {
                let suggestion = bxblock['bx-acQuery']['highlighted'];
                if(suggestion == null) {suggestion = bxblock['bx-acQuery']['suggestion'];}
                html +='<li class="list--entry block-group result--item">';
                html +=' <a href="'+ window.rtuxAutocomplete['suggestLink'] +
                    encodeURIComponent(bxblock['bx-acQuery']['suggestion']) + '"\n' +
                    '       title="' + bxblock['bx-acQuery']['suggestion'] + '"\n' +
                    '       class="search-result--link">\n' +
                    '        <p>'+ suggestion +'</p>\n' +
                    '    </a>';
                html+='</li>';
            }

            return html;
        },

        /**
         * Displays the "see all" link
         * (per Shopware6 default autocomplete structure)
         *
         * @public
         * @returns {string}
         */
        getSeeAllHtml:function(bxblock) {
            var html = '';
            if(this._totalProductsFound > 0) {
                html +='<li class="entry--all-results block-group result--item">';
                html +='<a href="'+ window.rtuxAutocomplete['suggestLink'] + this._value + '" ' +
                    'title="'+ window.rtuxAutocomplete['seeAllSearchResultsMessage'] + '" '+
                    'class="search-result--link entry--all-results-link block">' +
                    '<i class="icon--arrow-right"></i>' +
                    window.rtuxAutocomplete['seeAllSearchResultsMessage'] +
                    '</a><span class="entry--all-results-number block">' +
                    this._totalProductsFound + window.rtuxAutocomplete['seeAllSearchResultLabel'] + '</span></li>';
            } else {
                html += '<li class="list--entry entry--no-results result--item"><strong class="search-result--link" style="text-align: center;">'
                    + window.rtuxAutocomplete['noSearchResultsMessage'] + '</strong></li>';
            }

            return html;
        }

    };

    /**
     * Get the value of a cookie with the given name
     */
    $.getRtuxApiAcRenderer = function() {
        var renderer = rtuxApiAcRenderer;
        $.publish('plugin/swSearch/onGetRtuxApiAcRenderer', [renderer]);

        return renderer;
    };

    /**
     * Get the value of a cookie with the given name
     */
    $.getRtuxApiAcFilters = function() {
        var filters = [
            {"field": "products_bx_type","values": ["product"],"negative": false},
            {"field": "products_active","values": [1],"negative": false},
            {"field": "products_bx_parent_active", "values": [1],"negative": false},
            {"field": "products_shop_id", "values": [window.rtuxAutocomplete['filterById']],"negative": false}
        ];
        $.publish('plugin/swSearch/onGetRtuxApiAcFilters', [filters]);

        return filters;
    };

})(jQuery, window, document);