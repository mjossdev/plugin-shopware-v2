var json =  {$Data.json_facets},
lang  = '{$Data.locale}',
    url = '{url controller=cat sCategory=$Data.cpo_finder_link}',
    facets = new bxFacets(),
    max_score = {$Data.max_score},
highlighted = {$Data.highlighted},
selectedValues = {};
var alertString = "{s namespace="boxalino/intelligence" name="productfinder/alertString" default="Bitte beide Fragen beantworten"}{/s}";
facets.init(json);

// Get the current facet
var currentFacet = null;
var questions = facets.getAdditionalFacets();
if (highlighted == false) {
    // remove questions with no options
    questions.forEach(function(question){
        if (facets.getFacetValues(question)[question].length === 0) {
            var index = questions.indexOf(question);
            if (index > -1) {
                questions.splice(index, 1);
            }
        }
    });

    for (var i = 0; i < questions.length; i++) {
        var fieldName = questions[i];
        if (facets.getCurrentSelects(fieldName) === null) {
            if(facets.getFacetValues(fieldName)[fieldName].length < 2){
                continue;
            }
            currentFacet = fieldName;
            break;
        }
    }
}

// Show comment
jQuery('.cpo-finder-listing-comment-button').on('click', function() {
    if (jQuery(this).hasClass('comment-active') ){
        jQuery(this).removeClass('comment-active');
        currentArticleID = '.cpo-finder-listing-comment-' + jQuery(this).attr('articleid');
        jQuery(currentArticleID).hide();
    } else {
        jQuery(this).addClass('comment-active');
        currentArticleID = '.cpo-finder-listing-comment-' + jQuery(this).attr('articleid');
        jQuery(currentArticleID).show();
    }
});

// selected facets
var selects = facets.getCurrentSelects();
jQuery('.cpo-finder-right-criteria').append('<br>');
if (selects) {
    var bxUrl = window.location.href;
    var bxNewUrl = '';
    var bxUrlParamString = window.location.search.substring(1);
    var bxUrlParams = bxUrlParamString.split('&');
    var s = '';
    for (var key in selects) {
        if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
            bxUrlParams.forEach(function(param) {
                if (param.includes(key)) {
                    bxNewUrl = bxUrl.substring(0, bxUrl.indexOf(param));
                }
            });
            prefix = window.location.protocol + '//' + window.location.hostname + '/';
            bxNewUrl = bxNewUrl.replace(prefix, '');
            // facet Label
            facetLabel = facets.getFacetLabel(key, lang);
            jQuery('.cpo-finder-right-criteria').append('<b class="bx-finder-filter-label">' + facetLabel + '</b><br>');
            // if there is additional info, use that
            facetExtraInfo = facets.getFacetExtraInfo(key, 'facetValueExtraInfo');
            if (facetExtraInfo && facetExtraInfo[selects[key]]) {
                jQuery('.cpo-finder-right-criteria').append('<a href="' + bxNewUrl + '" class="' + key + ' bx-finder-filter-selected"><p id="' + selects[key] + '" class="bx-finder-filter-selected-value">- ' + facetExtraInfo[selects[key]]['additional_text'] + '</p></a>');
            }
            // otherwise use value from DI
            else {
                selects[key].forEach(function(key) {
                    jQuery('.cpo-finder-right-criteria').append('<a href="' + bxNewUrl + '" class="' + key + ' bx-finder-filter-selected"><p id="' + key + '" class="bx-finder-filter-selected-value">- ' + key + '</p></a>');
                });
            }
        }
    }
}

if (questions[0] == currentFacet && facets.getCurrentSelects().length == undefined && facets.getCurrentSelects(questions[1]) == null) {
    var combinedQuestions = [questions[0], questions[1]]
}

var expertFieldName = facets.getDataOwnerFacet(); // Get the field name of the expert facet
var expertFacetValues = facets.getFacetValues(expertFieldName)[expertFieldName]; // Returns all the experts
var expertFieldOrder = questions.findIndex(isExpertQuestion);
var defaultExpert = null; // default expert
expertFacetValues.forEach(function(value) {
    if (facets.getFacetValueExtraInfo(expertFieldName, value, 'is-initial')) {
        defaultExpert = value;
    }
});

