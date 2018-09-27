<style>
    {$Data.css}
</style>

<div id={$Data.id} style={$Data.style}>
    {$Data.loading_screen}
    <div data-u="slides" style={$Data.slides_style}>
        {foreach $Data.slides as $slide}
            {$slide.div}
        {/foreach}
    </div>

    {if $Data.hitCount > 1}
        {$Data.bullet_navigator}
        {$Data.arrow_navigator}
    {/if}

</div>