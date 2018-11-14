{extends file='parent:frontend/detail/config_upprice.tpl'}
{if $Data.isFinder}
    <div class="bx-finder-configurator">
        {block name='frontend_detail_group_name'}
            {$selectedOptionLabel=null}
            {foreach $sArticle.sConfigurator as $sConfigurator}
                {foreach $sConfigurator.values as $configValue}
                    {if !{config name=hideNoInstock} || ({config name=hideNoInstock} && $configValue.selectable)}
                        {if $configValue.selected}{$selectedOptionLabel=$configValue.optionname}{/if}
                    {/if}
                {/foreach}
            {/foreach}
            {if ($sArticle.isSelectionSpecified || $sArticle.additionaltext)}
                <div class="cpo-finder-listing-selected-option"><p>{s namespace="boxalino/intelligence" name="productfinder/specifiedSelection"}{/s}{$sArticle.additionaltext}</p></div>
            {/if}
            <a href="{$sArticle.linkDetails}" class="buybox--button block btn is--icon-right is--center is--large" title="{$label} - {$title}">
                {s namespace="boxalino/intelligence" name="productfinder/gotodetail"}{/s}<i class="icon--arrow-right"></i>
            </a>
        {/block}
        {block name='frontend_detail_group_description'}{/block}
        {block name='frontend_detail_group_selection'}{/block}
        {block name='frontend_detail_configurator_noscript_action'}{/block}
    </div>
{/if}
