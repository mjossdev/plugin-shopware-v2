<script type="text/javascript" async>
    document.asyncReady(function() {

        initialJssorScale = 0;
        {$banner.id}_slider_init = function() {
            var jssor_1_SlideoTransitions = {$banner.transition};
            var jssor_1_SlideoBreaks = {$banner.break};
            var jssor_1_SlideoControls = {$banner.control};
            var jssor_1_options = {$banner.options};
            var jssor_1_slider = new $JssorSlider$({$banner.id}, jssor_1_options);
            var MAX_WIDTH = {$banner.max_width};
            function ScaleSlider() {$banner.function}
            ScaleSlider();
            $Jssor$.$AddEvent(window, "load", ScaleSlider);
            $Jssor$.$AddEvent(window, "resize", ScaleSlider);
            $Jssor$.$AddEvent(window, "orientationchange", ScaleSlider);

        }
    });
</script>

<style>
    {$banner.css}
</style>

<div id={$banner.id} style={$banner.style}>
    {$banner.loading_screen}
    <div data-u="slides" style={$banner.slides_style}>
        {foreach $banner.slides as $slide}
            {$slide.div}
        {/foreach}
    </div>

    {if $banner.hitCount > 1}
        {$banner.bullet_navigator}
        {$banner.arrow_navigator}
    {/if}

</div>
