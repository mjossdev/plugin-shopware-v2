{extends file='parent:frontend/detail/actions.tpl'}
{if $Data.isFinder}
    {block name='frontend_detail_actions_compare'}{/block}
    {block name='frontend_detail_actions_review'}{/block}
    {block name='frontend_detail_actions_voucher'}{/block}
{/if}