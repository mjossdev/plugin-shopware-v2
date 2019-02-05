{extends file='parent:frontend/index/index.tpl'}
{block name="frontend_index_header_javascript"}
    {$smarty.block.parent}
    {if (isset($narrativeData.facets))}
        {include file="frontend/plugins/boxalino/narrative/script/facets.tpl"}
    {/if}
    <script>
        document.asyncReady(function() {
            $(document).ready(function() {
                StateManager.updatePlugin('*[data-listing-actions="true"]', 'swListingActions');
                StateManager.updatePlugin('*[data-range-slider="true"]', 'swRangeSlider');
            });
        });
    </script>
    {* from listing/list.tpl*}
    <script>
        document.asyncReady(function() {
            $(document).ready(function() {
                StateManager.updatePlugin('*[data-listing-actions="true"]', 'swListingActions');
            });
        });
    </script>
    <script>
        document.asyncReady(function() {
            $(document).ready(function () {
                $('.sort--field.action--field').on('change', function(e) {
                    var selectValue = this.options[e.target.selectedIndex].value;
                    $('input[name=o]').each(function(i, el){
                        el.value = selectValue;
                        $("#filter").submit();
                    });

                });
            });
        });
    </script>
    {*from banner/jssor.tpl*}
    {if (isset($narrativeData.banner))}
        {include file="frontend/plugins/boxalino/narrative/script/jssor.tpl"}
    {/if}
{/block}