if (currentFacet == expertFieldName) {
    createExpert(".cpo-finder-left-content", expertHtml, defaultExpert);
    expertFacetValues.forEach(function(value) {
        if (facets.getFacetValueExtraInfo(expertFieldName, value, 'is-initial') != true) {
            createExpert(".cpo-finder-center-content-container", expertListHtml, value);
        }
        createExpertFieldListener(value);
    });

    addIntroMessage(defaultExpert);
    createButton();
} else {
    if (combinedQuestions) {
        jQuery('.cpo-finder-right-content').hide();
        combinedQuestions.forEach(function(temp) {
            var tempFacetValues = facets.getFacetValues(temp)[temp];
        });
    }
    var finderQuestion = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');
    var selectedExpert = createExpert(".cpo-finder-left-content", expertLeftHtml);

    addIntroMessage(selectedExpert);
    createFields();
    createButton();
}

function createExpertFieldListener(value) {
    var expertFirstName = facets.getFacetValueExtraInfo(expertFieldName, value, 'first-name').replace(' ', '');
    var expertLastName = facets.getFacetValueExtraInfo(expertFieldName, value, 'last-name');
    jQuery('#' + expertFirstName + expertLastName + '_button').on('click', function() {
        facets.removeSelect(expertFieldName);
        facets.addSelect(expertFieldName, value);
        var params = facets.getFacetParameters();
        params.forEach(function(param, index) {
            if (index > 0) {
                url += '&';
            } else {
                url += '?'
            }
            if (param.indexOf('=') === -1) {
                url += param + '=100';
            } else {
                url += param;
            }
        });
        window.location = url;
    });
}

function addIntroMessage(selectedExpert) {
    var expertIntroSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'intro-sentence');
    var expertQuestionSentence = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-sentence');
    if (currentFacet != null) {
        facetLabel = facets.getFacetExtraInfo(currentFacet, 'finderQuestion');
        if(currentFacet == expertFieldName) {
            var dataOwnerHeader = facets.getFacetExtraInfo(currentFacet, 'dataOwnerHeader');
            jQuery('.cpo-finder-center-content-header').append(dataOwnerHeader);
        } else if (currentFacet == questions[expertFieldOrder+1]) {
            jQuery('.cpo-finder-center-content-header').append(expertIntroSentence[lang]);
            jQuery('.cpo-finder-center-content-header-question-first').append(facetLabel);
            if (combinedQuestions) {
                jQuery('.cpo-finder-center-content-header-question-second').append(facets.getFacetExtraInfo(combinedQuestions[1], 'finderQuestion'));
            }
        } else {
            jQuery('.cpo-finder-center-content-header').append(expertQuestionSentence[lang]);
            jQuery('.cpo-finder-center-content-header-question-first').append(facetLabel);
            if (combinedQuestions) {
                jQuery('.cpo-finder-center-content-header-question-second').append(facets.getFacetExtraInfo(combinedQuestions[1], 'finderQuestion'));
            }
        }
    }
}

function createExpert(locationClass, templateHtml, selectedExpert="") {
    if(selectedExpert != "") {
        selectedExpert = selectedExpert;
    } else if(facets.getCurrentSelects(expertFieldName)) {
        selectedExpert = facets.getCurrentSelects(expertFieldName)[0];
    } else {
        selectedExpert = defaultExpert;
    }

    var expertQuestionImg = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'question-img');
    var expertFirstName = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'first-name').replace(' ', '');
    var expertLastName = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'last-name');
    var expertPersona = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'persona');
    var expertExpertise = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'expertise');
    var expertCharacteristics = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'characteristics');
    var selectionImg = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'selection-img');

    jQuery(locationClass).append(
        templateHtml.replace('%%ExpertFirstName%%', expertFirstName)
            .replace('%%ExpertLastName%%', expertLastName)
            .replace('%%ExpertFirstName%%', expertFirstName)
            .replace('%%ExpertLastName%%', expertLastName)
            .replace('%%ExpertPersona%%', expertPersona[lang])
            .replace('%%ExpertExpertise%%', expertExpertise[lang])
            .replace('%%Characteristics0%%', expertCharacteristics[0]['alt-text'][lang])
            .replace('%%Characteristics0Value%%', expertCharacteristics[0]['property'][lang])
            .replace('%%Characteristics1%%', expertCharacteristics[1]['alt-text'][lang])
            .replace('%%Characteristics1Value%%', expertCharacteristics[1]['property'][lang])
            .replace('%%Characteristics2%%', expertCharacteristics[2]['alt-text'][lang])
            .replace('%%Characteristics2Value%%', expertCharacteristics[2]['property'][lang])
            .replace('%%ExpertQuestionImage%%', expertQuestionImg)
            .replace('%%ExpertSelectionImage%%', selectionImg)
    );

    return selectedExpert;
}


