{*
 * main class for displaying narratives server-side
 * @see SearchInterceptor::processNarrativeRequest()
*}
{extends file='parent:frontend/listing/index.tpl'}
{block name='frontend_index_content_left'}{/block}
{block name='frontend_index_content'}
    {include file='frontend/plugins/boxalino/narrative/basic.tpl'}
{/block}
