(function($) {
    'use strict';

    function BoxalinoFinder(el) {
        this.$el = $(el);

        this.$finderJs = new bxFinder();
        this.$facetsJson = this.$el.find('.bx-finder-init-json').html();
        this.$finderUrl = this.$el.find('.bx-finder-init-url').html();
        this.$finderLanguage = this.$el.find('.bx-finder-init-language').html();
        this.$finderMaxScore = this.$el.find('.bx-finder-init-max-score').html();
        this.$finderHighlighted = this.$el.find('.bx-finder-init-highlighted').html();
        this.$finderAlert = this.$el.find('.bx-finder-init-alert').html();
        this.$validatedJson = $.parseJSON(JSON.parse(JSON.stringify(this.$facetsJson.substr(0, Number(this.$facetsJson.lastIndexOf("}"))+1))));

        this.$finderJs.init(this.$validatedJson, this.$finderLanguage, this.$finderUrl, this.$finderMaxScore, this.$finderHighlighted, this.$finderAlert);

        this.$finderJs.setExpertListHtml(this.$el.find('.bx-finder-template-expertListHtml').html());
        this.$finderJs.setAdditionalButtonHtml(this.$el.find('.bx-finder-template-additionalButton').html());
        this.$finderJs.setFewerButtonHtml(this.$el.find('.bx-finder-template-fewerButton').html());
        this.$finderJs.setBackButtonHtml(this.$el.find('.bx-finder-template-backButton').html());
        this.$finderJs.setResultsButtonHtml(this.$el.find('.bx-finder-template-resultsButton').html());
        this.$finderJs.setSkipButtonHtml(this.$el.find('.bx-finder-template-skipButton').html());
        this.$finderJs.setShowProductsButtonHtml(this.$el.find('.bx-finder-template-showProductsButton').html());
        this.$finderJs.setConfiguratorMessageHtml(this.$el.find('.bx-finder-template-configuratorMessage').html());

        this.$finderJs.createView();
    }

    $.fn.bxFinder = function () {
        return this.each(function() {
            var $el = $(this);

            if ($el.data('plugin_finder')) {
                return;
            }

            var bxFinderPlugin = new BoxalinoFinder(this);
            $el.data('plugin_finder', bxFinderPlugin);
        });
    };

    $(function () {
        var el = $('*[data-bx-finder="true"]');
        if(el.length > 0) {
            el.bxFinder();
        }
    });

})(jQuery);