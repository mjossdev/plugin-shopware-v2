{namespace name="frontend/listing/listing_actions"}
<div class="listing--sidebar">
    <div class="sidebar-filter">
        <div class="sidebar-filter--content">
            {if $criteria && $facets}
            {block name='frontend_listing_actions_filter'}
            {$listingMode = {config name=listingMode}}

            <div class="action--filter-options off-canvas{if $facets|count <= 0} is--hidden{/if}{if $listingMode != 'full_page_reload'} is--ajax-reload{/if}">

                {block name='frontend_listing_actions_filter_close_button'}
                    <a href="#" class="filter--close-btn" data-show-products-text="{s name="ListingActionsCloseFilterShowProducts"}{/s}">
                        {s name="ListingActionsCloseFilter"}{/s} <i class="icon--arrow-right"></i>
                    </a>
                {/block}

                {block name='frontend_listing_actions_filter_container'}
                <div class="filter--container">
                    {block name='frontend_listing_actions_filter_form'}
                    <form id="filter"
                          method="get"
                          data-filter-form="true"
                          data-is-in-sidebar="{if $theme.sidebarFilter}true{else}false{/if}"
                          data-listing-url="{$countCtrlUrl}"
                          data-is-filtered="{if $criteria}{$criteria->getUserConditions()|count}{else}0{/if}"
                          data-load-facets="{if $listingMode == 'filter_ajax_reload'}true{else}false{/if}"
                          data-instant-filter-result="{if $listingMode != 'full_page_reload'}true{else}false{/if}"
                          class="{if $listingMode != 'full_page_reload'} is--instant-filter{/if}">

                        {if $listingMode === 'full_page_reload'}
                            {include file="frontend/listing/actions/filter-apply-button.tpl" clsSuffix='filter--actions-top'}
                        {/if}

                        {block name="frontend_listing_actions_filter_form_page"}
                            <input type="hidden" name="{$shortParameters['sPage']}" value="1"/>
                        {/block}

                        {block name="frontend_listing_actions_filter_form_search"}
                            {if $term}
                                <input type="hidden" name="{$shortParameters['sSearch']}" value="{$term|escape}"/>
                            {/if}
                        {/block}

                        {block name="frontend_listing_actions_filter_form_sort"}
                            {if $sSort}
                                <input type="hidden" name="{$shortParameters['sSort']}" value="{$sSort|escape}"/>
                            {/if}
                        {/block}

                        {block name="frontend_listing_actions_filter_form_perpage"}
                            {if $criteria && $criteria->getLimit()}
                                <input type="hidden" name="{$shortParameters['sPerPage']}" value="{$criteria->getLimit()|escape}"/>
                            {/if}
                        {/block}

                        {block name="frontend_listing_actions_filter_form_category"}
                            {if !$sCategoryContent && $sCategoryCurrent != $sCategoryStart && {controllerName} != 'search'}
                                <input type="hidden" name="{$shortParameters['sCategory']}" value="{$sCategoryCurrent|escape}" />
                            {/if}
                        {/block}

                        {block name="frontend_listing_actions_filter_form_facets"}
                        <div class="filter--facet-container">
                            {foreach $facets as $facet}
                            {if $facet->getTemplate() !== null}
                            {if $facet->getTemplate() == 'frontend/listing/filter/facet-value-list.tpl'}
                            {$type = 'value-list'}
                            {$filterType = 'value-list'}
                            {$listingMode = {config name="listingMode"}}
                            {if $listingMode == 'filter_ajax_reload'}
                                {$type = 'value-list-single'}
                                {$filterType = 'value-list-single'}
                            {/if}
                            <div class="filter-panel filter--multi-selection filter-facet--{$filterType} facet--{$facet->getFacetName()|escape:'htmlall'}"
                                 data-filter-type="{$filterType}"
                                 data-facet-name="{$facet->getFacetName()}"
                                 data-field-name="{$facet->getFieldName()|escape:'htmlall'}">
                                {block name="frontend_listing_filter_facet_multi_selection_flyout"}
                                <div class="filter-panel--flyout">
                                    {block name="frontend_listing_filter_facet_multi_selection_title"}
                                        <label class="filter-panel--title" for="{$facet->getFieldName()|escape:'htmlall'}">{$facet->getLabel()|escape}</label>
                                    {/block}
                                    {block name="frontend_listing_filter_facet_multi_selection_icon"}
                                        <span class="filter-panel--icon"></span>
                                    {/block}
                                    {block name="frontend_listing_filter_facet_value_list_search_field"}
                                        {if $bxFacets->getFacetExtraInfo({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim}, 'visualisation') == 'search'}
                                            <div class="bx-{$facet->getFacetName()|escape:'htmlall'}-search" style="padding: 0rem 0rem 1rem 0rem;position:relative;0.5px solid">
                                                <input class="bx--facet-search" type="search" style="width: 100%;border: 1px solid;" />
                                                <span class="icon--search search-remove" style="padding: 0.3rem .8rem 0.3rem 0.8rem;
                                            position: absolute;
                                            top:0rem;right:0rem;z-index:2;
                                            border: 0 none; background: transparent;outline:none;
                                            text-transform: none;font-size: 2rem;">
                                                                                    </span>
                                            </div>
                                        {/if}
                                    {/block}
                                    {block name="frontend_listing_filter_facet_multi_selection_content"}
                                    {$inputType = 'checkbox'}
                                    {if $filterType == 'radio'}
                                        {$inputType = 'radio'}
                                    {/if}
                                    {$indicator = $inputType}
                                    {$isMediaFacet = false}
                                    {if $facet|is_a:'\Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult'}
                                        {$isMediaFacet = true}
                                        {$indicator = 'media'}
                                    {/if}
                                    <div class="filter-panel--content input-type--{$indicator}">
                                        {block name="frontend_listing_filter_facet_multi_selection_list"}
                                        <ul class="filter-panel--option-list">
                                            {$showMore = false}
                                            {assign var="hiddenClass" value=''}
                                            {assign var="optionStyle" value=''}
                                            {foreach $facet->getValues() as $option}

                                            {if $showMore == false}
                                                {if $facet->getFacetName() == 'property'}
                                                    {if $bxFacets->isFacetValueHidden({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim},
                                                    {$option->getLabel()|substr:0:{{$option->getLabel()|strrpos:'('}-1}|cat:'_bx_'|cat:$option->getId()|trim}) === true}
                                                        {assign var="showMore" value=true}
                                                    {/if}
                                                {else}
                                                    {if $bxFacets->isFacetValueHidden({$facetOptions.{$facet->getLabel()|trim}.fieldName},
                                                    {$option->getLabel()|substr:0:{{$option->getLabel()|strrpos:'('}-1}|trim}) === true}
                                                        {assign var="showMore" value=true}
                                                    {/if}
                                                {/if}
                                                {if $showMore == true}
                                                    {assign var="hiddenClass" value=' hidden-items'}
                                                    {assign var="optionStyle" value='style="display:none"'}
                                                {/if}
                                            {/if}
                                            {block name="frontend_listing_filter_facet_multi_selection_option"}
                                            <li class="filter-panel--option{$hiddenClass}" {$optionStyle}>
                                                {block name="frontend_listing_filter_facet_multi_selection_option_container"}
                                                <div class="option--container">
                                                    {block name="frontend_listing_filter_facet_multi_selection_input"}
                                                    <span class="filter-panel--input filter-panel--{$inputType}">
    {$name = "__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"}
                                                                                                            {if $filterType == 'radio'}
    {$name = {$facet->getFieldName()|escape:'htmlall'} }
{/if}
    <input type="{$inputType}"id="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"name="{$name}"value="{$option->getId()|escape:'htmlall'}"{if $option->isActive()}checked="checked" {/if}/>
                                                                                                            <span class="input--state {$inputType}--state">&nbsp;</span>
                                                                                                            </span>
                                                                                                        {/block}

                                                                                                        {block name="frontend_listing_filter_facet_multi_selection_label"}
                                                                                                            <label class="filter-panel--label" for="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}">
                                                                                                                {if $facet|is_a:'\Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult'}
                                                                                                                    {$mediaFile = {link file='frontend/_public/src/img/no-picture.jpg'}}
                                                                                                                    {if $option->getMedia()}
                                                                                                                        {$mediaFile = $option->getMedia()->getFile()}
                                                                                                                    {/if}

                                                                                                                    <img class="filter-panel--media-image" src="{$mediaFile}" alt="{$option->getLabel()|escape:'htmlall'}" />
                                                                                                                {else}
                                                                                                                    {$option->getLabel()|escape}
                                                                                                                {/if}
                                                                                                            </label>
                                                                                                        {/block}
                                                                                                    </div>
                                                                                                {/block}
                                                                                            </li>
                                                                                        {/block}
                                                                                    {/foreach}
                                                                                    {if $showMore == true}
                                                                                        <li style="cursor:pointer" class="show-more-values">{s namespace="boxalino/intelligence" name="filter/morevalues"}{/s}</li>
                                                                                    {/if}
                                                                                </ul>
                                                                            {/block}
                                                                        </div>
                                                                    {/block}
                                                                </div>
                                                            {/block}
                                                        </div>
                                                        {else}
                                                            {include file=$facet->getTemplate() facet=$facet}
                                                        {/if}
                                                    {/if}
                                                {/foreach}
                                            </div>
                                        {/block}

                                        {block name="frontend_listing_actions_filter_active_filters"}
                                            <div class="filter--active-container"
                                                 data-reset-label="{s name='ListingFilterResetAll'}{/s}">
                                            </div>
                                        {/block}

                                        {if $listingMode === 'full_page_reload'}
                                            {include file="frontend/listing/actions/filter-apply-button.tpl" clsSuffix='filter--actions-bottom'}
                                        {/if}
                                    </form>
                                {/block}
                            </div>
                        {/block}
                    </div>
                {/block}
            {/if}
        </div>
    </div>
</div>