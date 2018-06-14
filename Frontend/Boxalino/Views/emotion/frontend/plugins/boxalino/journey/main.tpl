{$dependencies}
{foreach $narrative.acts as $act}
    {foreach $act.chapter.renderings as $rendering}
        {foreach $rendering.rendering.visualElements as $visualElement}
            {$bxRender->renderElement($visualElement.visualElement)}
        {/foreach}
    {/foreach}
{/foreach}