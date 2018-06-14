{if $Data.render_option == 1}
    {$Data.dependencies}
    {foreach $Data.narrative.acts as $act}
        {foreach $act.chapter.renderings as $rendering}
            {foreach $rendering.rendering.visualElements as $visualElement}
                {$Data.bxRender->renderElement($visualElement.visualElement)}
            {/foreach}
        {/foreach}
    {/foreach}
{else}
    <div class="narrative-container"></div>
    <script>
        $(document).ready(function() {
            var controller  = '{url controller=BxNarrative}';
            $.ajax({
                type: "GET",
                url: controller
            }).done(function(res) {
                $('.narrative-container').html(res);
                }, function (err) {

            });
        });
    </script>
{/if}