{*
 * main class for displaying product-finder without the use of emotion
 * @see SearchInterceptor::processCPOFinderRequest()
*}
{extends file='parent:frontend/listing/index.tpl'}

{block name='frontend_index_left_categories'}{/block}
{block name='frontend_index_left_menu'}{/block}

{block name="frontend_index_content_main_classes"}
    {strip}{$smarty.block.parent} is--finder is--no-sidebar{/strip}
{/block}

{block name='frontend_index_content'}
    {include file="frontend/plugins/boxalino/product_finder/product_finder.tpl" Data = $data}
{/block}