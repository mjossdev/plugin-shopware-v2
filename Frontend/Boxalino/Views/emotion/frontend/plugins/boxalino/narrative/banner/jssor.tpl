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
