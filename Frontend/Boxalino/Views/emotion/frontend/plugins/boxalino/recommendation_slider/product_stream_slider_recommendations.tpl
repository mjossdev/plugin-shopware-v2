{if $articles}
    {if $title != ''}<div class="bx_replace">{$title}</div>{/if}
    {if $bx_request_uuid}<div class="bx_attributes" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-group-by="{$bx_request_groupby}"></div>{/if}
    {include file="frontend/_includes/product_slider_items.tpl"}
{/if}