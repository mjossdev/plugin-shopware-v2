<div class="narrative-container">
    {$dependencies}

    {$additionalParameter = []}
    {$contextData = []}
    {$contextData.sCategoryContent = $sCategoryContent}
    {$contextData.narrative_bx_request_uuid = $narrative_bx_request_uuid}
    {$contextData.narrative_bx_request_id = $narrative_bx_request_id}
    {$contextData.narrative_bx_request_groupby = $narrative_bx_request_group_by}

    {foreach $narrative as $visualElement}
        {$bxRender->renderElement($visualElement.visualElement, $additionalParameter, $contextData)}
    {/foreach}
</div>