function createFields() {
    if (combinedQuestions) {
        container = jQuery('.cpo-finder-center-current-question-options-second');

        var fieldContainer = jQuery('<div class="' + combinedQuestions[1] + '_container cpo-finder-answers-container-second"></div>');
        var facetValues = facets.getFacetValues(combinedQuestions[1])[combinedQuestions[1]];
        var visualisation = facets.getFacetExtraInfo(combinedQuestions[1], 'visualisation');

        fieldContainer.append(createField(combinedQuestions[1], visualisation, facetValues));
        container.append(fieldContainer);

        facets.getFacets().forEach(function(fieldName) {
            createFieldListener(fieldName);
        });

        // only show as many as defined
        var displaySize = facets.getFacetExtraInfo(combinedQuestions[1], 'enumDisplayMaxSize');
        var secondDisplaySize = facets.getFacetExtraInfo(combinedQuestions[0], 'enumDisplayMaxSize');
        if(secondDisplaySize == null){
            secondDisplaySize = facets.getFacetValues(currentFacet)[currentFacet].length;
        }
        var combinedDisplaySize = parseInt(secondDisplaySize) + parseInt(displaySize);

        if (displaySize) {
            $('.cpo-finder-answer:gt(' + (displaySize - 1) + ')').hide();
        }
        jQuery('.cpo-finder-center-show-more-less').append(additionalButton);
        jQuery('.cpo-finder-center-show-more-less').append(fewerButton);
        $('#cpo-finder-additional').on('click', function(e) {
            $('.cpo-finder-answers-container-second cpo-finder-answer').show();
            $('#cpo-finder-additional').hide();
            $('#cpo-finder-fewer').show();
        });
        $('#cpo-finder-fewer').on('click', function(e) {
            $('.cpo-finder-answer:gt(' + (combinedDisplaySize - 1) + ')').hide();
            $('#cpo-finder-fewer').hide();
            $('#cpo-finder-additional').show();
        });
    }

    var fieldContainer = jQuery('<div class="' + currentFacet + '_container cpo-finder-answers-container"></div>');
    var facetValues = facets.getFacetValues(currentFacet)[currentFacet];
    var visualisation = facets.getFacetExtraInfo(currentFacet, 'visualisation');
    fieldContainer.append(createField(currentFacet, visualisation, facetValues));

    container = jQuery('.cpo-finder-center-current-question-options');
    container.append(fieldContainer);

    facets.getFacets().forEach(function(fieldName) {
        createFieldListener(fieldName);
    });

    // only show as many as defined
    var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');
    if (displaySize) {
        $('.cpo-finder-answer:gt(' + (displaySize - 1) + ')').hide();
    }
}

