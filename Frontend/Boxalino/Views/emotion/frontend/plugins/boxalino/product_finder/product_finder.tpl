{block name="frontend_product_finder_content"}
<div class="wrapper" style="margin:0;">

  <div class="left" style="width:25%; float:left;">

    <div class="leftContent" style="width:80%;left:0;right:0;margin-left:auto;margin-right:auto;">

    </div>

  </div>

  <div class="center" style="width:60%; float: left">

    <div class="centerContent" style="width: 100%;left:0;right:0;border: 1px solid; padding:10px">

      <div class="centerContentHeader" style="">

      </div>

      <div class="centerContentContainer" style="text-align:center;">

      </div>

      <div class="currentQuestionOptions" style="width:100%;">

      </div>

      <div class="showMoreLess" style="width:100%;text-align:center;">

      </div>

  </div>

  <div class="buttonContainer" style="height: 50px;">

  </div>

  <div class="buttonContainerBelow" style="height: 50px;">

  </div>

  </div>

  <div class="right" style="float:right;width:15%">

    <div class="rightContent" style="width:100%;margin-right:auto;margin-left:auto;right:0; left:0;">

      <div class="rightTitle" style="text-align:center;">

        <b style="border-bottom: 1px solid">Ihre Auswahl</b>

      </div>

      <div class="rightCriteria" style="padding-left: 20px;">

      </div>

    </div>

    </div>

</div>
{* listing *}

