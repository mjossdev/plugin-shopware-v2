{extends file='frontend/index/index.tpl'}
{block name="frontend_index_before_page" append}{$report_script}{/block}
{block name="frontend_index_footer" append}
    {$helper->callNotification()}
{/block}