{*
 * main class for displaying narratives server-side when the template is not replacing the main calls
 * @see SearchInterceptor::getNarrativeTemplateData()
*}
{extends file='parent:frontend/listing/index.tpl'}
{block name="frontend_index_content_top" nocache}
    {if ($narrative_block_position=="before" || empty($narrative_block_position))}
        {include file='frontend/plugins/boxalino/narrative/basic.tpl'}
    {/if}
    {if ($narrative_block_rewrite == "no" || empty($narrative_block_rewrite))}
        {$smarty.block.parent}
    {/if}
    {if $narrative_block_position=="after"}
        {include file='frontend/plugins/boxalino/narrative/basic.tpl'}
    {/if}
{/block}