<div class="listingBlock">

  <div class="cpo-finder-listing-wrapper" style="width:60%; float:right;">
    {block name="frontend_cpo_finder_listing_present"}
            <div class="cpo-finder-listing bx-present" style="display:none;">
                {foreach $Data.highlighted_articles as $sArticle}

                {include file="frontend/detail/content/header.tpl"}

                <div class="product--detail-upper block-group">
                    {* Product image *}
                    {block name='frontend_detail_index_image_container'}
                        <div class="product--image-container image-slider{if $sArticle.image && {config name=sUSEZOOMPLUS}} product--image-zoom{/if}"
                            {if $sArticle.image}
                            data-image-slider="true"
                            data-image-gallery="true"
                            data-maxZoom="{$theme.lightboxZoomFactor}"
                            data-thumbnails=".image--thumbnails"
                            {/if}>
                            {include file="frontend/detail/image.tpl"}
                        </div>
                    {/block}

                  </div>

                    {* "Buy now" box container *}
                    {include file="frontend/detail/content/buy_container.tpl" Shop = $Data.shop}
                {/foreach}
            </div>
    {/block}
    {block name="frontend_cpo_finder_listing_listing"}
         {* {if $Data.highlighted_articles} *}
                <div class="cpo-finder-listing bx-listing-emotion" style="display:none;">
                    {foreach $Data.sArticles as $sArticle}
                         {include file="frontend/listing/box_article.tpl" productBoxLayout='image' isFinder='true'}
                    {/foreach}
                </div>
        {* {/if} *}
    {/block}
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

    // Get the current facet

    var currentFacet = null;
    var questions = facets.getAdditionalFacets();
    if({$Data.highlighted} == false) {
      for(var i = 0; i < questions.length; i++) {
          var fieldName = questions[i];
          if(facets.getCurrentSelects(fieldName) === null) {
              currentFacet = fieldName;
              break;
          }
      }
    }

    // Get the field name of the expert facet
    var expertFieldName =  facets.getDataOwnerFacet();

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

    // check if data owner facet is selected
    if (currentFacet == expertFieldName) {

      // name, persona & image for default expert

      var defaultExpertFirstName = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'first-name');
      var defaultExpertLastName  = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'last-name');
      var persona                = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'persona');
      var defaultExpertQuestionImage = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-img');

      // append default expert to template

      jQuery('.leftContent').append('<div class="img"><img src="https://' + defaultExpertQuestionImage + '" alt="" style="width:100%;border-radius: 50%"></div><div class="text"><h4>' + defaultExpertFirstName + ' '  + defaultExpertLastName +'</h4><p>' + persona[lang] + '</p></div>');

      // create all other experts

      expertFacetValues.forEach(function(value) {

        var expertFirstName = facets.getFacetValueExtraInfo(expertFieldName, value, 'first-name');
        var expertLastName  = facets.getFacetValueExtraInfo(expertFieldName, value, 'last-name');
        var selectionImg    = facets.getFacetValueExtraInfo(expertFieldName, value, 'selection-img');
        var characteristics    = facets.getFacetValueExtraInfo(expertFieldName, value, 'characteristics');

        // append experts to template

        jQuery('.centerContentContainer').append('<div class="expert" style="width:33%; display:inline-block;vertical-align:top"><div class="expertimg" style="width:90%"><img src="https://'+selectionImg+'"  style="width:100%; border-radius: 50%"></div><div class="expertName"><h4 style="margin-bottom:0">' + expertFirstName + ' ' + expertLastName + '</h4></div><div class="expertCharacteristics" style="font-family:arial; font-size:14px; margin-bottom:5%;">'

        + characteristics[0]['alt-text'][lang] + ': ' + characteristics[0]['property'][lang] + '<br>'
        + characteristics[1]['alt-text'][lang] + ': ' + characteristics[1]['property'][lang] + '<br>'
        + characteristics[2]['alt-text'][lang] + ': ' + characteristics[2]['property'][lang] +

        '</div><div class="expertButton"><button id="' + expertFirstName + expertLastName + '_button" type="button" name="button" style="background-color: #993366; border:none; color:white; text-align:center;">ausw&auml;hlen ></button></div></div>');

        createFieldListener(value);

      });

      // get dataOwnerHeader

      var dataOwnerHeader = facets.getFacetExtraInfo(currentFacet, 'dataOwnerHeader');

      // append dataOwnerHeader

      jQuery('.centerContentHeader').append('<h2 style="margin:0;">"' + dataOwnerHeader + '"</h2>');

      // create field listener for data owner

      function createFieldListener(value){

        var expertFirstName = facets.getFacetValueExtraInfo(expertFieldName, value, 'first-name');
        var expertLastName  = facets.getFacetValueExtraInfo(expertFieldName, value, 'last-name');

        jQuery('#' + expertFirstName + expertLastName + '_button').on('click', function() {
              facets.removeSelect(expertFieldName);
              facets.addSelect(expertFieldName, value);
              var url = '{url controller=cat sCategory=$Data.cpo_finder_link}';
              var params = facets.getFacetParameters();
              params.forEach(function(param, index) {

                  if(index > 0) {
                      url += '&';
                  } else {
                      url += '?'
                  }
                  if(param.indexOf('=') === -1){
                      url += param + '=100';
                  } else {
                      url += param;
                  }
              });
              window.location = url;
          });
        }

    }else{

      // Get question of the current facet for the expert

      var finderQuestion = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');

      if (facets.getCurrentSelects(expertFieldName)) {

      // Returns selected expert
      var selectedExpert = facets.getCurrentSelects(expertFieldName)[0];

        // create selected expert

        var expertQuestionImg =   facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-img');
        var expertFirstName =     facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'first-name');
        var expertLastName =      facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'last-name');
        var expertPersona =       facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'persona');
        var expertExpertise =     facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'expertise');
        var expertIntroSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'intro-sentence');
        var expertQuestionSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-sentence');
        var expertCharacteristics = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'characteristics');

      }else {

        var expertQuestionImg =   facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-img');
        var expertFirstName =     facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'first-name');
        var expertLastName =      facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'last-name');
        var expertPersona =       facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'persona');
        var expertExpertise =     facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'expertise');
        var expertIntroSentence = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'intro-sentence');
        var expertQuestionSentence = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'question-sentence');
        var expertCharacteristics = facets.getFacetValueExtraInfo(expertFieldName, defaultExpert, 'characteristics');

      }

      // get current facet values
      var currentFacetValues =   facets.getFacetValues(currentFacet)[currentFacet];

      // append expert to template

      jQuery('.leftContent').append('<div class="img"><img src="https://'+ expertQuestionImg + '" alt="" style="width:100%; border-radius: 50%"></div><div class="text"><div class="nameExpert"><h4>' + expertFirstName + ' ' + expertLastName + '</h4></div><div class="extraExpert" style="font-size: 0.7rem;"><div class="persona"><p>' + expertPersona[lang] + '</p></div><div class="expertise">Expert in:<div class="expertInAreas"><div class="expertInArea">' + expertExpertise[lang] + '</div><div class="characteristics" style="margin-top:5%; font-size:0.7rem;">'

      + expertCharacteristics[0]['alt-text'][lang] + ': ' + expertCharacteristics[0]['property'][lang] + '<br>'
      + expertCharacteristics[1]['alt-text'][lang] + ': ' + expertCharacteristics[1]['property'][lang] + '<br>'
      + expertCharacteristics[2]['alt-text'][lang] + ': ' + expertCharacteristics[2]['property'][lang] +

      '</div></div></div></div></div>');

      // append intro sentance

      if (currentFacet != null) {

        facetLabel = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');

        // only show intro sentance on the first question

        if (currentFacet == questions[1]) {

          jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">' + expertIntroSentence[lang] + '</h2>');
          jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">' + facetLabel + '</h2>');

        }

        else {

          // otherwise show question header

          jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">' + expertQuestionSentence[lang] + '</h2>');
          jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">' + facetLabel + '</h2>');

        }

      }

      // create fields

      createFields();

      // create button

      createButton();

      // createFields function

      function createFields(){

        container = jQuery('.currentQuestionOptions');

        var fieldContainer = jQuery('<div class="' + currentFacet + '_container" style="width:100%; display:inline-block;vertical-align:top"></div>');
        var facetValues = facets.getFacetValues(currentFacet)[currentFacet];
        var visualisation = facets.getFacetExtraInfo(currentFacet, 'visualisation');

        fieldContainer.append(createField(currentFacet, visualisation, facetValues));
        container.append(fieldContainer);

        facets.getFacets().forEach(function(fieldName) {
            createFieldListener(fieldName);
        });

        // only show as many as defined

        var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');

        if (displaySize) {
          $('.' + currentFacet + ':gt(' + (displaySize - 1) + ')').hide();
        }


      }

      // createField
      function createField(field, elementType, values) {
          var element = null,
              facetLabel = facets.getFacetExtraInfo(field, 'finderQuestion');
          switch(elementType) {
              case 'checkbox':

                element = $('<div></div>');

                  values.forEach(function(value){

                  var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                  element.append($('<input class="' + field + '_check" ' + checked + ' name="' + field + '" type="checkbox" value="' + value + '">' + value + '</p></div></div>'));

                  });
                break;
              case 'multiselect':
                  element = $('<div></div>');

                  var displayMode = facets.getFacetExtraInfo(field, 'display-mode');
                  var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo');

                  if (displayMode == 'imageWithLabel') {
                  values.forEach(function(value) {
                      var facetExtraInfoValues = facetExtraInfo[value];
                      var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                        element.append($('<div class="' + field + '" style="width:33%; display:inline-block;vertical-align:top"><div class="questionImg" style="width:90%; left:0;right:0;margin-right:auto;margin-left:auto;"><img src="https://' + facetExtraInfoValues['image'] + '" style="width:100%;"></div><div class="criteriaQuestionText" style="font-family:arial; font-size:14"><p style="text-align:left;"><input class="' + field + '_check" ' + checked + ' name="' + value + '" type="checkbox" value="' + value + '">' + facetExtraInfoValues['additional_text'] + '</p></div></div>'));
                  });
                  }

                  else {

                    values.forEach(function(value) {
                        var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                          element.append($('<div class="' + field + '" style="width:33%; display:inline-block;vertical-align:top"><div class="questionImg" style="width:90%; left:0;right:0;margin-right:auto;margin-left:auto;"></div><div class="criteriaQuestionText" style="font-family:arial; font-size:14"><p style="text-align:left;"><input class="' + field + '_check" ' + checked + ' name="' + value + '" type="checkbox" value="' + value + '">' + value + '</p></div></div>'));
                    });

                  }

                  break;
              case 'radio':

              element = $('<form style="background-color:#fff;"></form>');

              var displayMode = facets.getFacetExtraInfo(field, 'display-mode');
              var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo');

              if (displayMode == 'imageWithLabel') {
              values.forEach(function(value) {
                  var facetExtraInfoValues = facetExtraInfo[value];
                  var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                    element.append($('<div class="' + field + '" style="width:33%; display:inline-block;vertical-align:top"><div class="questionImg" style="width:90%; left:0;right:0;margin-right:auto;margin-left:auto;"><img src="https://' + facetExtraInfoValues['image'] + '" style="width:100%; "></div><div class="criteriaQuestionText" style="font-family:arial; font-size:14"><p style="text-align:left;"><input class="' + field + '_check" ' + checked + ' name="' + field + '" type="checkbox" value="' + value + '">' + facetExtraInfoValues['additional_text'] + '</p></div></div>'));
              });
              }

              else if (displayMode == 'onlyLabel'){

                values.forEach(function(value) {
                    var facetExtraInfoValues = facetExtraInfo[value];
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                      element.append($('<div class="' + field + '" style="width:33%; display:inline-block;vertical-align:top"><div class="criteriaQuestionText" style="font-family:arial; font-size:14"><p style="text-align:left;"><input class="' + field + '_check" ' + checked + ' name="' + field + '" type="checkbox" value="' + value + '">' + facetExtraInfoValues['additional_text'] + '</p></div></div>'));
                });

              }

              else {

                values.forEach(function(value) {
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                      element.append($('<p style="text-align:left;display: inline-block;width: 33%;"><input class="' + field + '_check" ' + checked + ' name="' + field + '" type="radio" value="' + value + '">' + value + '</p>'));
                });

              }

                  break;
              case 'dropdown':
                  element = $('<select class='+field+' name='+field+'></select>'),
                      selectedValue = selectedValues.hasOwnProperty(field) ? selectedValues[field] : null;
                  element.append($('<option value="" disabled selected >Bitte wählen Sie aus.</option>'));
                  values.forEach(function(value) {
                      var optionElement = $('<option></option>').attr("value", value).text(value);
                      if(facets.isFacetValueSelected(field, value)) {
                          optionElement.attr('selected', 'selected');
                      }
                      element.append(optionElement);
                  });
                  element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
                  break;
              case 'slider':
                  var res = values[0].split("-"),
                      selectedValue = facets.getCurrentSelects(field) === null ? res : facets.getCurrentSelects(field)[0].split("-");
                  var activeMin = selectedValue[0],
                      activeMax = selectedValue[1];
                  element = $('<div class="range-slider" data-range-slider="true" data-roundPretty="false" data-labelFormat="" data-stepCount="100" data-stepCurve="linear" data-startMin="'+activeMin+'" data-startMax="'+activeMax+'" data-rangeMin="'+res[0]+'" data-rangeMax="'+res[1]+'"></div>');
                  element.append($('<input type="hidden" id="min" name="min" data-range-input="min" value="'+res[0]+'"/>'));
                  element.append($('<input type="hidden" id="max" name="max" data-range-input="max" value="'+res[1]+'"/>'));
                  element.append($('<div class="filter-panel--range-info"> <span class="range-info--min">{s name="ListingFilterRangeFrom" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="min" data-range-label="min"> '+activeMin+'</label><span class="range-info--max"> {s name="ListingFilterRangeTo" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="max" data-range-label="max"> '+activeMax+'</label></div>'));
              case 'enumeration':
                  element = $('<div><div>');
                  values.forEach(function(value) {
                      var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                      element.append( $("<label>"+value+"</label><input class='"+field+"' "+checked+" type='radio' name='"+field+"' value='"+value+"'>"));
                  });
                  element = $('<span><strong>'+facetLabel+'</strong></span><br />').add(element);
              default:
                  break;
          }
          return element;
      }

      // createFieldListener

      function createFieldListener(field) {
          var type = facets.getFacetExtraInfo(field, 'visualisation');
          $("." + field + "_check").on('change', function() {
              if(type == 'radio') {
                facets.removeSelect(field);
                  if($(this).is(':checked')) {
                      facets.addSelect(field, $(this).attr('value'));
                  } else {
                      facets.removeSelect(field);
                  }
              } else if (type == 'multiselect') {
                  if($(this).is(':checked')) {
                      facets.addSelect(field, $(this).attr('value'));
                  } else {
                      facets.removeSelect(field, $(this).attr('value'));
                  }
              } else {
                  facets.removeSelect(field);
                  facets.addSelect(field, $(this).attr('value'));
              }
              update();
          });
      }

      // update function

      function update() {
          var fields = facets.getUpdatedValues();
          for(var fieldName in fields) {
              var select = $('.'+fieldName).empty();
              var optionValues = fields[fieldName];
              optionValues.forEach(function(optionValue) {
                  select.append($('<option></option>').attr("value", optionValue).text(optionValue));
              });
          }
      }

        // selected facets

        var selects = facets.getCurrentSelects();

        jQuery('.rightCriteria').append('<br>');

        if (selects) {

          for(var key in selects){

            if (key != 'bxi_data_owner_expert' && selects[key] != '*') {

              // facet Label

              facetLabel = facets.getFacetLabel(key, lang);

              jQuery('.rightCriteria').append('<b>' + facetLabel + '</b><br>');

              // if there is additional info, use that

              facetExtraInfo = facets.getFacetExtraInfo(key, 'facetValueExtraInfo');

              if (facetExtraInfo) {

                jQuery('.rightCriteria').append('<a class="' + key + '"><p id="' + selects[key] +'" style="margin: 0;">- ' + facetExtraInfo[selects[key]]['additional_text'] + '</p></a>');

              }

              // otherwise use value from DI

              else {

                selects[key].forEach(function (key){

                jQuery('.rightCriteria').append('<a class="' + key + '"><p id="' + key +'" style="margin: 0;">- ' + key + '</p></a>');

                });

              }

            }

            // return to questions

            url = window.location.href;

            jQuery('#' + selects[key]).click(function(){

              var classname = jQuery(this).parent().attr('class');

              var urlParams = new URLSearchParams(window.location.search);

              var entries = urlParams.entries();
              for(pair of entries) {
                pair = pair.join('=')
                if(pair.includes(classname)){

                  newUrl = url.replace(pair, '');
                  newUrl = url.replace('&&', '&');

                  window.location = newUrl;

                }
              }

            });

          }

        }

      // create buttons

      function createButton() {

        // if current facet is set. Create all buttons

        if (currentFacet) {

          var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');
          var facetValues = facets.getFacetValues(currentFacet)[currentFacet];

          // if maxsize is defined add the show more/less buttons

          if (displaySize && displaySize < facetValues.length) {

            jQuery('.showMoreLess').append('<button id="cpo-finder-additional" type="button" name="additionalButton" style="background-color: #fff; border:none; color:black; text-align:center;">Mehr Anzeigen</button>');

            jQuery('.showMoreLess').append('<button id="cpo-finder-fewer" type="button" name="fewerButton" style="background-color: #fff; border:none; color:black; text-align:center;display:none">Weniger Anzeigen</button>');

          }

          // create other buttons

          jQuery('.buttonContainer').append('<button id="cpo-finder-back" type="button" name="backButton" style="background-color: #fff; border:none; color:black; text-align:center;float: left;margin-top:1%;">Zur&uuml;ck</button>');

          jQuery('.buttonContainer').append('<button id="cpo-finder-find" type="button" name="resultsButton" style="background-color: #993366; border:none; color:white; text-align:center;float: right;height:40px;width:130px;margin-top:1%;">WEITER</button>');

          jQuery('.buttonContainer').append('<button id="cpo-finder-skip" type="button" name="backButton" style="background-color: #707070; border:none; color:white; text-align:center;float: right;height:40px;width:130px;margin-top:1%;margin-right:1%;">&Uuml;BERSPRINGEN</button>');

          jQuery('.buttonContainerBelow').append('<button id="cpo-finder-show-products" style="background-color: #fff; border:none; color:black; text-align:center;float: right;margin-top: 1%;margin-right: 1%;">Ergebnisse bis ' + {$Data.max_score} + '% anzeigen</button>');

        // otherwise only show back button and show the products

        }else{

          // comment

          var button = jQuery('[class^=bxCommentButton_]');

          button.click(function(){

            jQuery(this).hide();
            jQuery(this).next().show();

          });

          jQuery('.buttonContainer').append('<button id="cpo-finder-back" type="button" name="backButton" style="background-color: #fff; border:none; color:black; text-align:center;float: left;margin-top:1%;">Zur&uuml;ck</button>');

          jQuery('.buttonContainerBelow').hide();

          var expertResultSentence10 = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-result-level10');
          var expertResultSentence5  = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-results-level5');
          var expertResultSentence1  = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-results-level1');

          var selects = facets.getCurrentSelects();
          var count = 0;

          if (selects) {
            for(var key in selects){
              if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
                count++;
              }
            }
          }

          if ({$Data.max_score} >= 90) { //if highlighted == true

            jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">' + expertResultSentence10[lang] + '</h2>');
            $('.bx-present').show();
            $('.bx-listing-emotion').show();

          }

          else {

            jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">' + expertResultSentence5[lang] + '</h2>');
            $('.bx-listing-emotion').show();

          }

        }

      }

    }

      // find button logic

      $('#cpo-finder-find').on('click', function (e) {
        var url = '{url controller=cat sCategory=$Data.cpo_finder_link}',
            params = facets.getFacetParameters(),
            paramString = '',
            prefix = facets.getParameterPrefix();
            contextPrefix = facets.getContextParameterPrefix();
        params.forEach(function(param, index) {
            if(index > 0) {
                paramString += '&';
            } else {
                paramString += '?'
            }
            if(param.indexOf('=') === -1){
                paramString += param + '=100';
            } else {
                paramString += param;
            }
        });
        window.location.search.substr(1).split("&").forEach(function(param, index) {
            if(param.indexOf(prefix) !== 0 && param.indexOf(contextPrefix) !== 0) {
                bind = ((paramString === '') ? (index > 0 ? '&' : '?') : '&');
                paramString += bind + param;
            }
        });
        window.location = url + paramString;
    });

    // show more/less button logic

    var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');
    $('.' + currentFacet + ':gt(' + (displaySize - 1) + ')').hide();

    $('#cpo-finder-additional').on('click', function (e){

      $('.' + currentFacet).show();

      $('#cpo-finder-additional').hide();
      $('#cpo-finder-fewer').show();

    });

    $('#cpo-finder-fewer').on('click', function (e){

      $('.' + currentFacet + ':gt(' + (displaySize - 1) + ')').hide();

      $('#cpo-finder-fewer').hide();
      $('#cpo-finder-additional').show();

    });

    // skip button logic

      $('#cpo-finder-skip').on('click', function (e) {

        facets.removeSelect(currentFacet);
        facets.addSelect(currentFacet, '*');

        var url = '{url controller=cat sCategory=$Data.cpo_finder_link}',
            params = facets.getFacetParameters(),
            paramString = '',
            prefix = facets.getParameterPrefix();
            contextPrefix = facets.getContextParameterPrefix();

        params.forEach(function(param, index) {
            if(index > 0) {
                paramString += '&';
            } else {
                paramString += '?'
            }
            if(param.indexOf('=') === -1){
                paramString += param + '=100';
            } else {
                paramString += param;
            }
        });
        window.location.search.substr(1).split("&").forEach(function(param, index) {
            if(param.indexOf(prefix) !== 0 && param.indexOf(contextPrefix) !== 0) {
                bind = ((paramString === '') ? (index > 0 ? '&' : '?') : '&');
                paramString += bind + param;
            }
        });

        window.location = url + paramString;

      });

      // back button logic

      $('#cpo-finder-back').on('click', function (e) {

        window.history.back()

      });

    // show products button logic

    $('#cpo-finder-show-products').on('click', function () {

      var selects = facets.getCurrentSelects();
      var count = 0;

      if (selects) {
        for(var key in selects){
          if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
            count++;
          }
        }
      }


      if ({$Data.max_score} >= 90) {

        $('.bx-present').show();
        $('.bx-listing-emotion').show();
        // $('.bx-listing-listing').show();

      }

      else {

        $('.bx-listing-emotion').show();
        // $('.bx-listing-listing').show();

      }

    });

</script>

{/block}
