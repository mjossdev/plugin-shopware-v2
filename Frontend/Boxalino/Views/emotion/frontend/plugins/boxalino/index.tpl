{extends file='parent:frontend/index/index.tpl'}
{block name="frontend_index_before_page" append}{$report_script}{/block}
{block name="frontend_index_footer" append}
    {if $bxHelper}
        {$bxHelper->callNotification($bxForce)}
    {/if}
{/block}

{block name="frontend_index_header_meta_tags" append}
    <meta name="bx_debug" content="{$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}" />
{/block}