// createField
function createField(field, elementType, values) {
    var element = null,
        facetLabel = facets.getFacetExtraInfo(field, 'finderQuestion');
    switch (elementType) {
        case 'multiselect':
            element = $('<div></div>');
            var displayMode = facets.getFacetExtraInfo(field, 'display-mode');
            var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo');
            if (displayMode == 'imageWithLabel') {
                values.forEach(function(value) {
                    var facetExtraInfoValues = facetExtraInfo[value];
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                    element.append($(checkboxImageWithLabel.replace('%%FieldValue%%', field)
                        .replace('%%AnswerImage%%', facetExtraInfoValues['image'])
                        .replace('%%AnswerText%%', facetExtraInfoValues['additional_text'])
                        .replace('%%AnswerCheckboxName%%', value)
                        .replace('%%AnswerCheckboxValue%%', value)
                        .replace('%%AnswerCheckboxChecked%%', checked)));
                });
            } else {
                values.forEach(function(value) {
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                    element.append($(checkboxLabelWithoutImage.replace('%%FieldValue%%', field)
                        .replace('%%AnswerText%%', value)
                        .replace('%%AnswerCheckboxName%%', field)
                        .replace('%%AnswerCheckboxValue%%', value)
                        .replace('%%AnswerCheckboxChecked%%', checked)));
                });
            }
            break;
        case 'radio':
            element = $('<form></form>');
            var displayMode = facets.getFacetExtraInfo(field, 'display-mode');
            var facetExtraInfo = facets.getFacetExtraInfo(field, 'facetValueExtraInfo');
            if (displayMode == 'imageWithLabel') {
                values.forEach(function(value) {
                    var facetExtraInfoValues = facetExtraInfo[value];
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                    element.append($(radioImageWithLabel.replace('%%FieldValue%%', field)
                        .replace('%%AnswerImage%%', facetExtraInfoValues['image'])
                        .replace('%%AnswerText%%', facetExtraInfoValues['additional_text'])
                        .replace('%%AnswerCheckboxName%%', field)
                        .replace('%%AnswerCheckboxValue%%', value)
                        .replace('%%AnswerCheckboxChecked%%', checked)));
                });
            } else {
                values.forEach(function(value) {
                    var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                    element.append($(radioLabelWithoutImage.replace('%%FieldValue%%', field)
                        .replace('%%AnswerText%%', value)
                        .replace('%%AnswerCheckboxName%%', value)
                        .replace('%%AnswerCheckboxValue%%', value)
                        .replace('%%AnswerCheckboxChecked%%', checked)));
                });
            }
            break;
        case 'dropdown':
            element = $('<select class=' + field + ' name=' + field + '></select>'),
                selectedValue = selectedValues.hasOwnProperty(field) ? selectedValues[field] : null;
            element.append($('<option value="" disabled selected >Bitte wählen Sie aus.</option>'));
            values.forEach(function(value) {
                var optionElement = $('<option></option>').attr("value", value).text(value);
                if (facets.isFacetValueSelected(field, value)) {
                    optionElement.attr('selected', 'selected');
                }
                element.append(optionElement);
            });
            element = $('<span><strong>' + facetLabel + '</strong></span><br />').add(element);
            break;
        case 'slider':
            var res = values[0].split("-"),
                selectedValue = facets.getCurrentSelects(field) === null ? res : facets.getCurrentSelects(field)[0].split("-");
            var activeMin = selectedValue[0],
                activeMax = selectedValue[1];
            element = $('<div class="range-slider" data-range-slider="true" data-roundPretty="false" data-labelFormat="" data-stepCount="100" data-stepCurve="linear" data-startMin="' + activeMin + '" data-startMax="' + activeMax + '" data-rangeMin="' + res[0] + '" data-rangeMax="' + res[1] + '"></div>');
            element.append($('<input type="hidden" id="min" name="min" data-range-input="min" value="' + res[0] + '"/>'));
            element.append($('<input type="hidden" id="max" name="max" data-range-input="max" value="' + res[1] + '"/>'));
            element.append($('<div class="filter-panel--range-info"> <span class="range-info--min">{s name="ListingFilterRangeFrom" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="min" data-range-label="min"> ' + activeMin + '</label><span class="range-info--max"> {s name="ListingFilterRangeTo" namespace="frontend/listing/listing_actions"}{/s}</span><label class="range-info--label" for="max" data-range-label="max"> ' + activeMax + '</label></div>'));
        case 'enumeration':
            element = $('<div><div>');
            values.forEach(function(value) {
                var checked = facets.isFacetValueSelected(field, value) ? "checked='checked'" : "";
                element.append($("<label>" + value + "</label><input class='" + field + "' " + checked + " type='radio' name='" + field + "' value='" + value + "'>"));
            });
            element = $('<span><strong>' + facetLabel + '</strong></span><br />').add(element);
        default:
            break;
    }
    return element;
}

