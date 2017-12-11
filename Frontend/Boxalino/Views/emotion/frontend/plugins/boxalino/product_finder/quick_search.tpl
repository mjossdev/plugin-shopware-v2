{block name="frontend_quick_finder_content"}
<div class="wrapper" style="margin:0">

  <div class="left" style="width:20%; float:left;">

    <div class="leftContent" style="width:80%;left:0;right:0;margin-left:auto;margin-right:auto;">

    </div>

    <div class="button" style=="width:60%;left:0;right:0;margin-left:auto;margin-right:auto;">

    </div>

  </div>

  <div class="center" style="width:80%; float: left;">

    <div class="centerContent" style="width: 100%;">

      <div class="centerContentHeader" style="text-align:center;">

      </div>

      <div class="centerContentContainer" style="text-align:center;">

      </div>

      <div class="currentQuestionOptions" style="width:100%;">

      </div>

  </div>

  </div>

</div>

{/block}

{block name="frontend_product_finder_script"}

<script>

var json =  {$Data.json_facets},
    lang  = '{$Data.locale}',
    facets = new bxFacets(),
    selectedValues = {};
facets.init(json);

// Get the field name of the expert facet
var expertFieldName =  facets.getDataOwnerFacet();

  // get quickFinderCover

  var quickFinderCover = facets.getFacetExtraInfo(expertFieldName, 'quickFinderCover');

  // append Cover to Template

  jQuery('.centerContent').append('<img src=' + quickFinderCover + '>');

  // Returns all the experts

  var expertFacetValues = facets.getFacetValues(expertFieldName)[expertFieldName];


  // default expert

  var defaultExpert = null;
  expertFacetValues.forEach(function(value) {
      //checks each sommelier if set to default
      if(facets.getFacetValueExtraInfo(expertFieldName, value, 'is-initial')) {
          defaultExpert = value;
      }
  });

  // image for default expert & Intro

  var defaultExpertFirstName = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'first-name');
  var defaultExpertLastName  = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'last-name');
  var persona                = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'persona');
  var defaultExpertQuestionImage = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-img');
  var quickFinderIntro = facets.getFacetExtraInfo(expertFieldName, 'quickFinderIntro');

  // append default expert to template

  jQuery('.leftContent').append('<div class="img"><img src="https://' + defaultExpertQuestionImage + '" alt="" style="width:100%;border-radius: 50%"></div><div class="text"><p style="font-size: 0.8rem;">' + quickFinderIntro + '</p></div>');

  createButton();

  function createButton() {
      $('.button').append($('<button id="b-find" style="left:0;right:0;margin-right:auto;margin-left:auto;background-color: #993366; border:none; color:white; text-align:center;font-size: 0.7rem;">ZUM PRODUKTEFINDER</button>'));
      $('#b-find').on('click', function (e) {
          var urlString = '{url controller=cat sCategory=$Data.cpo_finder_link}',
              params = facets.getFacetParameters();
          params.forEach(function(param, index) {

              if(index > 0) {
                  urlString += '&';
              } else {
                  urlString += '?'
              }
              if(param.indexOf('=') === -1){
                  urlString += param + '=100';
              } else {
                  urlString += param;
              }
          });
          {*urlString = urlString.lastIndexOf('&') > -1 ? urlString + "&bx_cat=" + {$Data.category_id} : urlString + "?bx_cat=" + {$Data.category_id};*}
          window.location = urlString;
      });
  }

</script>

{/block}
