{block name="frontend_product_finder_content"}
<div class="wrapper" style="margin:0">

  <div class="left" style="width:25%; float:left;">

    <div class="leftContent" style="width:80%;left:0;right:0;margin-left:auto;margin-right:auto;">

    </div>

  </div>

  <div class="center" style="width:60%; float: left">

    <div class="centerContent" style="width: 100%;left:0;right:0;border: 1px solid; padding:10px">

      <div class="centerContentHeader" style="text-align:center;">

      </div>

      <div class="centerContentContainer" style="text-align:center;">

      </div>

      <div class="currentQuestionOptions" style="width:100%;">

      </div>

  </div>

  <div class="buttonContainer" style="height: 50px;">

  </div>

  <div class="buttonContainerBelow" style="height: 50px;">

  </div>

  </div>

  <div class="right" style="float:right;width:15%">

    <div class="rightContent" style="width:90%;margin-right:auto;margin-left:auto;right:0; left:0;">

      <div class="rightTitle" style="text-align:center;">

        <b style="border-bottom: 1px solid">Ihre Auswahl</b>

      </div>

      <div class="rightCriteria" style="padding-left: 15px;">

      </div>

    </div>

    </div>

</div>

<div class="cpo-finder-listing-wrapper">
        {block name="frontend_cpo_finder_listing_present"}
            {if $Data.top_match}
                <div class="cpo-finder-listing bx-present" style="display:none">
                    {foreach $Data.top_match as $sArticle}
                            {include file="frontend/listing/box_article.tpl" productBoxLayout='image'}
                    {/foreach}
                </div>
            {/if}
        {/block}
        {block name="frontend_cpo_finder_listing_listing"}
            {if $Data.highlighted_articles}
                {if $Data.top_match}
                    <div class="cpo-finder-listing bx-listing" style="display:none;">
                        {foreach $Data.highlighted_articles as $sArticle}
                            {include file="frontend/listing/box_article.tpl" productBoxLayout='emotion'}
                        {/foreach}
                    </div>
                {/if}
            {/if}
        {/block}
        {block name="frontend_cpo_finder_listing_question"}
            <div class="cpo-finder-listing" style="display:none;width:100%;float:left">
                {foreach $Data.sArticles as $sArticle}
                    {include file="frontend/listing/box_article.tpl" productBoxLayout='minimal'}
                {/foreach}
            </div>
        {/block}
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

    // check if data owner id selected

    if (facets.getCurrentSelects(expertFieldName) == null) {

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

      // Get the current facet

      var currentFacet = null;
      var questions = facets.getAdditionalFacets();
      for(var i = 0; i < questions.length; i++) {
          var fieldName = questions[i];
          if(facets.getCurrentSelects(fieldName) === null) {
              currentFacet = fieldName;
              break;
          }
      }

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

      // Returns selected expert
      var selectedExpert = facets.getCurrentSelects(expertFieldName)[0];

      // Get the current facet

      var currentFacet = null;
      var questions = facets.getAdditionalFacets();
      for(var i = 0; i < questions.length; i++) {
          var fieldName = questions[i];
          if(facets.getCurrentSelects(fieldName) === null) {
              currentFacet = fieldName;
              break;
          }
      }

      // Get question of the current facet for the expert

      var finderQuestion = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');

      // create selected expert

      var expertQuestionImg =   facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-img');
      var expertFirstName =     facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'first-name');
      var expertLastName =      facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'last-name');
      var expertPersona =       facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'persona');
      var expertExpertise =     facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'expertise');
      var expertIntroSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'intro-sentence');
      var expertResultSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-result-level10');
      var expertCharacteristics = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'characteristics');

      // get current facet values
      var currentFacetValues =   facets.getFacetValues(currentFacet)[currentFacet];

      // append expert to template

      jQuery('.leftContent').append('<div class="img"><img src="https://'+ expertQuestionImg + '" alt="" style="width:100%; border-radius: 50%"></div><div class="text"><div class="nameExpert"><h4>' + expertFirstName + ' ' + expertLastName + '</h4></div><div class="extraExpert" style="font-size: 0.7rem;"><div class="persona"><p>' + expertPersona[lang] + '</p></div><div class="expertise">Expert in:<div class="expertInAreas"><div class="expertInArea">' + expertExpertise[lang] + '</div><div class="characteristics" style="margin-top:5%; font-size:0.7rem;">'

      + expertCharacteristics[0]['alt-text'][lang] + ': ' + expertCharacteristics[0]['property'][lang] + '<br>'
      + expertCharacteristics[1]['alt-text'][lang] + ': ' + expertCharacteristics[1]['property'][lang] + '<br>'
      + expertCharacteristics[2]['alt-text'][lang] + ': ' + expertCharacteristics[2]['property'][lang] +

      '</div></div></div></div></div>');

      // append intro sentance

      if (currentFacet == null) {

        jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">"' + expertResultSentence[lang] + '"</h2>');

      }else {

        jQuery('.centerContentHeader').append('<h2 style="margin:0;margin-bottom: 5px;">"' + expertIntroSentence[lang] + '"</h2>');

      }

      // create fields

      createFields();

      // create button

      createButton();

      // createFields function

      function createFields(){

        container = jQuery('.currentQuestionOptions');

        var fieldContainer = jQuery('<div class="' + currentFacet + '" style="width:100%; display:inline-block;vertical-align:top"></div>');
        var facetValues = facets.getFacetValues(currentFacet)[currentFacet];
        var visualisation = facets.getFacetExtraInfo(currentFacet, 'visualisation');

        fieldContainer.append(createField(currentFacet, visualisation, facetValues));
        container.append(fieldContainer);

        facets.getFacets().forEach(function(fieldName) {
            createFieldListener(fieldName);
        });


      }

      // createField
      function createField(field, elementType, values) {
          var element = null,
              facetLabel = facets.getFacetExtraInfo(field, 'finderQuestion');
          switch(elementType) {
              case 'checkbox':

                element = $('<div><h4 style="margin:0">' + facetLabel + '</h4></div>');

                  values.forEach(function(value){

                  var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                  element.append($('<input class="' + field + '_check" ' + checked + ' name="' + value + '" type="checkbox" value="' + value + '">' + value + '</p></div></div>'));

                  });
                break;
              case 'multiselect':
                  element = $('<div><h4 style="margin:0">' + facetLabel + '</h4></div>');
                  values.forEach(function(value) {
                      var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo')[value];
                      var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                        element.append($('<div class="' + field + '" style="width:33%; display:inline-block;vertical-align:top"><div class="questionImg" style="width:90%; left:0;right:0;margin-right:auto;margin-left:auto;"><img src="https://' + facetExtraInfo['image'] + '" style="width:100%; border-radius: 50%"></div><div class="criteriaQuestionText" style="font-family:arial; font-size:14"><p style="text-align:center;"><input class="' + field + '_check" ' + checked + ' name="' + value + '" type="checkbox" value="' + value + '">' + facetExtraInfo['additional_text'] + '</p></div></div>'));
                  });
                  break;
              case 'radio':

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
              if(type == 'checkbox') {
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

      // create buttons

      function createButton() {

        // if current facet is set. Create all four buttons

        if (currentFacet) {

          jQuery('.buttonContainer').append('<button id="cpo-finder-back" type="button" name="backButton" style="background-color: #fff; border:none; color:black; text-align:center;float: left;margin-top:1%;">Zur&uuml;ck</button>');

          jQuery('.buttonContainer').append('<button id="cpo-finder-find" type="button" name="resultsButton" style="background-color: #993366; border:none; color:white; text-align:center;float: right;height:40px;width:130px;margin-top:1%;">WEITER</button>');

          jQuery('.buttonContainer').append('<button id="cpo-finder-skip" type="button" name="backButton" style="background-color: #707070; border:none; color:white; text-align:center;float: right;height:40px;width:130px;margin-top:1%;margin-right:1%;">&Uuml;BERSPRINGEN</button>');

          jQuery('.buttonContainerBelow').append('<button id="cpo-finder-show-products" style="background-color: #fff; border:none; color:black; text-align:center;float: right;margin-top: 1%;margin-right: 1%;">Ergebnisse anzeigen</button>');

        // otherwise only show back button and show the products

        }else{

          jQuery('.buttonContainer').append('<button id="cpo-finder-back" type="button" name="backButton" style="background-color: #fff; border:none; color:black; text-align:center;float: left;margin-top:1%;">Zur&uuml;ck</button>');

          $('.cpo-finder-listing-wrapper').find('.cpo-finder-listing').each(function (i, e) {
            $(e).show();
          });

        }

      }

    }

      // selected facets

      var selects = facets.getCurrentSelects();

      if (selects) {

        for(var key in selects){

          if (key != 'bxi_data_owner_expert' && selects[key] != '*') {

            jQuery('.rightCriteria').append(' - ' + selects[key] + '<br>');

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

    $('#cpo-finder-show-products').on('click', function (e) {
      $('.cpo-finder-listing-wrapper').find('.cpo-finder-listing').each(function (i, e) {
        $(e).show();
      });
    });

</script>

{/block}
