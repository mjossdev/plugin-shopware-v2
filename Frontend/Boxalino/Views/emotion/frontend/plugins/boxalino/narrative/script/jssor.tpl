<script type="text/javascript">
    document.asyncReady(function() {
        initialJssorScale = 0;
        {$narrativeData.banner.id}_slider_init = function() {
            var {$narrativeData.banner.id}_SlideoTransitions = {$narrativeData.banner.transition};
            var {$narrativeData.banner.id}_SlideoBreaks = {$narrativeData.banner.break};
            var {$narrativeData.banner.id}_SlideoControls = {$narrativeData.banner.control};
            var {$narrativeData.banner.id}_options = {$narrativeData.banner.options};
            var {$narrativeData.banner.id}_slider = new $JssorSlider$({$narrativeData.banner.id}, {$narrativeData.banner.id}_options);
            var MAX_WIDTH = {$narrativeData.banner.max_width};
            function ScaleSlider() {$narrativeData.banner.function}
            ScaleSlider();
            $Jssor$.$AddEvent(window, "load", ScaleSlider);
            $Jssor$.$AddEvent(window, "resize", ScaleSlider);
            $Jssor$.$AddEvent(window, "orientationchange", ScaleSlider);

        }
        {$narrativeData.banner.id}_slider_init();
    });
</script>
