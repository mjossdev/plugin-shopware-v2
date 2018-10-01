{*
 * main class for displaying narratives server-side
 * @see SearchInterceptor::processNarrativeRequest()
*}
{extends file='parent:frontend/listing/index.tpl'}
{block name='frontend_index_content_left'}{/block}
{block name='frontend_index_content'}
    <div class="narrative-container">
        {$dependencies}

        {foreach $narrative as $visualElement}
            {$bxRender->renderElement($visualElement.visualElement)}
        {/foreach}

    </div>
{/block}
