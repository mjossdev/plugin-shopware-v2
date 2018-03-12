
<div class="content listing--content">
    {$Data.bx_view}
</div>
<script>
    document.asyncReady(function() {
        console.log(window.controller);
        window.controller.ajax_listing = '/shopware_5_3v2/widgets/Listing/ajaxListing';
        window.StateManager

            .addPlugin('body', 'swAjaxProductNavigation')
            .addPlugin('*[data-collapse-panel="true"]', 'swCollapsePanel')
            .addPlugin('*[data-range-slider="true"]', 'swRangeSlider')
            .addPlugin('*[data-auto-submit="true"]', 'swAutoSubmit')
            .addPlugin('*[data-drop-down-menu="true"]', 'swDropdownMenu')
            .addPlugin('*[data-newsletter="true"]', 'swNewsletter')
            .addPlugin('*[data-pseudo-text="true"]', 'swPseudoText')
            .addPlugin('*[data-preloader-button="true"]', 'swPreloaderButton')
            .addPlugin('*[data-filter-type]', 'swFilterComponent')
            .addPlugin('*[data-listing-actions="true"]', 'swListingActions')
            .addPlugin('*[data-scroll="true"]', 'swScrollAnimate')

            .addPlugin('*[data-infinite-scrolling="true"]', 'swInfiniteScrolling')
        ;
        $(document).ready(function() {

        });
    });
</script>