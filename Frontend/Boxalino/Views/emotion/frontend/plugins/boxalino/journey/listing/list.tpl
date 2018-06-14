<div class="content--wrapper">
    <div class="content listing--content">
        <div class="listing--wrapper has--sidebar-filter">
            <div data-listing-actions="true" data-buffertime="0" class="listing--actions is--rounded without-facets without-pagination">
                <div class="action--filter-btn">
                    <a href="#" class="filter--trigger btn is--small" data-filter-trigger="true" data-offcanvas="true" data-offcanvasselector=".action--filter-options" data-closebuttonselector=".filter--close-btn">
                        <i class="icon--filter"></i>
                        Filter
                        <span class="action--collapse-icon"></span>
                    </a>
                </div>
                <form class="action--sort action--content block" method="get" data-action-form="true">
                    <input name="p" value="1" type="hidden">
                    <label class="sort--label action--label">Sorting</label>
                    <div class="sort--select select-field">
                        <select name="o" class="sort--field action--field" data-auto-submit="true" data-loadingindicator="false">
                            <option value="7" selected="selected">Best results</option>
                            <option value="1">Release date</option>
                            <option value="2">Popularity</option>
                            <option value="3">Lowest price</option>
                            <option value="4">Highest price</option>
                            <option value="5">Article description</option>
                        </select>
                    </div>
                </form>

            </div>
            <div class="listing--container">
                <div class="listing" data-ajax-wishlist="true" data-compare-ajax="true">
                    {$i=0}
                    {foreach $bxSubRenderings as $subRendering}
                        {$additionalParameter=[]}
                        {if $subRendering.visualElement.format == 'product'}
                            {$additionalParameter['list_index'] = $i++}
                        {/if}
                        {$bxRender->renderElement($subRendering.visualElement, $additionalParameter)}
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>

