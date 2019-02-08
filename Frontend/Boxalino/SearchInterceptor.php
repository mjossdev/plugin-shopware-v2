<?php

use Doctrine\DBAL\Connection;
use Shopware\Components\ReflectionHelper;
use Shopware\Bundle\SearchBundle\FacetResult\TreeFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\TreeItem;
use Shopware\Bundle\SearchBundle\FacetResult\RadioFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\ValueListItem;
/**
 * Class Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
 */
class Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
    extends Shopware_Plugins_Frontend_Boxalino_Interceptor {

    /**
     * @var Shopware\Components\DependencyInjection\Container
     */
    private $container;

    /**
     * @var FacetHandlerInterface[]
     */
    protected $facetHandlers;

    /**
     * @var array
     */
    protected $facetOptions = [];

    /**
     * @var bool
     */
    protected $shopCategorySelect = false;

    /**
     * @var bool
     */
    protected $isNarrative = false;

    /**
     * @var bool
     */
    protected $replaceMain = false;

    /**
     * Shopware_Plugins_Frontend_Boxalino_SearchInterceptor constructor.
     * @param Shopware_Plugins_Frontend_Boxalino_Bootstrap $bootstrap
     */
    public function __construct(Shopware_Plugins_Frontend_Boxalino_Bootstrap $bootstrap) {
        parent::__construct($bootstrap);
        $this->container = Shopware()->Container();
    }

    /**
     * Display narrative server-side using emotion
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function onNarrativeEmotion($data)
    {
        $narrativeLogic = new Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative($data['choiceId'], Shopware()->Front()->Request(), true, $data['additional_choiceId']);

        $data['narrative'] = $narrativeLogic->getNarratives();
        $data['dependencies'] = $narrativeLogic->getDependencies();
        $data['bxRender'] = $narrativeLogic->getRenderer();

        return $data;
    }

    public function landingPage() {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        $view = new Enlight_View_Default($this->get('Template'));

        $view = $this->prepareViewConfig($view);
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        $orderParam = $this->get('query_alias_mapper')->getShortAlias('sSort');

        $request = Shopware()->Front()->Request();
        $request = $this->setRequestWithRefererParams($request);

        $defaultSort = null;
        if(is_null($request->getParam($orderParam))) {
            $request->setParam('sSort', 7);
        }
        if(is_null($request->getParam('sSort')) && is_null($request->getParam($orderParam))) {
            if($this->Config()->get('boxalino_navigation_sorting')) {
                $request->setParam('sSort', 7);
            } else {
                $default = $this->get('config')->get('defaultListingSorting');
                $request->setParam('sSort', $default);
            }
        }

        $criteria = $this->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($request, $context);
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");

        $facets = $criteria->getFacets();
        $options = $this->BxData()->getFacetConfig($facets, $request);

        $sort =  $this->BxData()->getSortOrder($criteria, null, true);
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $pageCounts = array_values(explode('|', $this->get('config')->get('fuzzySearchSelectPerPage')));

        $this->Helper()->setRequest($request);
        $this->Helper()->addSearch('', $pageOffset, $hitCount, 'product', $sort, $options, array(), false, 'landingpage');

        $articles = array();
        if ($totalHitCount = $this->Helper()->getTotalHitCount('product', 'landingpage')) {
            $ids = $this->Helper()->getHitFieldValues('products_ordernumber', 'product', 'landingpage');
            $articles = $this->BxData()->getLocalArticles($ids);
            $facets = $this->updateFacetsWithResult($facets, $context, $request, 'landingpage');
        }

        $view->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');

        $view->loadTemplate('frontend/plugins/boxalino/landingpage/content.tpl');
        if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
            $view->extendsTemplate('frontend/plugins/boxalino/listing/filter/facet-value-list.tpl');
            $view->extendsTemplate('frontend/plugins/boxalino/listing/index.tpl');
        } else {
            $view->extendsTemplate('frontend/plugins/boxalino/listing/filter/_includes/filter-multi-selection.tpl');
            $view->extendsTemplate('frontend/plugins/boxalino/listing/index_5_3.tpl');
        }
        $service = $this->get('shopware_storefront.custom_sorting_service');
        $sortingIds = $this->container->get('config')->get('searchSortings');
        $sortingIds = array_filter(explode('|', $sortingIds));
        $sortings = $service->getList($sortingIds, $context);

        $templateProperties = array(
            'sTemplate' => $request->getParam('sTemplate'),
            'sPerPage' => $pageCounts,
            'sRequests' => $request->getParams(),
            'ajaxCountUrlParams' => [],
            'sPage' => $request->getParam('sPage', 1),
            'bxFacets' => $this->Helper()->getFacets('product', 'landingpage', 0),
            'criteria' => $criteria,
            'facets' => $facets,
            'sortings' => $sortings,
            'sNumberArticles' => $totalHitCount,
            'sArticles' => $articles,
            'facetOptions' => $this->facetOptions,
            'sSort' => $request->getParam('sSort'),
            'showListing' => true,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'bx_request_id' => $this->Helper()->getRequestId(),
            'baseUrl' => $request->getBaseUrl() . $request->getPathInfo(),
        );
        $view->assign($templateProperties);
        return $view->render();
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return array
     */
    public function voucher(Enlight_Event_EventArgs $arguments) {
        $data = $arguments->getReturn();
        $choiceId = $data['choiceId'];
        $data = array_merge($data, $this->Helper()->addVoucher($choiceId));
        $data = $this->prepareVoucherTemplate($data);
        $data['show'] = false;
        if (!is_null($data)) {
            $data['show'] = true;
        }
        return $data;
    }

    public function CPOFinder($data) {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }

        $return = $data;
        $filter['category_id'] = $data['category_id'];
        $choice_id = $return['choice_id_productfinder'] == '' ? 'productfinder' : $return['choice_id_productfinder'];
        $hitCount = $return['cpo_finder_page_size'];
        $this->Helper()->addFinder($hitCount, $choice_id, $filter, 'product', $return['widget_type']);
        $data['json_facets'] = $this->convertFacetsToJson();
        if($return['widget_type'] == '2'){
            $articleIds = $this->Helper()->getHitFieldValues('products_ordernumber');
            $scores = $this->Helper()->getHitFieldValues('finalScore');
            $highlightedValues = $this->Helper()->getHitFieldValues('highlighted');
            $comment = $this->Helper()->getHitFieldValues('products_bxi_expert_sentence');
            $description = $this->Helper()->getHitFieldValues('products_bxi_expert_description');
            $articles = $this->BxData()->getLocalArticles($articleIds, $highlightedValues);
            $type = $this->checkParams();
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
            $data['sArticles'] = $articles;
            $data['isFinder'] = true;
            $data['highlighted_articles'] = $highlighted_articles;
            $data['highlighted'] = (sizeof($highlighted_articles)>0) ? "true" : "false";
            $data['top_match'] = $top_match;
            $data['max_score'] = max(array_values($scores));
            if(empty($data['max_score']))
            {
                $data['max_score'] = 0;
            }
            $data['finderMode'] = $type;// $finderMode = ($highlight_count == 0 ? 'question' : ($highlight_count == 1 ? 'present' : 'listing'));
            $data['slider_data'] = ['no_border' => true, 'article_slider_arrows' => 1, 'article_slider_type' => 'selected_article',
                'article_slider_max_number' => count($highlighted_articles), 'values' => $highlighted_articles, 'article_slider_title' => 'Zu Ihnen passende Produkte'];
            $data['shop'] = Shopware()->Shop(); //$this->get('shopware_storefront.context_service')->getShopContext();
        }

        return $data;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return array|null
     */
    public function portfolio(Enlight_Event_EventArgs $arguments) {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        $data = $arguments->getReturn();
        $portfolio = $this->Helper()->addPortfolio($data);
        return $portfolio;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool|null
     */
    public function ajaxSearch(Enlight_Event_EventArgs $arguments)
    {
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled') || !$this->Config()->get('boxalino_autocomplete_enabled')) {
            return null;
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);
        }
        $this->init($arguments);
        Shopware()->Plugins()->Controller()->Json()->setPadding();

        $term = $this->getSearchTerm();
        if (empty($term) || strlen($term) < $this->Config()->get('MinSearchLenght')) {
            return;
        }
        $with_blog = $this->Config()->get('boxalino_blog_search_enabled');
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->Helper()->addNotification("Ajax Search pre autocomplete took: " . (microtime(true) - $t1) * 1000 . "ms");
        }
        $templateProperties = $this->Helper()->autocomplete($term, $with_blog);
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t2 = microtime(true);
        }
        $this->View()->loadTemplate('frontend/search/ajax.tpl');
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/ajax.tpl');
        $this->View()->assign($templateProperties);
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->Helper()->addNotification("Ajax Search post autocomplete took: " . (microtime(true) - $t2) * 1000 . "ms");
            $this->Helper()->addNotification("Ajax Search took in total: " . (microtime(true) - $t1) * 1000 . "ms");
            $this->Helper()->callNotification(true);
        }

        return false;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool|void
     */
    public function listingAjax(Enlight_Event_EventArgs $arguments)
    {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        $this->init($arguments);

        if(is_null($this->Request()->getParam('q'))) {
            if(!$this->Config()->get('boxalino_navigation_enabled')){
                return null;
            }
        } else {
            if(!$this->Config()->get('boxalino_search_enabled')){
                return null;
            }
        }

        if($this->Request()->getActionName() == 'productNavigation'){
            return null;
        }

        $choice_id = $this->Request()->getParam('choice_id', null);

        if($choice_id) {
            $this->Request()->setParam('sCategory', Shopware()->Shop()->getCategory()->getId());
        }

        $viewData = $this->View()->getAssign();
        $catId = $this->Request()->getParam('sCategory', null);
        $streamId = $this->BxData()->findStreamIdByCategoryId($catId);
        $listingCount = $this->Request()->getActionName() == 'listingCount';
        if(version_compare(Shopware::VERSION, '5.3.0', '>=')) {
            if(!$listingCount || (!empty($streamId) && !$this->Config()->get('boxalino_navigation_product_stream'))) {
                return null;
            }
        } else {
            if ((!empty($streamId) && !$this->Config()->get('boxalino_navigation_product_stream'))) {
                return null;
            }
        }
        $filter = array();
        if($streamId) {
            $streamConfig = $this->BxData()->getStreamById($streamId);
            if($streamConfig['conditions']){
                $conditions = $this->unserialize(json_decode($streamConfig['conditions'], true));
                $filter = $this->getConditionFilter($conditions);
                if(is_null($filter)) {
                    return null;
                }
            } else {
                $filter['products_stream_id'] = [$streamId];
            }
        }

        if(!$this->Config()->get('boxalino_navigation_activate_cache')) {
            $this->Bootstrap()->disableHttpCache();
        }
        $showFacets = $this->BxData()->categoryShowFilter($catId);
        if($supplier = $this->Request()->getParam('sSupplier')) {
            if(strpos($supplier, '|') === false){
                $supplier_name = $this->BxData()->getSupplierName($supplier);
                $filter['products_brand'] = [$supplier_name];
            }
        }
        $context  = $this->get('shopware_storefront.context_service')->getShopContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $orderParam = $this->get('query_alias_mapper')->getShortAlias('sSort');
        if($this->Request()->has($orderParam)) {
            $viewData['sSort'] = $this->Request()->getParam($orderParam);
        }
        if(is_null($this->Request()->getParam('sSort')) && is_null($this->Request()->getParam($orderParam))) {
            if($this->Config()->get('boxalino_navigation_sorting')) {
                $viewData['sSort'] = null;
                $this->Request()->setParam('sSort', 7);
            } else {
                $default = $this->get('config')->get('defaultListingSorting');
                $this->Request()->setParam('sSort', $default);
            }
        }
        $criteria = $this->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->Request(), $context);
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $sort =  $this->BxData()->getSortOrder($criteria, $viewData['sSort'], true);
        $queryText = $this->Request()->getParams()['q'];
        $facets = $criteria->getFacets();
        $options = $showFacets ? $this->BxData()->getFacetConfig($facets, $this->Request()) : [];
        $this->Helper()->addSearch($queryText, $pageOffset, $hitCount, 'product', $sort, $options, $filter, !is_null($streamId));
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');

        if(version_compare(Shopware::VERSION, '5.3.0', '>=')) {
            $body['totalCount'] = $this->Helper()->getTotalHitCount();
            if ($this->Request()->getParam('loadFacets')) {
                $facets = $showFacets ? $this->updateFacetsWithResult($facets, $context) : [];
                $body['facets'] = array_values($facets);
            }
            if ($this->Request()->getParam('loadProducts')) {
                if ($this->Request()->has('productBoxLayout')) {
                    $boxLayout = $this->Request()->get('productBoxLayout');
                } else {
                    $boxLayout = $catId ? Shopware()->Modules()->Categories()
                        ->getProductBoxLayout($catId) : $this->get('config')->get('searchProductBoxLayout');
                }
                $this->View()->assign($this->Request()->getParams());
                $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/_includes/filter-multi-selection.tpl');
                $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index_5_3.tpl');
                $articles = $this->BxData()->getLocalArticles($this->Helper()->getHitFieldValues('products_ordernumber'));
                $articles = $this->convertArticlesResult($articles, $catId);
                $this->loadThemeConfig();
                $this->View()->assign([
                    'sArticles' => $articles,
                    'pageIndex' => $this->Request()->getParam('sPage'),
                    'productBoxLayout' => $boxLayout,
                    'sCategoryCurrent' => $catId,
                ]);
                $body['listing'] = '<div style="display:none;">'.$this->Helper()->getRequestId().'</div>' . $this->View()->fetch('frontend/listing/listing_ajax.tpl');
                $sPerPage = $this->Request()->getParam('sPerPage');
                $this->View()->assign([
                    'sPage' => $this->Request()->getParam('sPage'),
                    'pages' => ceil($this->Helper()->getTotalHitCount() / $sPerPage),
                    'baseUrl' => $this->Request()->getBaseUrl() . $this->Request()->getPathInfo(),
                    'pageSizes' => explode('|', $this->container->get('config')->get('numberArticlesToShow')),
                    'shortParameters' => $this->container->get('query_alias_mapper')->getQueryAliases(),
                    'limit' => $sPerPage,
                ]);
                $body['pagination'] = $this->View()->fetch('frontend/listing/actions/action-pagination.tpl');
            }
            $this->Controller()->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Controller()->Response()->setBody(json_encode($body));
            $this->Controller()->Response()->setHeader('Content-type', 'application/json', true);
        } else {
            if ($listingCount) {
                $this->Controller()->Front()->Plugins()->ViewRenderer()->setNoRender();
                $this->Controller()->Response()->setBody('{"totalCount":' . $this->Helper()->getTotalHitCount() . '}');
                $this->Controller()->Response()->setHeader('Content-type', 'application/json', true);
                return false;
            }
            $articles = $this->BxData()->getLocalArticles($this->Helper()->getHitFieldValues('products_ordernumber'));
            $viewData['sArticles'] = $articles;
            $this->View()->assign($viewData);
        }
        return true;
    }


    private function getManufacturerById($ids) {
        $names = array();
        $db = Shopware()->Db();
        $select = $db->select()->from(array('s' => 's_articles_supplier'), array('name'))
            ->where('s.id IN(' . implode(',', $ids) . ')');
        $stmt = $db->query($select);
        if($stmt->rowCount()) {
            while($row = $stmt->fetch()){
                $names[] = $row['name'];
            }
        }
        return $names;
    }

    private function getConditionFilter($conditions) {
        $filter = array();
        foreach ($conditions as $condition) {
            switch(get_class($condition)) {
                case 'Shopware\Bundle\SearchBundle\Condition\PropertyCondition':
                    $filterValues = $condition->getValueIds();
                    $option_id = $this->getOptionIdFromValue(reset($filterValues));
                    $useTranslation = $this->BxData()->useTranslation('propertyvalue');
                    $result = $this->getFacetValuesResult($option_id, $filterValues, $useTranslation);
                    $values = array();
                    foreach ($result as $r) {
                        if(!empty($r['value'])){
                            if($useTranslation == true && isset($r['objectdata'])) {
                                $translation = unserialize($r['objectdata']);
                                $r['value'] = isset($translation['optionValue']) && $translation['optionValue'] != '' ?
                                    $translation['optionValue'] : $r['value'];
                            }
                            $values[] = trim($r['value']);
                        }
                    }
                    $filter['products_optionID_' . $option_id] = $values;
                    break;
                case 'Shopware\Bundle\SearchBundle\Condition\CategoryCondition':
                    $filterValues = $condition->getCategoryIds();
                    $filter['category_id'] = $filterValues;
                    break;
                case 'Shopware\Bundle\SearchBundle\Condition\ManufacturerCondition':
                    $filterValues = $condition->getManufacturerIds();
                    $filter['products_brand'] = $this->getManufacturerById($filterValues);
                    break;
                default:
                    return null;
                    break;
            }
        }
        return $filter;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    public function listing(Enlight_Event_EventArgs $arguments) {
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $start = microtime(true);
            $this->Helper()->addNotification("Navigation start: " . $start);
        }
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_navigation_enabled')) {
            return null;
        }

        $this->init($arguments);

        if($this->Request()->getActionName() == 'manufacturer') {
            $this->prepareManufacturer();
        }

        $filter = array();
        $viewData = $this->View()->getAssign();

        $this->prepareNarrativeCase($viewData);
        if($this->isNarrative && $this->replaceMain)
        {
            return $this->processNarrativeRequest($viewData['sCategoryContent']['attribute']['narrative_choice'], $viewData['sCategoryContent']['attribute']['narrative_additional_choice']);
        }

        $catId = $this->Request()->getParam('sCategory');
        $streamId = $this->BxData()->findStreamIdByCategoryId($catId);
        if ((!empty($streamId) && !$this->Config()->get('boxalino_navigation_product_stream'))) {
            return null;
        }

        if($streamId) {
            $streamConfig = $this->BxData()->getStreamById($streamId);
            if($streamConfig['conditions']){
                $conditions = $this->unserialize(json_decode($streamConfig['conditions'], true));
                $filter = $this->getConditionFilter($conditions);
                if(is_null($filter)) {
                    return null;
                }
            } else {
                $filter['products_stream_id'] = [$streamId];
            }
        }
        if(!$this->Config()->get('boxalino_navigation_activate_cache')) {
            $this->Bootstrap()->disableHttpCache();
        }
        $showFacets = $this->BxData()->categoryShowFilter($catId);
        if(isset($viewData['manufacturer']) && !empty($viewData['manufacturer'])) {
            $filter['products_brand'] = [$viewData['manufacturer']->getName()];
        }
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $orderParam = $this->get('query_alias_mapper')->getShortAlias('sSort');

        if(is_null($this->Request()->getParam($orderParam))) {
            $specialCase = $this->Config()->get('boxalino_navigation_special_enabled');
            $ids = explode(',', $this->Config()->get('boxalino_navigation_exclude_ids'));
            if($specialCase && in_array($this->Request()->getParam('sCategory'), $ids)) {
                $default = $this->get('config')->get('defaultListingSorting');
                $this->Request()->setParam('sSort', $default);
                $viewData['sSort'] = $default;
            } else {
                $viewData['sSort'] = null;
                $this->Request()->setParam('sSort', 7);
            }
        } else {
            $viewData['sSort'] = $this->Request()->getParam($orderParam);
        }

        if(is_null($this->Request()->getParam('sSort')) && is_null($this->Request()->getParam($orderParam))) {
            if($this->Config()->get('boxalino_navigation_sorting')) {
                $viewData['sSort'] = null;
                $this->Request()->setParam('sSort', 7);
            } else {
                $default = $this->get('config')->get('defaultListingSorting');
                $this->Request()->setParam('sSort', $default);
            }
        }
        $criteria = $this->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->Request(), $context);
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $start) * 1000 ;
            $this->Helper()->addNotification("Navigation before createFacets took in total: " . $t1 . "ms.");
        }
        $facets = $criteria->getFacets();
        $options = $showFacets ? $this->BxData()->getFacetConfig($facets, $this->Request()) : [];
        $sort = $this->BxData()->getSortOrder($criteria, $viewData['sSort'], true);

        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->Helper()->addNotification("Navigation before response took in total: " . (microtime(true)- $start) * 1000 . "ms.");
        }
        $this->Helper()->addSearch('', $pageOffset, $hitCount, 'product', $sort, $options, $filter, !is_null($streamId));
        if($this->isNarrative && !$this->replaceMain){
            $this->processNarrativeRequest($viewData['sCategoryContent']['attribute']['narrative_choice'], $viewData['sCategoryContent']['attribute']['narrative_additional_choice'], false, $filter);
        }

        if($this->Helper()->getResponse()->getRedirectLink() != '') {
            $this->Controller()->redirect($this->Helper()->getResponse()->getRedirectLink());
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $afterStart = microtime(true);
            $this->Helper()->addNotification("Navigation after response: " . $afterStart);
        }
        $facets = $showFacets ? $this->updateFacetsWithResult($facets, $context) : [];
        $articles = $this->BxData()->getLocalArticles($this->Helper()->getHitFieldValues('products_ordernumber'));
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
            if ($this->Config()->get('boxalino_navigation_sorting') == true) {
                $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-sorting.tpl');
            }
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/facet-value-list.tpl');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index.tpl');
        } else {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/_includes/filter-multi-selection.tpl');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index_5_3.tpl');
            $service = $this->get('shopware_storefront.custom_sorting_service');
            $sortingIds = $this->container->get('config')->get('searchSortings');
            $sortingIds = array_filter(explode('|', $sortingIds));
            $sortings = $service->getList($sortingIds, $context);
        }
        $totalHitCount = $this->Helper()->getTotalHitCount();
        $pageCounts = array_values(explode('|', $this->get('config')->get('numberarticlestoshow')));
        $templateProperties = array(
            'pageSizes' => $pageCounts,
            'sPerPage' => $pageCounts,
            'sPage' => $this->Request()->getParam('sPage', 1),
            'bxFacets' => $this->Helper()->getFacets(),
            'criteria' => $criteria,
            'facets' => $facets,
            'sortings' => $sortings,
            'sNumberArticles' => $totalHitCount,
            'sArticles' => $articles,
            'facetOptions' => $this->facetOptions,
            'sSort' => $this->Request()->getParam('sSort'),
            'showListing' => true,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'bx_request_id' => $this->Helper()->getRequestId(),
            'isNarrative' => $this->isNarrative
        );
        $narrativeTemplateData = array();
        if($this->isNarrative)
        {
            $narrativeTemplateData = $this->getNarrativeTemplateData($viewData['sCategoryContent']['attribute']['narrative_choice'], $viewData['sCategoryContent']['attribute']['narrative_additional_choice']);
        }
        $categoryTemplateData = new Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData($this->Helper(), $viewData);
        $viewData = $categoryTemplateData->update();

        $templateProperties = array_merge($viewData, $templateProperties, $narrativeTemplateData);
        $this->View()->assign($templateProperties);

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $afterStart = microtime(true);
            $this->Helper()->addNotification("Search after response took in total: " . (microtime(true) - $afterStart) * 1000 . "ms.");
            $this->Helper()->addNotification("Navigation time took in total: " . (microtime(true) - $start) * 1000 . "ms.");
        }

        return true;
    }

    /**
     * Call for narrative element on category page
     *
     * @param $choiceId
     * @param $additionalChoice
     * @return array
     * @throws Exception
     */
    public function getNarrativeTemplateData($choiceId, $additionalChoice)
    {
        $narrativeLogic = new Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative($choiceId, $this->Request(), false, $additionalChoice, true);

        $narratives = $narrativeLogic->getNarrativeResponse();
        $dependencies = $narrativeLogic->getDependencies();
        $renderer = $narrativeLogic->getRenderer();
        $narrativeData = $renderer->getTemplateDataToBeAssigned($narratives);

        $globalParams = $narrativeLogic->processNarrativeParameters($narratives[0]['parameters']);
        if(!isset($globalParams['narrative_block_main_template']))
        {
            $globalParams['narrative_block_main_template'] = null;
        }

        $this->View()->extendsTemplate($narrativeLogic->getServerSideScriptTemplate());
        $this->View()->extendsTemplate($narrativeLogic->getMainTemplateNoReplace($globalParams['narrative_block_main_template']));

        return array_merge($globalParams, array(
            'narrativeData'=>$narrativeData,
            'dependencies' => $dependencies,
            'narrative' => $narratives,
            'bxRender' => $renderer->setDataForRendering($narrativeData)
        ));
    }

    /**
     * Set class variable with narrative status for category view
     *
     * @param $viewData
     */
    protected function prepareNarrativeCase($viewData)
    {
        if(!empty($viewData['sCategoryContent']['attribute']['narrative_choice'])) {
            $this->isNarrative = true;
        }

        if($viewData['sCategoryContent']['attribute']['narrative_replace_main'])
        {
            $this->replaceMain = true;
        }
    }

    /**
     * Processing narrative request;
     * If finder, a divided logic to be applied
     *
     * @param $choiceId
     * @param null $additionalChoiceId
     * @param bool $replaceMain
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    public function processNarrativeRequest($choiceId, $additionalChoiceId = null, $execute = true, $filters=[])
    {
        if($choiceId === "productfinder")
        {
            return $this->processCPOFinderRequest($choiceId, $additionalChoiceId);
        }

        return $this->processNarrative($choiceId, $additionalChoiceId, $execute, $filters);
    }

    /**
     * Used for rendering narrative when it is not rendered via emotion
     *
     * @param $choiceId
     * @param $hitCount
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    public function processCPOFinderRequest($choiceId, $hitCount)
    {
        try {
            $data = $this->View()->getAssign();
            $cpodata['category_id'] = $data['sCategoryContent']['id'];
            $cpodata['locale'] = substr(Shopware()->Shop()->getLocale()->toString(), 0, 2);
            $cpodata['widget_type'] = 2;
            $cpodata['choice_id_productfinder'] = $choiceId;
            $cpodata['cpo_finder_page_size'] = $hitCount;
            $cpodata['cpo_finder_link'] = $cpodata['category_id'];
            $cpodata['cpo_is_narrative'] = true;
            $data = $this->CPOFinder($cpodata);
            $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
            $this->View()->extendsTemplate("frontend/plugins/boxalino/product_finder/main.tpl");
            $this->View()->assign('data', $data);

            return true;
        }  catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    /**
     * catching a narrative request
     * checking for the choice id and for the additional choice ids to render the requested narrative
     * @param $choiceId
     * @param null $additionalChoiceId
     * @return bool
     */
    public function processNarrative($choiceId, $additionalChoiceId = null, $execute = true)
    {
        try {
            $data = $this->View()->getAssign();
            $narrativeLogic = new Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative($choiceId, $this->Request(), false, $additionalChoiceId, $this->replaceMain, $filters);
            $narratives = $narrativeLogic->getNarratives();

            if(!$execute)
            {
                return;
            }

            $dependencies = $narrativeLogic->getDependencies();
            $renderer = $narrativeLogic->getRenderer();
            $narrativeData = $renderer->getTemplateDataToBeAssigned($narratives);

            //updating content of the category view in case it was set via narrative
            if(isset($data['sCategoryContent']) || isset($data['sBreadcrumb']))
            {
                $categoryTemplateData = new Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData($this->Helper(), $data);
                $data = $categoryTemplateData->update();
            }

            $this->View()->addTemplateDir($narrativeLogic->getServerSideTemplateDirectory());
            if ($this->Config()->get('boxalino_navigation_sorting') == true) {
                $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-sorting.tpl');
            }
            $this->View()->extendsTemplate($narrativeLogic->getServerSideScriptTemplate());
            $this->View()->extendsTemplate($narrativeLogic->getServerSideMainTemplate());

            $this->View()->assign($data);
            $this->View()->assign('narrativeData', $narrativeData);
            $this->View()->assign('dependencies', $dependencies);
            $this->View()->assign('narrative', $narratives);
            $this->View()->assign('bxRender', $renderer->setDataForRendering($narrativeData));

            return true;
        }  catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    protected function prepareManufacturer()
    {
        $manufacturerId = $this->Request()->getParam('sSupplier', null);
        $context = $this->get('shopware_storefront.context_service')->getShopContext();

        if (!$this->Request()->getParam('sCategory')) {
            $this->Request()->setParam('sCategory', $context->getShop()->getCategory()->getId());
        }

        /** @var $manufacturer Manufacturer */
        $manufacturer = $this->get('shopware_storefront.manufacturer_service')->get(
            $manufacturerId,
            $this->get('shopware_storefront.context_service')->getShopContext()
        );

        if ($manufacturer === null) {
            throw new Enlight_Controller_Exception(
                'Manufacturer missing, non-existent or invalid',
                404
            );
        }

        $this->View()->assign('showListing', true);
        $this->View()->assign('manufacturer', $manufacturer);
        $this->View()->assign('ajaxCountUrlParams', [
            'sSupplier' => $manufacturerId,
            'sCategory' => $context->getShop()->getCategory()->getId(),
        ]);
        $this->View()->assign('sCategoryContent', $this->getSeoDataOfManufacturer($manufacturer));
    }

    private function getSeoDataOfManufacturer($manufacturer)
    {
        $content = [];
        $content['metaDescription'] = $manufacturer->getMetaDescription();
        $content['metaKeywords'] = $manufacturer->getMetaKeywords();

        $canonicalParams = [
            'sViewport' => 'listing',
            'sAction' => 'manufacturer',
            'sSupplier' => $manufacturer->getId(),
        ];

        $content['canonicalParams'] = $canonicalParams;

        $path = Shopware()->Front()->Router()->assemble($canonicalParams);
        if ($path) {
            /* @deprecated */
            $content['sSelfCanonical'] = $path;
        }

        $content['metaTitle'] = $manufacturer->getMetaTitle();
        $content['title'] = $manufacturer->getName();

        return $content;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return null
     */
    public function blog(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_blog_page_recommendation')) {
            return null;
        }
        $this->init($arguments);

        $blog = $this->View()->getAssign();
        $excludes = array();
        $relatedArticles =  isset($blog['sArticle']['sRelatedArticles']) ? $blog['sArticle']['sRelatedArticles'] : array();

        foreach ($relatedArticles as $article) {
            $excludes[] = $article['articleID'];
        }
        $context = 'blog_' . $blog['sArticle']['id'];
        $choiceId = $this->Config()->get('boxalino_blog_page_recommendation_name');
        $min = $this->Config()->get('boxalino_blog_page_recommendation_min');
        $max = $this->Config()->get('boxalino_blog_page_recommendation_max');
        $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $context, 'blog_product', false, $excludes);
        $ids = $this->Helper()->getRecommendation($choiceId);
        $articles = $this->BxData()->getLocalArticles($ids);
        $blog['bxProductRecommendation'] = $articles;
        $blog['bxRecTitle'] = $this->Helper()->getSearchResultTitle($choiceId);
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/blog/detail.tpl');
        $this->View()->assign($blog);
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     */
    public function search(Enlight_Event_EventArgs $arguments)
    {
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $start = microtime(true);
            $this->Helper()->addNotification("Search start: " . $start);
        }
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled')) {
            return null;
        }
        $this->init($arguments);
        $term = $this->getSearchTerm();
        // Check if we have a one to one match for ordernumber, then redirect
        $location = $this->searchFuzzyCheck($term);
        if (!empty($location)) {
            return $this->Controller()->redirect($location);
        }

        if(is_null($this->Request()->getParam('sSort'))) {
            if($this->Config()->get('boxalino_navigation_sorting')){
                $this->Request()->setParam('sSort', 7);
            } else {
                $default = $this->get('config')->get('defaultListingSorting');
                $this->Request()->setParam('sSort', $default);
            }
        }

        /* @var ProductContextInterface $context */
        $context  = $this->get('shopware_storefront.context_service')->getShopContext();

        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->Request(), $context);
        // discard search / term conditions from criteria, such that _all_ facets are properly requested
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $start) * 1000 ;
            $this->Helper()->addNotification("Search before createFacets took in total: " . $t1 . "ms.");
        }
        $facets = $criteria->getFacets();
        $options = $this->BxData()->getFacetConfig($facets, $this->Request(), "products_bx_purchasable");
        $sort =  $this->BxData()->getSortOrder($criteria);
        $config = $this->get('config');
        $pageCounts = array_values(explode('|', $config->get('fuzzySearchSelectPerPage')));

        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $this->Helper()->addSearch($term, $pageOffset, $hitCount, 'product', $sort, $options);
        $templateBlogSearchProperties = array();

        if($config->get('boxalino_blog_search_enabled')){
            $blogOffset = ($this->Request()->getParam('sBlogPage', 1) -1)*($hitCount);
            $this->Helper()->addSearch($term, $blogOffset, $hitCount, 'blog');
            $templateBlogSearchProperties = $this->getSearchTemplateProperties($hitCount);
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->Helper()->addNotification("Search before response took in total: " . (microtime(true)- $start) * 1000 . "ms.");
        }

        if($this->Helper()->getResponse()->getRedirectLink() != '' && $this->Request()->getParam('bxActiveTab') !== 'blog') {
            $this->Controller()->redirect($this->Helper()->getResponse()->getRedirectLink());
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $afterStart = microtime(true);
            $this->Helper()->addNotification("Search after response: " . $afterStart);
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $beforeUpdate = microtime(true);
        }
        $corrected = false;
        $articles = array();
        $no_result_articles = array();
        $sub_phrases = array();
        $totalHitCount = 0;
        $sub_phrase_limit = $config->get('boxalino_search_subphrase_result_limit');
        if ($this->Helper()->areThereSubPhrases() && $sub_phrase_limit > 0) {
            $sub_phrase_queries = array_slice(array_filter($this->Helper()->getSubPhrasesQueries()), 0, $sub_phrase_limit);
            foreach ($sub_phrase_queries as $query){
                $ids = array_slice($this->Helper()->getSubPhraseFieldValues($query, 'products_ordernumber'), 0, $config->get('boxalino_search_subphrase_product_limit'));
                $suggestion_articles = [];
                if (count($ids) > 0) {
                    $suggestion_articles = $this->BxData()->getLocalArticles($ids);
                }
                $hitCount = $this->Helper()->getSubPhraseTotalHitCount($query);
                $sub_phrases[] = array('hitCount'=> $hitCount, 'query' => $query, 'articles' => $suggestion_articles);
            }
            $facets = array();
        } else {
            if ($totalHitCount = $this->Helper()->getTotalHitCount()) {
                if($totalHitCount == 1 && $config->get('boxalino_redirect_search_enabled')) {
                    $ids = $this->Helper()->getEntitiesIds();
                    $location = $this->Controller()->Front()->Router()->assemble(array('sViewport' => 'detail', 'sArticle' => $ids[0]));
                    return $this->Controller()->redirect($location);
                }
                if ($this->Helper()->areResultsCorrected()) {
                    $corrected = true;
                    $term = $this->Helper()->getCorrectedQuery();
                }
                $ids = $this->Helper()->getHitFieldValues('products_ordernumber');
                if ($_REQUEST['dev_bx_debug'] == 'true') {
                    $localTime = microtime(true);
                }
                $articles = $this->BxData()->getLocalArticles($ids);
                if ($_REQUEST['dev_bx_debug'] == 'true') {
                    $this->Helper()->addNotification("Search getLocalArticles took: " . (microtime(true) - $localTime) * 1000 . "ms");
                }
                if ($_REQUEST['dev_bx_debug'] == 'true') {
                    $this->Helper()->addNotification("Search beforeUpdateFacets took: " . (microtime(true) - $beforeUpdate) * 1000 . "ms");
                }
                if ($_REQUEST['dev_bx_debug'] == 'true') {
                    $updateFacets = microtime(true);
                }
                $facets = $this->updateFacetsWithResult($facets, $context);
                if ($_REQUEST['dev_bx_debug'] == 'true') {
                    $this->Helper()->addNotification("Search updateFacetsWithResult took: " . (microtime(true) - $updateFacets) * 1000 . "ms");
                }
                if ($_REQUEST['dev_bx_debug'] == 'true') {
                    $afterUpdate = microtime(true);
                }
            } else {
                if ($config->get('boxalino_noresults_recommendation_enabled')) {
                    $this->Helper()->resetRequests();
                    $this->Helper()->flushResponses();
                    $min = $config->get('boxalino_noresults_recommendation_min');
                    $max = $config->get('boxalino_noresults_recommendation_max');
                    $choiceId = $config->get('boxalino_noresults_recommendation_name');
                    $this->Helper()->getRecommendation($choiceId, $max, $min, 0, [], '', false);
                    $hitIds = $this->Helper()->getRecommendation($choiceId);
                    $no_result_articles = $this->BxData()->getLocalArticles($hitIds);
                }
                $facets = array();
            }
        }
        $request = $this->Request();
        $params = $request->getParams();
        $params['sSearchOrginal'] = $term;
        $params['sSearch'] = $term;

        // Assign result to template
        $this->View()->loadTemplate('frontend/search/fuzzy.tpl');
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-pagination.tpl');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/search/fuzzy.tpl');
        if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/facet-value-list.tpl');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index.tpl');
            if($this->Helper()->getTotalHitCount('blog')) {
                $this->View()->extendsTemplate('frontend/plugins/boxalino/blog/listing_actions.tpl');
            }
        } else {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/_includes/filter-multi-selection.tpl');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index_5_3.tpl');
            if($this->Helper()->getTotalHitCount('blog')) {
                $this->View()->extendsTemplate('frontend/plugins/boxalino/blog/listing_actions.tpl');
            }
            $service = $this->get('shopware_storefront.custom_sorting_service');
            $sortingIds = $this->container->get('config')->get('searchSortings');
            $sortingIds = array_filter(explode('|', $sortingIds));
            $sortings = $service->getList($sortingIds, $context);
        }
        $no_result_title = Shopware()->Snippets()->getNamespace('boxalino/intelligence')->get('search/noresult');
        $templateProperties = array_merge(array(
            'bxFacets' => $this->Helper()->getFacets(),
            'term' => $term,
            'corrected' => $corrected,
            'bxNoResult' => count($no_result_articles) > 0,
            'BxData' => [
                'article_slider_title'=> $no_result_title,
                'no_border'=> true,
                'article_slider_type' => 'selected_article',
                'values' => $no_result_articles,
                'article_slider_max_number' => count($no_result_articles),
                'article_slider_arrows' => 1
            ],
            'criteria' => $criteria,
            'sortings' => $sortings,
            'facets' => $facets,
            'sPage' => $request->getParam('sPage', 1),
            'sSort' => $request->getParam('sSort', 7),
            'sTemplate' => $params['sTemplate'],
            'sPerPage' => $pageCounts,
            'sRequests' => $params,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'pageSizes' => $pageCounts,
            'ajaxCountUrlParams' => version_compare(Shopware::VERSION, '5.3.0', '<') ?
                ['sCategory' => $context->getShop()->getCategory()->getId()] : [],
            'sSearchResults' => array(
                'sArticles' => $articles,
                'sArticlesCount' => $totalHitCount
            ),
            'productBoxLayout' => $config->get('searchProductBoxLayout'),
            'bxHasOtherItemTypes' => false,
            'bxActiveTab' => (count($no_result_articles) > 0) ? $request->getParam('bxActiveTab', 'blog'): $request->getParam('bxActiveTab', 'article'),
            'bxSubPhraseResults' => $sub_phrases,
            'facetOptions' => $this->facetOptions
        ), $templateBlogSearchProperties);
        $this->View()->assign($templateProperties);
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->Helper()->addNotification("Search afterUpdateFacets took: " . (microtime(true) - $afterUpdate) * 1000 . "ms");
            $this->Helper()->addNotification("Search after response took in total: " . (microtime(true) - $afterStart) * 1000 . "ms.");
            $this->Helper()->addNotification("Search time took in total: " . (microtime(true) - $start) * 1000 . "ms.");
            $this->Helper()->callNotification(true);
        }
        return false;
    }

    /**
     * @param $hitCount
     * @return array
     */
    private function getSearchTemplateProperties($hitCount)
    {
        $props = array();
        $total = $this->Helper()->getTotalHitCount('blog');
        if ($total == 0) {
            return $props;
        }
        $sPage = $this->Request()->getParam('sBlogPage', 1);
        $entity_ids = $this->Helper()->getEntitiesIds('blog');
        if (!count($entity_ids)) {
            return $props;
        }
        $ids = array();
        foreach ($entity_ids as $id) {
            $ids[] = str_replace('blog_', '', $id);
        }
        $count = count($ids);
        $numberPages = ceil($count > 0 ? $total / $hitCount : 0);
        $props['bxBlogCount'] = $total;
        $props['sNumberPages'] = $numberPages;
        $props['bxHasOtherItemTypes'] = true;

        $pages = array();
        if ($numberPages > 1) {
            $params = array_merge($this->Request()->getParams(), array('bxActiveTab' => 'blog'));
            for ($i = 1; $i <= $numberPages; $i++) {
                $pages["numbers"][$i]["markup"] = $i == $sPage;
                $pages["numbers"][$i]["value"] = $i;
                $pages["numbers"][$i]["link"] = $this->assemble(array_merge($params, array('sBlogPage' => $i)));
            }
            if ($sPage > 1) {
                $pages["previous"] = $this->assemble(array_merge($params, array('sBlogPage' => $sPage - 1)));
            } else {
                $pages["previous"] = null;
            }
            if ($sPage < $numberPages) {
                $pages["next"] = $this->assemble(array_merge($params, array('sBlogPage' => $sPage + 1)));
            } else {
                $pages["next"] = null;
            }
        }

        $props['sBlogPage'] = $sPage;
        $props['sPages'] = $pages;
        $blogArticles = $this->enhanceBlogArticles($this->Helper()->getBlogs($ids));
        $props['sBlogArticles'] = $blogArticles;

        return $props;
    }

    /**
     * @param $params
     * @return string
     */
    private function assemble($params) {
        $p = $this->Request()->getBasePath() . $this->Request()->getPathInfo();
        if (empty($params)) return $p;

        $ignore = array("module" => 1, "controller" => 1, "action" => 1);
        $kv = [];
        array_walk($params, function($v, $k) use (&$kv, &$ignore) {
            if ($ignore[$k]) return;

            $kv[] = $k . '=' . $v;
        });
        return $p . "?" . implode('&', $kv);
    }

    /**
     * mostly copied from Frontend/Blog.php#indexAction
     * @param $blogArticles
     * @return mixed
     */
    public function enhanceBlogArticles($blogArticles) {
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);
        $context = $this->Bootstrap()->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->Bootstrap()->get('shopware_storefront.media_service')->getList($mediaIds, $context);

        foreach ($blogArticles as $key => $blogArticle) {
            //adding number of comments to the blog article
            $blogArticles[$key]["numberOfComments"] = count($blogArticle["comments"]);

            //adding thumbnails to the blog article
            if (empty($blogArticle["media"][0]['mediaId'])) {
                continue;
            }

            $mediaId = $blogArticle["media"][0]['mediaId'];

            if (!isset($medias[$mediaId])) {
                continue;
            }

            /**@var $media \Shopware\Bundle\StoreFrontBundle\Struct\Media*/
            $media = $medias[$mediaId];
            $media = $this->get('legacy_struct_converter')->convertMediaStruct($media);

            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }

    /**
     * @param $facet
     * @return array
     */
    protected function getValueIds($facet) {
        if ($facet instanceof Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup) {
            $ids = array();
            foreach ($facet->getfacetResults() as $facetResult) {
                $ids = array_merge($ids, $this->getValueIds($facetResult));
            }
            return $ids;
        } else {
            return array_map(function($value) { return $value->getId(); }, $facet->getValues());
        }
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name) {
        return $this->container->get($name);
    }

    /**
     * @return mixed|string
     */
    protected function getSearchTerm() {
        $term = $this->Request()->get('sSearch', '');
        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));

        // we have to strip the / otherwise broken urls would be created e.g. wrong pager urls
        $term = str_replace('/', '', $term);

        return $term;
    }

    /**
     * @param $search
     * @return mixed|string
     */
    protected function searchFuzzyCheck($search) {
        $minSearch = empty($this->Config()->sMINSEARCHLENGHT) ? 2 : (int) $this->Config()->sMINSEARCHLENGHT;
        $db = Shopware()->Db();
        if (!empty($search) && strlen($search) >= $minSearch) {
            $ordernumber = $db->quoteIdentifier('ordernumber');
            $sql = $db->select()
                ->distinct()
                ->from('s_articles_details', array('articleID'))
                ->where("$ordernumber = ?", $search)
                ->limit(2);
            $articles = $db->fetchCol($sql);

            if (empty($articles)) {
                $percent = $db->quote('%');
                $sql->orWhere("? LIKE CONCAT($ordernumber, $percent)", $search);
                $articles = $db->fetchCol($sql);
            }
        }
        if (!empty($articles) && count($articles) == 1) {
            $sql = $db->select()
                ->from(array('ac' => 's_articles_categories_ro'), array('ac.articleID'))
                ->joinInner(
                    array('c' => 's_categories'),
                    $db->quoteIdentifier('c.id') . ' = ' . $db->quoteIdentifier('ac.categoryID') . ' AND ' .
                    $db->quoteIdentifier('c.active') . ' = ' . $db->quote(1) . ' AND ' .
                    $db->quoteIdentifier('c.id') . ' = ' . $db->quote(Shopware()->Shop()->get('parentID'))
                )
                ->where($db->quoteIdentifier('ac.articleID') . ' = ?', $articles[0])
                ->limit(1);
            $articles = $db->fetchCol($sql);
        }
        if (!empty($articles) && count($articles) == 1) {
            return $this->Controller()->Front()->Router()->assemble(array('sViewport' => 'detail', 'sArticle' => $articles[0]));
        }
    }

    /**
     * @return array
     */
    protected function registerFacetHandlers() {
        // did not find a way to use the service tag "facet_handler_dba"
        // it seems the dependency injection CompilerPass is not available to plugins?
        $facetHandlerIds = [
            'vote_average',
            'shipping_free',
            'product_attribute',
            'immediate_delivery',
            'manufacturer',
            'property',
            'category',
            'price',
        ];
        $facetHandlers = [];
        foreach ($facetHandlerIds as $id) {
            $facetHandlers[] = $this->container->get("shopware_searchdbal.${id}_facet_handler_dbal");
        }
        return $facetHandlers;
    }

    /**
     * @param \Shopware\Bundle\SearchBundle\FacetInterface $facet
     * @return FacetHandlerInterface|null|\Shopware\Bundle\SearchBundle\FacetHandlerInterface
     */
    protected function getFacetHandler(Shopware\Bundle\SearchBundle\FacetInterface $facet) {
        if ($this->facetHandlers == null) {
            $this->facetHandlers = $this->registerFacetHandlers();
        }
        foreach ($this->facetHandlers as $handler) {
            if ($handler->supportsFacet($facet)) {
                return $handler;
            }
        }
        return null;
    }

    /**
     * @param $value_id
     * @return string
     */
    private function getOptionIdFromValue($value_id) {
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from('s_filter_values', array('optionId'))
            ->where('s_filter_values.id = ?', $value_id);
        return $db->fetchOne($sql);
    }

    /**
     * @param $values
     * @return null
     */
    protected function getLowestActiveTreeItem($values) {
        foreach ($values as $value) {
            $innerValues = $value->getValues();
            if (count($innerValues)) {
                $innerValue = $this->getLowestActiveTreeItem($innerValues);
                if ($innerValue instanceof Shopware\Bundle\SearchBundle\FacetResult\TreeItem) {
                    return $innerValue;
                }
            }
            if ($value->isActive()) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param $id
     * @return mixed
     */
    private function getMediaById($id)
    {
        return $this->get('shopware_storefront.media_service')
            ->get($id, $this->get('shopware_storefront.context_service')->getProductContext());
    }

    /**
     * @param $bxFacets
     * @param $facet
     * @param $lang
     * @return \Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult|void
     */
    private function generateManufacturerListItem($bxFacets, $facet, $lang) {
        $db = Shopware()->Db();
        $fieldName = 'products_brand';
        $where_statement = '';
        $values = $bxFacets->getFacetValues($fieldName);
        if(sizeof($values) == 0){
            return;
        }
        foreach ($values as $index => $value) {
            if($index > 0) {
                $where_statement .= ' OR ';
            }
            $where_statement .= 'a_s.name LIKE \'%'. addslashes($value) .'%\'';
        }

        $sql = $db->select()
            ->from(array('a_s' => 's_articles_supplier', array('a_s.id', 'a_s.name')))
            ->where($where_statement);
        $result = $db->fetchAll($sql);
        $showCount = $bxFacets->showFacetValueCounters($fieldName);
        $values = $this->useValuesAsKeys($values);
        foreach ($result as $r) {
            $label = trim($r['name']);
            if(!isset($values[$label])) {
                continue;
            }
            $selected = $bxFacets->isFacetValueSelected($fieldName, $label);
            $values[$label] = new Shopware\Bundle\SearchBundle\FacetResult\MediaListItem(
                (int)$r['id'],
                $showCount ? $label . ' (' . $bxFacets->getFacetValueCount($fieldName, $label) . ')' : $label,
                $selected
            );
        }
        $finalValues = array();
        foreach ($values as $key => $innerValue) {
            if(!is_string($innerValue)) {
                $finalValues[] = $innerValue;
            }
        }
        $mapper = $this->get('query_alias_mapper');
        return new Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult(
            'manufacturer',
            $bxFacets->isSelected($fieldName),
            $bxFacets->getFacetLabel($fieldName, $lang),
            $finalValues,
            $mapper->getShortAlias('sSupplier')
        );
    }

    private function getFacetValuesResult($option_id, $values, $translation){
        $shop_id = $this->BxData()->getShopId();
        $where_statement = '';
        $db = Shopware()->Db();
        foreach ($values as $index => $value) {
            $id = end(explode("_bx_", $value));
            if($index > 0) {
                $where_statement .= ' OR ';
            }
            $where_statement .= 'v.id = '. $db->quote($id);
        }
        $sql = $db->select()
            ->from(array('v' => 's_filter_values', array()))
            ->where($where_statement)
            ->where('v.optionID = ?', $option_id);
        if($translation == true) {
            $sql = $sql
                ->joinLeft(array('t' => 's_core_translations'),
                    't.objectkey = v.id AND t.objecttype = ' . $db->quote('propertyvalue') . ' AND t.objectlanguage = ' . $shop_id,
                    array('objectdata'));
        }
        $result = $db->fetchAll($sql);
        return $result;
    }

    private function getCategoriesOfParent($categories, $parentId)
    {
        $result = [];
        foreach ($categories as $category) {
            if (!$category->getPath() && $parentId !== null) {
                continue;
            }

            if ($category->getPath() == $parentId) {
                $result[] = $category;
                continue;
            }

            $parents = $category->getPath();
            $lastParent = $parents[count($parents) - 1];

            if ($lastParent == $parentId) {
                $result[] = $category;
            }
        }
        return $result;
    }

    private function createTreeItem($categories, $category, $active, $showCount, $bxFacets)
    {
        $children = $this->getCategoriesOfParent(
            $categories,
            $category->getId()
        );

        $values = [];
        foreach ($children as $child) {
            $values[] = $this->createTreeItem($categories, $child, $active, $showCount, $bxFacets);
        }
        $name = $category->getName();
        if($showCount) {
            $cat = $bxFacets->getCategoryById($category->getId());
            $name .= " (" . $bxFacets->getCategoryValueCount($cat) . ")";
        }
        return new TreeItem(
            $category->getId(),
            $name,
            in_array($category->getId(), $active),
            $values,
            $category->getAttributes()
        );
    }

    private function generateTreeResult($facet, $selectedCategoryId, $categories, $label, $bxFacets, $categoryFieldName){

        $parent = null;
        $values = [];
        $showCount = $bxFacets->showFacetValueCounters('categories');
        if(version_compare(Shopware::VERSION, '5.3.0', '>=')){
            if(sizeof($selectedCategoryId) == 1 && (reset($selectedCategoryId) == Shopware()->Shop()->getCategory()->getId())) {
                $selectedCategoryId = [];
            }
            $parent = Shopware()->Shop()->getCategory()->getId();
        }

        $items = $this->getCategoriesOfParent($categories, $parent);
        foreach ($items as $item) {
            $values[] = $this->createTreeItem($categories, $item, $selectedCategoryId, $showCount, $bxFacets);
        }

        if(empty($values))
        {
            return array();
        }

        return new TreeFacetResult(
            $facet->getName(),
            $categoryFieldName,
            !empty($selectedCategoryId),
            $label,
            $values,
            [],
            version_compare(Shopware::VERSION, '5.3.0', '<') ? null :
                'frontend/listing/filter/facet-value-tree.tpl'
        );
    }

    /**
     * @param $fieldName
     * @param $bxFacets
     * @param $facet
     * @param $lang
     */
    private function generateListItem($fieldName, $bxFacets, $facet, $lang, $useTranslation, $propertyFieldName)
    {
        if(is_null($facet)) {
            return;
        }
        $option_id = end(explode('_', $fieldName));
        $values = $bxFacets->getFacetValues($fieldName);

        if(sizeof($values) == 0) {
            return;
        }

        $result = $this->getFacetValuesResult($option_id, $values, $useTranslation);
        $media_class = false;
        $showCount = $bxFacets->showFacetValueCounters($fieldName);
        $values = $this->useValuesAsKeys($values);
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);
        }

        foreach ($result as $r) {
            if($useTranslation == true && isset($r['objectdata'])) {
                $translation = unserialize($r['objectdata']);
                $r['value'] = isset($translation['optionValue']) && $translation['optionValue'] != '' ?
                    $translation['optionValue'] : $r['value'];
            }
            $label = trim($r['value']);
            $key = $label . "_bx_{$r['id']}";
            if(!isset($values[$key])) {
                continue;
            }

            $selected = $bxFacets->isFacetValueSelected($fieldName, $key);
            if ($showCount) {
                $label .= ' (' . $bxFacets->getFacetValueCount($fieldName, $key) . ')';
            }
            $media = $r['media_id'];
            if (!is_null($media)) {
                $media = $this->getMediaById($media);
                $media_class = true;
            }
            $values[$key] = new Shopware\Bundle\SearchBundle\FacetResult\MediaListItem(
                (int)$r['id'],
                $label,
                (boolean)$selected,
                $media
            );
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $t1) * 1000 ;
            $this->Helper()->addNotification("Search generateListItem for $fieldName: " . $t1 . "ms.");
        }
        $finalValues = array();
        foreach ($values as $key => $innerValue) {
            if(!is_string($innerValue)) {
                $finalValues[] = $innerValue;
            }
        }
        $class = $media_class === true ? 'Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult' :
            'Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult';

        return new $class(
            $facet->getName(),
            $bxFacets->isSelected($fieldName),
            $bxFacets->getFacetLabel($fieldName,$lang),
            $finalValues,
            $propertyFieldName
        );
    }

    protected function getCategoryFacet() {
        $snippetManager = Shopware()->Snippets()->getNamespace('frontend/listing/facet_labels');
        $label = $snippetManager->get('category', 'Kategorie');
        $depth = $this->Config()->get('levels');
        return new \Shopware\Bundle\SearchBundle\Facet\CategoryFacet($label, $depth);
    }

    public function getFacetOptions() {
        return $this->facetOptions;
    }

    /**
     * @param $facets
     * @return array
     */
    public function updateFacetsWithResult($facets, $context, $request = null, $choice = '') {
        $request = is_null($request) ? $this->Request() : $request;
        $start = microtime(true);
        $lang = substr(Shopware()->Shop()->getLocale()->getLocale(), 0, 2);
        $this->facetOptions['mode'] = $this->Config()->get('listingMode');
        $variant_index = $choice == '' ? null : 0;
        $bxFacets = $this->Helper()->getFacets('product', $choice, $variant_index);
        $propertyFacets = [];
        $filters = array();
        $mapper = $this->get('query_alias_mapper');
        $request = is_null($request) ? $this->Request() : $request;
        if(!$propertyFieldName = $mapper->getShortAlias('sFilterProperties')) {
            $propertyFieldName = 'sFilterProperties';
        }
        $useTranslation = $this->BxData()->useTranslation('propertyvalue');
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);

        }
        $leftFacets = $bxFacets->getLeftFacets();
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $t1) * 1000 ;
            $this->Helper()->addNotification("Search getLeftFacets took: " . $t1 . "ms.");

        }
        foreach ($leftFacets as $fieldName) {
            $key = '';
            if ($bxFacets->isFacetHidden($fieldName)) {
                continue;
            }

            switch ($fieldName) {
                case 'discountedPrice':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    if(isset($facets['price'])){
                        $facet = $facets['price'];
                        $selectedRange = $bxFacets->getSelectedPriceRange();
                        $label = trim($bxFacets->getFacetLabel($fieldName,$lang));
                        $this->facetOptions[$label] = [
                            'fieldName' => $fieldName,
                            'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                        ];
                        $priceRange = explode('-', $bxFacets->getPriceRanges()[0]);
                        $from = (float) $priceRange[0];
                        $to = (float) $priceRange[1];
                        if($selectedRange == '0-0'){
                            $activeMin = $from;
                            $activeMax = $to;
                        } else {
                            $selectedRange = explode('-', $selectedRange);
                            $activeMin = $selectedRange[0];
                            $activeMax = $selectedRange[1];
                        }

                        $result = new Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult(
                            $facet->getName(),
                            $selectedRange == '0-0' ? false : $bxFacets->isSelected($fieldName),
                            $label,
                            $from,
                            $to,
                            $activeMin,
                            $activeMax,
                            $mapper->getShortAlias('priceMin'),
                            $mapper->getShortAlias('priceMax')
                        );
                        $result->setTemplate('frontend/listing/filter/facet-currency-range.tpl');
                        $filters[] = $result;
                    }
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->Helper()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'categories':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    $facet = isset($facets['category']) ? $facets['category'] : $this->getCategoryFacet();
                    $selectedCategoryId = $bxFacets->getSelectedCategoryIds();
                    $shopCategory = Shopware()->Shop()->getCategory()->getName();
                    $shopCategoryId = Shopware()->Shop()->getCategory()->getId();

                    $ids = array();
                    if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
                        foreach ($bxFacets->getCategories() as $c) {
                            $id = reset(explode('/', $c));
                            $ids[$id] = $id;
                        }
                        if (!$categoryFieldName = $mapper->getShortAlias('sCategory')) {
                            $categoryFieldName = 'sCategory';
                        }
                    } else {
                        foreach (range(0, $facet->getDepth()) as $i) {
                            $levelCategories = $bxFacets->getCategoryFromLevel($i);
                            foreach ($levelCategories as $lc) {
                                if(strpos($lc, $shopCategory) !== false) {
                                    $id = reset(explode("/", $lc));
                                    if($id != $shopCategoryId) {
                                        $ids[$id] = $id;
                                    }
                                }
                            }
                        }
                        if (!$categoryFieldName = $mapper->getShortAlias('categoryFilter')) {
                            $categoryFieldName = 'categoryFilter';
                        }
                    }
                    if(reset($selectedCategoryId) != $shopCategoryId) {
                        foreach ($bxFacets->getParentCategories() as $category_id => $parent){
                            if(($category_id != $shopCategoryId) && !isset($ids[$category_id])) {
                                $ids[] = $category_id;
                            }
                        }
                    }
                    $label = $bxFacets->getFacetLabel($fieldName,$lang);
                    $categories = $this->get('shopware_storefront.category_service')->getList($ids, $context);
                    $treeResult = $this->generateTreeResult($facet, $selectedCategoryId, $categories, $label, $bxFacets, $categoryFieldName);

                    if(empty($treeResult))
                    {
                        unset($facets['categories']);
                    } else {
                        $filters[] = $treeResult;
                    }

                    $this->facetOptions[$label] = [
                        'fieldName' => $fieldName,
                        'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                    ];
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->Helper()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'products_shippingfree':
                    $key = 'shipping_free';
                case 'products_immediate_delivery':
                    if($key == '') {
                        $key = 'immediate_delivery';
                    }
                    $facet = $facets[$key];
                    $facetFieldName = $key == 'shipping_free' ? $mapper->getShortAlias('shippingFree') : $mapper->getShortAlias('immediateDelivery');

                    $facetValues = $bxFacets->getFacetValues($fieldName);
                    if($facetValues && sizeof($facetValues) == 1 && reset($facetValues) == 0) {
                        break;
                    }
                    $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\BooleanFacetResult(
                        $facet->getName(),
                        $facetFieldName,
                        $bxFacets->isSelected($fieldName),
                        $bxFacets->getFacetLabel($fieldName,$lang),
                        []
                    );
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $start) * 1000 ;
                        $this->Helper()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'products_brand':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    $params = $request->getParams();
                    $id = isset($params[$mapper->getShortAlias('sSupplier')]) ? $params[$mapper->getShortAlias('sSupplier')] : null;
                    $values = $bxFacets->getFacetSelectedValues($fieldName);
                    if(sizeof($values) > 0 && is_null($id)) {
                        break;
                    }
                    $facet = $facets['manufacturer'];
                    $returnFacet = $this->generateManufacturerListItem($bxFacets, $facet, $lang);
                    if($returnFacet) {
                        $this->facetOptions[$bxFacets->getFacetLabel($fieldName,$lang)] = [
                            'fieldName' => $fieldName,
                            'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                        ];
                        $filters[] = $returnFacet;
                    }
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->Helper()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'di_rating':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    $facet = $facets['vote_average'];
                    $values = $bxFacets->getFacetValues($fieldName);
                    $data = array();
                    $selectedValue = null;
                    $selected = $bxFacets->isSelected($fieldName);
                    $selectedValues = $bxFacets->getSelectedValues($fieldName);
                    $setMin = !empty($selectedValues) ? min($selectedValues) : null;

                    if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
                        foreach (range(1, 5) as $i) {
                            $data[] = new ValueListItem($i, (string) '', $setMin == $i);
                        }
                    } else {
                        $values = array_reverse($values);
                        foreach ($values as $value) {
                            if($value == 0) continue;
                            $count = $bxFacets->getFacetValueCount($fieldName, $value);
                            $data[] = new ValueListItem($value, (string) $count, $setMin == $value);
                        }
                    }

                    if (!$facetFieldName = $mapper->getShortAlias('rating')) {
                        $facetFieldName = 'rating';
                    }
                    $filters[] =  new RadioFacetResult(
                        $facet->getName(),
                        $selected,
                        $bxFacets->getFacetLabel($fieldName,$lang),
                        $data,
                        $facetFieldName,
                        [],
                        'frontend/listing/filter/facet-rating.tpl'
                    );
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->Helper()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                default:
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    if ((strpos($fieldName, 'products_optionID_mapped') !== false)) {
                        $facet = $facets['property'];
                        $returnFacet = $this->generateListItem($fieldName, $bxFacets, $facet, $lang, $useTranslation, $propertyFieldName);
                        if($returnFacet) {
                            $this->facetOptions[$bxFacets->getFacetLabel($fieldName, $lang)] = [
                                'fieldName' => $fieldName,
                                'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                            ];
                            if( $this->facetOptions['mode'] == 'filter_ajax_reload'){
                                $propertyFacets[] = $returnFacet;
                            } else {
                                $filters[] = $returnFacet;
                            }
                        }
                    }
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->Helper()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
            }
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $start) * 1000 ;
            $this->Helper()->addNotification("Search updateFacets after for loop: " . $t1 . "ms.");
        }

        if( $this->facetOptions['mode'] == 'filter_ajax_reload') {
            $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup($propertyFacets, null, 'property');
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $start) * 1000 ;
            $this->Helper()->addNotification("Search updateFacets after took: " . $t1 . "ms.");
        }
        return $filters;
    }

    /**
     * @param $array
     * @return array
     */
    public function useValuesAsKeys($array){
        return array_combine(array_keys(array_flip($array)),$array);
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResult\TreeItem[] $values
     * @param com\boxalino\p13n\api\thrift\FacetValue[] $FacetValues
     * @return Shopware\Bundle\SearchBundle\FacetResult\TreeItem[]
     */
    protected function updateTreeItemsWithFacetValue($values, $resultFacet) {
        foreach ($values as $key => $value) {
            $id = (string) $value->getId();
            $label = $value->getLabel();
            $innerValues = $value->getValues();

            if (count($innerValues)) {
                $innerValues = $this->updateTreeItemsWithFacetValue($innerValues, $resultFacet);
            }

            $category = $resultFacet->getCategoryById($id);
            $showCounter = $resultFacet->showFacetValueCounters('categories');
            if ($category && $showCounter) {
                $label .= ' (' . $resultFacet->getCategoryValueCount($category) . ')';
            } else {
                if (sizeof($innerValues)==0) {
                    continue;
                }
            }

            $finalVals[] = new Shopware\Bundle\SearchBundle\FacetResult\TreeItem(
                "{$value->getId()}",
                $label,
                $value->isActive(),
                $innerValues,
                $value->getAttributes()
            );
        }
        return $finalVals;
    }

    private function loadThemeConfig($return = false)
    {
        $inheritance = $this->container->get('theme_inheritance');

        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = $this->container->get('Shop');

        $config = $inheritance->buildConfig($shop->getTemplate(), $shop, false);

        $this->get('template')->addPluginsDir(
            $inheritance->getSmartyDirectories(
                $shop->getTemplate()
            )
        );

        if($return) {
            return $config;
        }
        $this->View()->assign('theme', $config);
    }

    private function convertArticlesResult($articles, $categoryId)
    {
        $router = $this->get('router');
        if (empty($articles)) {
            return $articles;
        }
        $urls = array_map(function ($article) use ($categoryId) {
            if ($categoryId !== null) {
                return $article['linkDetails'] . '&sCategory=' . (int) $categoryId;
            }

            return $article['linkDetails'];
        }, $articles);
        $rewrite = $router->generateList($urls);
        foreach ($articles as $key => &$article) {
            if (!array_key_exists($key, $rewrite)) {
                continue;
            }
            $article['linkDetails'] = $rewrite[$key];
        }
        return $articles;
    }

    private function convertFacetsToJson(){
        $json = [];
        $bxFacets =  $this->Helper()->getFacets();
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
                        $attributeModel = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeName)->getSource();
                        $options = $attributeModel->getAllOptions();
                        $responseValues =  $this->useValuesAsKeys($json['facets'][$fieldName]['facetValues']);
                        foreach ($options as $option){
                            $label = is_array($option) ? $option['label'] : $option;
                            if(isset($responseValues[$label])){
                                $facetMapping[$label] = $option['value'];
                            }
                        }
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
            $json['contextParameterPrefix'] = $this->Helper()->getPrefixContextParameter();
            $json['level'] = $this->getFinderLevel();
            $json['separator'] = '|';

        }
        return json_encode($json);
    }

    /**
     * @param Enlight_Controller_Request_Request $request
     * @return Enlight_Controller_Request_Request
     */
    public function setRequestWithRefererParams($request) {

        $address = $_SERVER['HTTP_REFERER'];
        $basePath = $request->getBasePath();
        $start = strpos($address, $basePath) + strlen($basePath);
        $end = strpos($address, '?');
        $length = $end ? $end - $start : strlen($address);
        $pathInfo = substr($address, $start, $length);
        $request->setPathInfo($pathInfo);
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));
        foreach ($params as $index => $param){
            $keyValue = explode("=", $param);
            $params[$keyValue[0]] = $keyValue[1];
            unset($params[$index]);
        }
        foreach ($params as $key => $value) {
            $request->setParam($key, $value);
            if($key == 'p') {
                $request->setParam('sPage', (int) $value);
            }
        }
        return $request;
    }

    /**
     * @param Enlight_View_Default $view
     * @return Enlight_View_Default
     */
    private function prepareViewConfig($view) {
        $inheritance = $this->container->get('theme_inheritance');

        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = $this->container->get('Shop');

        $config = $inheritance->buildConfig($shop->getTemplate(), $shop, false);

        $this->get('template')->addPluginsDir(
            $inheritance->getSmartyDirectories(
                $shop->getTemplate()
            )
        );
        $view->assign('theme', $config);
        return $view;
    }

    private function prepareVoucherTemplate($data){
        if(!is_null($data['template']) && $data['template'] != '') {
            $template = html_entity_decode($data['template']);
            $properties = array_keys($data);
            foreach ($properties as $property) {
                $template = str_replace("%%{$property}%%", $data[$property], $template);
            }
            $data['template'] = $template;
        }
        return $data;
    }

    public function checkParams(){
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

    public function getFinderLevel(){
        $ids = $this->Helper()->getEntitiesIds();
        $level = 10;
        $h = 0;
        foreach ($ids as $id) {
            if($this->Helper()->getHitVariable($id, 'highlighted')){
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

    private function unserialize($serialized)
    {
        $reflector = new ReflectionHelper();
        if (empty($serialized)) {
            return [];
        }
        $sortings = [];
        foreach ($serialized as $className => $arguments) {
            $className = explode('|', $className);
            $className = $className[0];
            $sortings[] = $reflector->createInstanceFromNamedArguments($className, $arguments);
        }

        return $sortings;
    }

    /**
     * @deprecated
     * @param $id
     * @param $code
     * @param $modus
     * @return bool
     */
    public function checkVoucher($id, $code, $modus)
    {
        $db = Shopware()->Db();
        $fields = ['*'];
        if($modus != 1) {
            $fields["used_vouchers"] = new Zend_Db_Expr("(SELECT count(*) FROM s_order_details as d WHERE articleordernumber = v.ordercode AND d.ordernumber!='0')");
        }
        $sql = $db->select()->from(array('v' => 's_emarketing_vouchers'), $fields)
            ->where('v.id = ?', $id)
            ->where('v.modus = ?', $modus);

        if($modus == 1) {
            $sql->joinLeft(array('v_c' => 's_emarketing_voucher_codes'),
                'v_c.voucherID = v.id AND v_c.code = ' . $db->quote($code));
        }

        $row = $db->fetchRow($sql);
        if(empty($row)){
            return false;
        }

        if($modus != 1 && ($row['numberofunits'] > $row['used_vouchers'])) {
            return true;
        }

        if($row['cashed'] == 0 && $modus==1){
            return true;
        }

        return false;
    }

}
