(function($, window, document) {
    'use strict';

    var rtuxApiHelper = {

        /**
         * additional parameters to be set: returnFields, filters, facets, sort
         * for more details, check the Narrative Api Technical Integration manual provided by Boxalino
         *
         * @public
         * @returns {{widget: *, hitCount: number, apiKey: *, dev: boolean, test: boolean, profileId: (string|*|{}|DOMPoint|SVGTransform|SVGNumber|SVGLength|SVGPathSeg), language: *, sessionId: *, groupBy: *, parameters: {"User-Agent": string, "User-URL", "User-Referer": string}, username: *}}
         */
        getApiRequestData: function(account, apiKey, widget, language, groupBy, hitCount = 1, dev=false, test=false, otherParameters={}) {
            var baseParameters = {
                'username':account,
                'apiKey': apiKey,
                'sessionId': this.getApiSessionId(),
                'profileId': this.getApiProfileId(),
                'customerId': this.getApiProfileId(),
                'widget': widget,
                'dev': dev,
                'test': test,
                'hitCount':hitCount,
                'language': language.split("_")[0],
                'groupBy': groupBy,
                'parameters': {
                    'User-Referer':document.referrer,
                    'User-URL':window.location.href,
                    'User-Agent':navigator.userAgent
                }
            };

            return Object.assign({}, baseParameters, otherParameters);
        },

        /**
         * @public
         * @returns {string}
         */
        getApiRequestUrl:function() {
            var url = "https://main.bx-cloud.com";
            if(window.rtuxAutocomplete['dev'] || window.rtuxAutocomplete['test']) {
                url = "https://r-st.bx-cloud.com";
            }
            var account = window.rtuxAutocomplete['apiPreferentialAccount'],
                endpoint= url+"/narrative/%%account%%/api/1?profileId=";
            return endpoint.replace("%%account%%", account) + encodeURIComponent(this.getApiProfileId());
        },

        /**
         * @public
         * @returns {string|*|{}|DOMPoint|SVGTransform|SVGNumber|SVGLength|SVGPathSeg}
         */
        getApiProfileId:function() {
            return this.getCookie('cemv');
        },

        /**
         * @public
         * @returns {string|*|{}|DOMPoint|SVGTransform|SVGNumber|SVGLength|SVGPathSeg}
         */
        getApiSessionId:function() {
            return this.getCookie('cems');
        },

        /**
         * Get the value of a cookie with the given name
         * @param name
         * @returns {string|undefined}
         */
        getCookie:function(name) {
            var parts = document.cookie.split(name + '=');
            if (parts.length > 1) {
                return parts.pop().split(';').shift();
            }
            return undefined;
        }
    };

    window.rtuxApiHelper = rtuxApiHelper;

})(jQuery, window, document);