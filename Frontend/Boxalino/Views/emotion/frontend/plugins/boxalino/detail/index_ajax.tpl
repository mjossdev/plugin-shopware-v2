{extends name="frontend/detail/index.tpl"}

{block name="frontend_detail_index_tabs_cross_selling"}

    <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
    <div class="tab-menu--cross-selling"{if $sArticle.relatedProductStreams} data-scrollable="true"{/if}>
        <div id="bx-loader"></div>
    </div>

    <script>
        $(function() {
            var el = $('<div>', {
                'class': 'js--loading-indicator indicator--relative',
                'html': $('<i>', {
                    'class': 'icon--default'
                })
            });
            $('#bx-loader').append(el);
            var controller  = '{url controller=RecommendationSlider action=detail articleId=$sArticle.articleID}';
            $.ajax({
                type: "GET",
                url: controller
            }).done(function(res) {
                $('.tab-menu--cross-selling').html(res);
                el.hide();
            }, function (err) {
                console.log(err);
            });
        });
    </script>
{/block}