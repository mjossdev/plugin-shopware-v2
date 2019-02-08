<div class="narrative-container">
    {$dependencies}

    {$additionalParameter = []}
    {$contextData = []}
    {$contextData.sCategoryContent = $sCategoryContent}

    {foreach $narrative as $visualElement}
        {$bxRender->renderElement($visualElement.visualElement, $additionalParameter, $contextData)}
    {/foreach}
</div>
