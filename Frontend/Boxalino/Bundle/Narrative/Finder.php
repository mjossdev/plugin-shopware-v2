<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Finder
 *
 * Per emotion configuration, the following templates are used:
 * 1. product_finder.tpl https://github.com/boxalino/plugin-shopware-v2/blob/master/Frontend/Boxalino/Views/emotion/frontend/plugins/boxalino/product_finder/product_finder.tpl
 * 2. boxalinoFinder.js https://github.com/boxalino/plugin-shopware-v2/blob/master/Frontend/Boxalino/Views/responsive/frontend/_resources/javascript/boxalinoFinder.js
 * 3. the finder autoloader JS https://github.com/boxalino/plugin-shopware-v2/blob/master/Frontend/Boxalino/Views/responsive/frontend/_resources/javascript/boxalinoFinderFn.js
 *
 * For template updates/configuration, please extend the above files or have them re-written in your shop`s theme
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Finder
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_BoxalinoNarrativeInterface
{

    CONST BOXALINO_NARRATIVE_FINDER_CHOICE = "productfinder";
    CONST BOXALINO_NARRATIVE_FINDER_TEMPLATE_MAIN = "frontend/plugins/boxalino/product_finder/main.tpl";

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper|null
     */
    protected $helper;

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    /**
     * @var \Shopware\Components\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxData
     */
    protected $dataHelper;

    /**
     * @var array
     */
    protected $viewData = [];

    /**
     * @var Enlight_View_Default
     */
    protected $view;

    protected $choice;

    protected $hitCount = 0;

    /**
     * @var string | null
     */
    protected $template = null;

    /**
     * @var bool
     */
    protected $main = true;

    protected $filters = [];

    protected $type = 2;

    public function __construct()
    {
        $this->helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $this->dataHelper = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
        $this->config = Shopware()->Config();
        $this->container = Shopware()->Container();
    }

    /**
     * @return bool
     */
    public function render(&$view)
    {
        try {
            $data = $view->getAssign();
            $this->viewData['category_id'] = $data['sCategoryContent']['id'];
            $this->viewData['sCategoryContent'] = $data['sCategoryContent'];
            $this->viewData['locale'] = substr(Shopware()->Shop()->getLocale()->toString(), 0, 2);
            $this->viewData['widget_type'] = 2;
            $this->viewData['choice_id_productfinder'] = $this->getChoice();
            $this->viewData['cpo_finder_page_size'] = $this->getHitCount();
            $this->viewData['cpo_finder_link'] = $this->viewData['category_id'];
            $this->viewData['cpo_is_narrative'] = true;
            $data = $this->getContent();

            $view->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/emotion/');
            $view->extendsTemplate($this->getMainTemplate());

            $data['sCategoryContent'] = $this->viewData['sCategoryContent'];
            $view->assign('data', $data);

            return true;
        }  catch (\Exception $e) {
            $this->container->get('pluginlogger')->error("BxNarrativeFinder: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * make call for the finder
     * @return $this
     */
    public function getNarratives()
    {
        $this->helper->addFinder($this->getHitCount(), $this->getChoice(), $this->getFilters(), 'product', $this->getType());
        return $this;
    }

    public function getDependencies(){}
    public function getRenderer(){}

    /**
     * Getting the finder content from Boxalino
     *
     * @return array|null
     */
    public function getContent() {
        if (!$this->config->get('boxalino_active'))
        {
            return null;
        }

        $return = $this->viewData;
        $this->setChoice($return['choice_id_productfinder']);
        $this->setHitCount($return['cpo_finder_page_size']);
        $this->filters['category_id'] = $this->viewData['category_id'];
        $this->type = $return['widget_type'];
        $this->getNarratives();

        $this->viewData['json_facets'] = $this->convertFacetsToJson();
        if($return['widget_type'] == '2'){
            $articleIds = $this->helper->getHitFieldValues('products_ordernumber');
            $scores = $this->helper->getHitFieldValues('finalScore');
            $highlightedValues = $this->helper->getHitFieldValues('highlighted');
            $comment = $this->helper->getHitFieldValues('products_bxi_expert_sentence');
            $description = $this->helper->getHitFieldValues('products_bxi_expert_description');
            $articles = $this->dataHelper->getLocalArticles($articleIds, $highlightedValues);
            $type = $this->getViewType();
            $highlighted_articles =  null;
            $top_match = null;
            $highlight_count = 0;
            $c=0;
            foreach($articles as $index => $article) {
                $id = $article['articleID'];
                $article['bx_score'] = number_format($scores[$c]);
                $article['comment'] = $comment[$c];
                $article['description'] = $description[$c];
                $highlighted =  $highlightedValues[$c++] == 'true';
                if($highlighted){
                    if($index == 0 && $type == 'present'){
                        $top_match[] = $article;
                    }else {
                        $highlighted_articles[] = $article;
                    }
                    $highlight_count++;
                    unset($articles[$index]);
                } else {
                    $articles[$index] = $article;
                }
            }
            $this->viewData['sArticles'] = $articles;
            $this->viewData['isFinder'] = true;
            $this->viewData['highlighted_articles'] = $highlighted_articles;
            $this->viewData['highlighted'] = (sizeof($highlighted_articles)>0) ? "true" : "false";
            $this->viewData['top_match'] = $top_match;
            $this->viewData['max_score'] = round(max(array_values($scores)));
            $this->viewData['narrative_bx_request_id'] = $this->helper->getRequestId($this->choice);
            $this->viewData['narrative_bx_request_uuid'] = $this->helper->getRequestUuid($this->choice);
            $this->viewData['narrative_bx_request_group_by'] = $this->helper->getRequestGroupBy($this->choice);
            if(empty($this->viewData['max_score']))
            {
                $this->viewData['max_score'] = 0;
            }
            $this->viewData['finderMode'] = $type;// $finderMode = ($highlight_count == 0 ? 'question' : ($highlight_count == 1 ? 'present' : 'listing'));
            $this->viewData['slider_data'] = ['no_border' => true, 'article_slider_arrows' => 1, 'article_slider_type' => 'selected_article',
                'article_slider_max_number' => count($highlighted_articles), 'values' => $highlighted_articles, 'article_slider_title' => 'Zu Ihnen passende Produkte'];
            $this->viewData['shop'] = Shopware()->Shop();
        }

        return $this->viewData;
    }

    public function getViewType(){
        $address = $_SERVER['HTTP_REFERER'];
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));
        foreach ($params as $index => $param){
            $keyValue = explode("=", $param);
            $params[$keyValue[0]] = $keyValue[1];
            unset($params[$index]);
        }
        $count = 0;
        foreach ($params as $key => $value) {
            if(strpos($key, 'bxrpw_') === 0) {
                $count++;
            }
        }
        return ($count == 0 ? 'question' : ($count == 1 ? 'listing' : 'present'));
    }

    protected function convertFacetsToJson()
    {
        $json = [];
        $bxFacets =  $this->helper->getFacets();
        $bxFacets->showEmptyFacets(true);
        $fieldNames = $bxFacets->getCPOFinderFacets();
        if(!empty($fieldNames)) {
            foreach ($fieldNames as $fieldName) {
                if($fieldName == ''){
                    continue;
                }
                $facet_info = $bxFacets->getAllFacetExtraInfo($fieldName);
                $extraInfo = [];
                $facetValues = $bxFacets->getFacetValues($fieldName);
                $json['facets'][$fieldName]['facetValues'] = $facetValues;
                foreach ($facetValues as $value) {
                    if($bxFacets->isFacetValueHidden($fieldName, $value)) {
                        $json['facets'][$fieldName]['hidden_values'][] = $value;
                    }
                }
                $json['facets'][$fieldName]['label'] = $bxFacets->getFacetLabel($fieldName);

                foreach ($facet_info as $info_key => $info) {
                    if($info_key == 'isSoftFacet' && $info == null){
                        $facetMapping = [];
                        $attributeName = substr($fieldName, 9);
                        $json['facets'][$fieldNames]['parameterName'] = $attributeName;
                        $json['facets'][$fieldName]['facetMapping'] = $facetMapping;
                    }
                    if($info_key == 'jsonDependencies' || $info_key == 'label' || $info_key == 'iconMap' || $info_key == 'facetValueExtraInfo') {
                        $info = json_decode($info);
                        if($info_key == 'jsonDependencies') {
                            if(!is_null($info)) {
                                if(isset($info[0]) && isset($info[0]->values[0])) {
                                    $check = $info[0]->values[0];
                                    if(strpos($check, ',') !== false) {
                                        $info[0]->values = explode(',', $check);
                                    }
                                }
                            }
                        }
                    }
                    $extraInfo[$info_key] = $info;
                }
                $json['facets'][$fieldName]['facetExtraInfo'] = $extraInfo;
            }
            $json['parametersPrefix'] = 'bx_';
            $json['contextParameterPrefix'] = $this->helper->getPrefixContextParameter();
            $json['level'] = $this->getLevel();
            $json['separator'] = '|';
        }

        return json_encode($json);
    }

    protected function getLevel()
    {
        $ids = $this->helper->getEntitiesIds();
        $level = 10;
        $h = 0;
        foreach ($ids as $id) {
            if($this->helper->getHitVariable($id, 'highlighted')){
                if($h++ >= 2){
                    $level = 5;
                    break;
                }
            }
            if($h == 0) {
                $level = 1;
                break;
            } else {
                break;
            }
        }

        return $level;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setViewData($data)
    {
        $this->viewData = $data;
        return $this;
    }

    public function setChoice($choice)
    {
        $this->choice = $choice;
        return $this;
    }

    public function getChoice()
    {
        if(empty($this->choice))
        {
            $this->choice = self::BOXALINO_NARRATIVE_FINDER_CHOICE;
        }
        return $this->choice;
    }

    public function setHitCount($count)
    {
        $this->hitCount = $count;
        return $this;
    }

    public function getHitCount()
    {
        return $this->hitCount;
    }

    /**
     * the view returned by the finder replaces main or not
     * @param $main
     * @return $this
     */
    public function setMain($main)
    {
        $this->main = $main;
        return $this;
    }

    public function setMainTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function getMainTemplate()
    {
        if(is_null($this->template))
        {
            $this->template = self::BOXALINO_NARRATIVE_FINDER_TEMPLATE_MAIN;
        }

        return $this->template;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getViewManager()
    {
        return null;
    }


}