// createFieldListener
function createFieldListener(field) {
    var type = facets.getFacetExtraInfo(field, 'visualisation');
    $("." + field + "_check").on('change', function() {
        if (type == 'radio') {
            facets.removeSelect(field);
            if ($(this).is(':checked')) {
                facets.addSelect(field, $(this).attr('value'));
            } else {
                facets.removeSelect(field);
            }
        } else if (type == 'multiselect') {
            if ($(this).is(':checked')) {
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
    for (var fieldName in fields) {
        var select = $('.' + fieldName).empty();
        var optionValues = fields[fieldName];
        optionValues.forEach(function(optionValue) {
            select.append($('<option></option>').attr("value", optionValue).text(optionValue));
        });
    }
}

/**
 * if current facet is set. Create all buttons
 *  otherwise only show back button and show the products
 */
function createButton() {
    if (currentFacet) {
        var displaySize = facets.getFacetExtraInfo(currentFacet, 'enumDisplayMaxSize');
        var facetValues = facets.getFacetValues(currentFacet)[currentFacet];

        // if maxsize is defined add the show more/less buttons
        if (displaySize && displaySize < facetValues.length) {
            jQuery('.cpo-finder-center-show-more-less').append(additionalButton);
            jQuery('.cpo-finder-center-show-more-less').append(fewerButton);
        }

        jQuery('.cpo-finder-additional').on('click', function() {
            jQuery('.cpo-finder-fewer').show()
            jQuery('.cpo-finder-additional').hide()
        })

        jQuery('.cpo-finder-fewer').on('click', function() {
            jQuery('.cpo-finder-additional').show()
            jQuery('.cpo-finder-fewer').hide()
        })

        // create other buttons
        if (questions[0] != currentFacet) {
            jQuery('.cpo-finder-button-container').append(backButton);
        }
        var visualisation = facets.getFacetExtraInfo(currentFacet, 'visualisation');
        jQuery('.cpo-finder-button-container').append(resultsButton);
        jQuery('.cpo-finder-button-container').append(skipButton);
        if(max_score > 0 && questions[0]!= currentFacet && questions[1]!= currentFacet && currentFacet != expertFieldName) {
            jQuery('.cpo-finder-button-container-below').append(showProductsButton.replace('%%CurrentScore%%', max_score));
        }
    } else {
        jQuery('.cpo-finder-button-container').append(backButton);
        jQuery('.cpo-finder-button-container-below').hide();

        var expertResultSentence10 = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-result-level10');
        var expertResultSentence5 = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-results-level5');
        var expertResultSentence1 = facets.getFacetValueExtraInfo(expertFieldName, selectedExpert, 'sentence-results-level1');

        var selects = facets.getCurrentSelects();
        var count = 0;
        if (selects) {
            for (var key in selects) {
                if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
                    count++;
                }
            }
        }
        if (highlighted == true) {
            jQuery('.cpo-finder-center-content-header').append(expertResultSentence10[lang]);
            $('.bx-present').show();
            $('.bx-listing-emotion').show();
        } else {
            jQuery('.cpo-finder-center-content-header').append(expertResultSentence5[lang]);
            $('.bx-listing-emotion').show();
        }
    }
}

function skipQuestion(){
    facets.removeSelect(currentFacet);
    facets.addSelect(currentFacet, '*');
    goToNextQuestion();
}

function goToNextQuestion(){
    params = facets.getFacetParameters(),
        paramString = '',
        prefix = facets.getParameterPrefix();
    contextPrefix = facets.getContextParameterPrefix();
    params.forEach(function(param, index) {
        if (index > 0) {
            paramString += '&';
        } else {
            paramString += '?'
        }
        if (param.indexOf('=') === -1) {
            paramString += param + '=100';
        } else {
            paramString += param;
        }
    });
    window.location.search.substr(1).split("&").forEach(function(param, index) {
        if (param.indexOf(prefix) !== 0 && param.indexOf(contextPrefix) !== 0) {
            bind = ((paramString === '') ? (index > 0 ? '&' : '?') : '&');
            paramString += bind + param;
        }
    });
    window.location = window.location.origin + window.location.pathname + paramString;
}

function isExpertQuestion(element) { return element==expertFieldName;}

// find button logic
$('#cpo-finder-results').on('click', function (e) {
    var proceedToNextQuestion;
    if (combinedQuestions != null){
        combinedQuestions.forEach(function (question) {
            if(facets.getCurrentSelects(question) == null){
                proceedToNextQuestion = false;
            } else {
                proceedToNextQuestion = true;
            }
        })
    } else {
        proceedToNextQuestion = true;
    }
    if (proceedToNextQuestion == false){
        window.alert(alertString);
    } else {
        if(facets.getCurrentSelects(currentFacet) == null){
            skipQuestion();
        }
        goToNextQuestion();
    }
});

//show more action
$('#cpo-finder-additional').on('click', function(e) {
    $('.cpo-finder-answer').show();
    $('#cpo-finder-additional').hide();
    $('#cpo-finder-fewer').show();
});
$('#cpo-finder-fewer').on('click', function(e) {
    $('.cpo-finder-answer:gt(' + (displaySize - 1) + ')').hide();
    $('#cpo-finder-fewer').hide();
    $('#cpo-finder-additional').show();
});

// skip button logic
$('#cpo-finder-skip').on('click', function(e) {
    skipQuestion();
});

// back button logic
$('#cpo-finder-back').on('click', function(e) {
    window.history.back();
});

// show products button logic
$('#cpo-finder-show-products').on('click', function() {
    var selects = facets.getCurrentSelects();
    var count = 0;
    if (selects) {
        for (var key in selects) {
            if (key != 'bxi_data_owner_expert' && selects[key] != '*') {
                count++;
            }
        }
    }
    if (highlighted == true) {
        $('.bx-present').show();
        toggleProducts();
    } else {
        toggleProducts();
    }
});

function toggleProducts(){
    if($('.bx-listing-emotion').css('display') == 'block'){
        $('.bx-listing-emotion').hide();
    } else {
        $('.bx-listing-emotion').show();
    }
}

if(document.readyState==='interactive') {
    $(".cpo-finder-wrapper").fadeIn(100);
}
