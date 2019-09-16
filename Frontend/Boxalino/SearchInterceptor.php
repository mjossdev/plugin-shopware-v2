<?php

use Doctrine\DBAL\Connection;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Components\ReflectionHelper;
use Shopware\Bundle\SearchBundle\FacetResult\TreeFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\TreeItem;
use Shopware\Bundle\SearchBundle\FacetResult\RadioFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\ValueListItem;
/**
 * Class Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
 */
class Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
    extends Shopware_Plugins_Frontend_Boxalino_Interceptor
{

    CONST BOXALINO_PRODUCT_VARIANT_ATTRIBUTE = 'products_ordernumber';

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
     *
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function onNarrativeEmotion($data)
    {
        $narrativeBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative('emotion', $data['choiceId']);
        try {
            $narrativeBundle->setRequest(Shopware()->Front()->Request());
            $narrativeBundle->addRequest();
            $narrativeBundle->addDependencies();

            return $narrativeBundle->getBundle()->getContent();
        } catch(\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            throw new \Exception($exception);
        }
    }


    /**
     * Display narrative for product-finder server-side using emotion
     *
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function onEmotionFinder($data)
    {
        try {
            $finder = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Finder();
            $finder->setViewData($data);
            return $finder->getContent();
        } catch(\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            throw new \Exception($exception);
        }
    }

    /**
     * @return |null
     * @throws Exception
     */
    public function landingPage() {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        $view = new Enlight_View_Default($this->get('Template'));
        $view = $this->prepareViewConfig($view);

        $searchBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Search($this->Helper(), "landingPage");
        try {
            $searchBundle->setRequest(Shopware()->Front()->Request());
            $searchBundle->setChoice("landingpage");
            $searchBundle->addRequest();
            $request = $searchBundle->getRequest();
        } catch(\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            throw new \Exception($exception);
        }

        $articles = [];
        $facets = [];
        if ($totalHitCount = $this->Helper()->getTotalHitCount('product', 'landingpage'))
        {
            $ids = $this->Helper()->getHitFieldValues(self::BOXALINO_PRODUCT_VARIANT_ATTRIBUTE, 'product', 'landingpage');
            $articles = $this->BxData()->getLocalArticles($ids);
            $facets = $searchBundle->getFacetBundle()->updateFacetsWithResult();
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
        $templateProperties = array(
            'sTemplate' => $request->getParam('sTemplate'),
            'sPerPage' => array_values(explode('|', $this->get('config')->get('fuzzySearchSelectPerPage'))),
            'sRequests' => $request->getParams(),
            'ajaxCountUrlParams' => [],
            'sPage' => $request->getParam('sPage', 1),
            'bxFacets' => $searchBundle->getFacets('product', 'landingpage', 0),
            'criteria' => $searchBundle->getCriteria(),
            'facets' => $facets,
            'sortings' => $searchBundle->getStoreSortings(),
            'sNumberArticles' => $totalHitCount,
            'sArticles' => $articles,
            'facetOptions' => $searchBundle->getFacetBundle()->getFacetOptions(),
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
     * @throws Exception
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

        $catId = $this->Request()->getParam('sCategory', null);
        $streamId = $this->BxData()->findStreamIdByCategoryId($catId);
        $listingCount = $this->Request()->getActionName() == 'listingCount';
        if(!$listingCount || (!empty($streamId) && !$this->Config()->get('boxalino_navigation_product_stream'))) {
            return null;
        }

        if(!$this->Config()->get('boxalino_navigation_activate_cache')) {
            $this->Bootstrap()->disableHttpCache();
        }

        $searchBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Search($this->Helper(), 'listingAjax');
        try {
            $searchBundle->setRequest($this->Request());
            $searchBundle->addViewData($this->View()->getAssign());
            $searchBundle->addRequest();
        } catch (Shopware_Plugins_Frontend_Boxalino_Bundle_NullException $exception){
            Shopware()->Container()->get('pluginlogger')->warning($exception);
            return null;
        } catch(\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            throw new \Exception($exception);
        }

        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');

        if(version_compare(Shopware::VERSION, '5.3.0', '>=')) {
            $body['totalCount'] = $this->Helper()->getTotalHitCount();
            $showFacets = $searchBundle->getSearchBundle()->showFacets();
            if ($this->Request()->getParam('loadFacets') && $showFacets) {
                $facets = $searchBundle->getFacetBundle()->updateFacetsWithResult();
                $body['facets'] = array_values($facets);
            }
            if ($this->Request()->getParam('loadProducts')) {
                $boxLayout = $catId ? Shopware()->Modules()->Categories()->getProductBoxLayout($catId) : $this->get('config')->get('searchProductBoxLayout');
                if ($this->Request()->has('productBoxLayout')) {
                    $boxLayout = $this->Request()->get('productBoxLayout');
                }
                $this->View()->assign($this->Request()->getParams());
                $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/filter/_includes/filter-multi-selection.tpl');
                $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/index_5_3.tpl');
                $articles = $this->BxData()->getLocalArticles($this->Helper()->getHitFieldValues(self::BOXALINO_PRODUCT_VARIANT_ATTRIBUTE));
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
                    'pages' => ceil($body['totalCount'] / $sPerPage),
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
            $articles = $this->BxData()->getLocalArticles($this->Helper()->getHitFieldValues(self::BOXALINO_PRODUCT_VARIANT_ATTRIBUTE));
            $viewData['sArticles'] = $articles;
            $this->View()->assign($viewData);
        }

        return true;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return bool
     * @throws Exception
     */
    public function listing(Enlight_Event_EventArgs $arguments) {
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $start = microtime(true);
            $this->Helper()->addNotification("Navigation start: " . $start);
        }
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_navigation_enabled'))
        {
            return null;
        }

        $this->init($arguments);
        if($this->Request()->getActionName() == 'manufacturer') {
            $this->prepareManufacturer();
        }

        $this->checkNarrativeCase();
        if(!$this->Config()->get('boxalino_navigation_activate_cache')) {
            $this->Bootstrap()->disableHttpCache();
        }

        if($this->isNarrative && $this->replaceMain){
            return $this->onNarrative();
        }

        $request = $this->Request();
        $viewData = $this->View()->getAssign();
        $searchBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Search($this->Helper(), 'listing');
        try {
            $searchBundle->setRequest($request);
            $searchBundle->addViewData($viewData);
            $searchBundle->addRequest();
            $request= $searchBundle->getRequest();
            if($this->isNarrative)
            {
                $this->onNarrative();
            }
        } catch (Shopware_Plugins_Frontend_Boxalino_Bundle_NullException $exception){
            Shopware()->Container()->get('pluginlogger')->warning($exception);
            return null;
        } catch(\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            throw new \Exception($exception);
        }

        $redirectLink = $searchBundle->getRedirectLink();
        if(!empty($redirectLink)) {
            $this->Controller()->redirect($redirectLink);
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $afterStart = microtime(true);
            $this->Helper()->addNotification("Navigation after response: " . $afterStart);
        }

        $facets = [];
        $showFacets = $searchBundle->getSearchBundle()->showFacets();
        if ($showFacets) {
            $facets = $searchBundle->getFacetBundle()->updateFacetsWithResult();
        }
        $articles = $this->BxData()->getLocalArticles($this->Helper()->getHitFieldValues(self::BOXALINO_PRODUCT_VARIANT_ATTRIBUTE));

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
        }

        $catId = $request->getParam('sCategory');
        $viewData['sCategoryContent']['productBoxLayout'] = $catId ? Shopware()->Modules()->Categories()->getProductBoxLayout($catId) : $this->get('searchProductBoxLayout');
        if ($request->has('productBoxLayout')) {
            $viewData['sCategoryContent']['productBoxLayout'] = $request->get('productBoxLayout');
        }

        $totalHitCount = $this->Helper()->getTotalHitCount();
        $pageCounts = array_values(explode('|', $this->get('config')->get('numberarticlestoshow')));
        $templateProperties = array(
            'pageSizes' => $pageCounts,
            'sPerPage' => $pageCounts,
            'sPage' => $request->getParam('sPage', 1),
            'bxFacets' => $searchBundle->getFacets(),
            'criteria' => $searchBundle->getCriteria(),
            'facets' => $facets,
            'sortings' => $searchBundle->getStoreSortings(),
            'sNumberArticles' => $totalHitCount,
            'sArticles' => $articles,
            'facetOptions' => $searchBundle->getFacetBundle()->getFacetOptions(),
            'sSort' => $request->getParam('sSort'),
            'showListing' => true,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'bx_request_id' => $this->Helper()->getRequestId(),
            'isNarrative' => $this->isNarrative
        );
        $narrativeTemplateData = [];
        if($this->isNarrative){
            $narrativeTemplateData = $this->getNarrativeTemplateData($viewData['sCategoryContent']['attribute']['narrative_choice']);
        }

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
     * @return array
     * @throws Exception
     */
    public function getNarrativeTemplateData($choiceId)
    {
        $narrative = $this->getNarrativeBundle();
        $narrative->getResponse($choiceId);
        $narrative->addDependencies();
        $bundle = $narrative->getBundle();
        $content = $bundle->getContent();

        $this->View()->extendsTemplate($bundle->getScriptTemplate());
        $this->View()->extendsTemplate($bundle->getMainTemplateNoReplace($content['narrative_block_main_template']));

        return $content;
    }

    /**
     * Set class variable with narrative status for category view
     *
     * @param string $type
     * @return bool
     */
    protected function checkNarrativeCase($type='category')
    {
        $viewData = $this->View()->getAssign();
        if($type=='category')
        {
            if(!isset($viewData['sCategoryContent']['attribute']['narrative_choice']))
            {
                return false;
            }

            if(!empty($viewData['sCategoryContent']['attribute']['narrative_choice'])) {
                $this->isNarrative = true;
            }

            if($viewData['sCategoryContent']['attribute']['narrative_replace_main'])
            {
                $this->replaceMain = true;
            }
        }
    }

    /**
     * Processing narrative request;
     * If finder, a divided logic to be applied
     *
     * @param bool $execute
     * @param string $type - currently supported: category, finder, emotion
     * @param array $filters
     * @return bool
     * @throws Exception
     */
    public function onNarrative($type='category', $filters = [])
    {
        $viewData = $this->View()->getAssign();
        if($type == 'category')
        {
            $choiceId  = $viewData['sCategoryContent']['attribute']['narrative_choice'];
            if(strpos($choiceId, 'productfinder') !== FALSE)
            {
                $hitCount = $viewData['sCategoryContent']['attribute']['narrative_additional_choice'];
                return $this->onFinder($choiceId, $hitCount);
            }

            $additionalChoiceId = $viewData['sCategoryContent']['attribute']['narrative_additional_choice'];
            if($this->replaceMain)
            {
                return $this->executeNarrative($type, $choiceId, $additionalChoiceId);
            }

            return $this->addNarrativeRequest($type, $choiceId, $additionalChoiceId);
        }

        return true;
    }

    /**
     * Show the finder content on replace main
     *
     * @param $choiceId
     * @param $count
     * @param null $template
     * @return mixed
     */
    public function onFinder($choiceId, $count, $template = null)
    {
        $finder = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Finder();
        $finder->setChoice($choiceId);
        $finder->setHitCount($count);
        if(!is_null($template))
        {
            $finder->setMainTemplate($template);
        }

        if(!$this->replaceMain)
        {
            $finder->setMain(false);
        }

        return $finder->render($this->View());
    }

    /**
     * catching a narrative request
     * checking for the choice id and for the additional choice ids to render the requested narrative
     *
     * @param $type
     * @param $choiceId
     * @param null $additionalChoiceId
     * @return bool
     */
    public function executeNarrative($type, $choiceId, $additionalChoiceId = null)
    {
        try {
            $this->addNarrativeRequest($type, $choiceId, $additionalChoiceId);
            $this->getNarrativeBundle()->addDependencies();
            return $this->getNarrativeBundle()->render($this->View());
        }  catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error($e);
            exit;
        }
    }


    /**
     * adding a narrative request
     * checking for the choice id and for the additional choice ids to render the requested narrative
     *
     * @param $type
     * @param $choiceId
     * @param null $additionalChoiceId
     * @return bool
     * @throws Exception
     */
    public function addNarrativeRequest($type, $choiceId, $additionalChoiceId = null)
    {
        try {
            $narrativeBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative($type, $choiceId);
            $narrativeBundle->setAdditionalChoices($additionalChoiceId);
            $narrativeBundle->setRequest($this->Request());
            $narrativeBundle->setExecute($this->replaceMain);
            $narrativeBundle->addViewData($this->View()->getAssign());
            $narrativeBundle->addRequest();

            $this->setNarrativeBundle($narrativeBundle);
            return true;
        }  catch (\Exception $e) {
            Shopware()->Container()->get('pluginlogger')->error($e);
            exit;
        }
    }

    protected $narrativeBundle = null;
    public function setNarrativeBundle($bundle)
    {
        $this->narrativeBundle = $bundle;
        return $this;
    }

    public function getNarrativeBundle()
    {
        return $this->narrativeBundle;
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
     * @throws Exception
     */
    public function blog(Enlight_Event_EventArgs $arguments)
    {
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
     * @throws Exception
     */
    public function search(Enlight_Event_EventArgs $arguments)
    {
        $debug = $_REQUEST['dev_bx_debug'] == 'true';
        if($debug){
            $start = microtime(true);
            $this->Helper()->addNotification("Search start: " . $start);
        }
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled')) {
            return null;
        }
        $this->init($arguments);
        $term = $this->getSearchTerm();
        $location = $this->searchFuzzyCheck($term);         // Check if we have a one to one match for ordernumber, then redirect
        if (!empty($location)) {
            return $this->Controller()->redirect($location);
        }

        if(empty($term))
        {
            Shopware()->Container()->get('pluginlogger')->warning("Boxalino Search: Invalid request; the search term must be provided. Trigger fallback.");
            throw new \Exception("Boxalino Search: Fallback trigger due to missing search term on search request");
        }

        $templateBlogSearchProperties = array();
        $config = $this->get('config');
        $searchBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Search($this->Helper(), 'search');
        try {
            $searchBundle->setRequest($this->Request());
            $searchBundle->addViewData($this->View()->getAssign());
            $searchBundle->addRequest();
            if($config->get('boxalino_blog_search_enabled'))
            {
                $searchBundle->executeBlog();
                $templateBlogSearchProperties = $this->getSearchTemplateProperties($searchBundle->getSearchBundle()->getHitCount());
            }

            $request = $searchBundle->getRequest();
        } catch (Shopware_Plugins_Frontend_Boxalino_Bundle_NullException $exception){
            Shopware()->Container()->get('pluginlogger')->warning($exception);
            return null;
        } catch(\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            throw new \Exception($exception);
        }

        if($debug){
            $this->Helper()->addNotification("Search before response took in total: " . (microtime(true)- $start) * 1000 . "ms.");
        }

        $redirectLink = $searchBundle->getRedirectLink();
        if(!empty($redirectLink) && $this->Request()->getParam('bxActiveTab') !== 'blog') {
            $this->Controller()->redirect($redirectLink);
        }

        if($searchBundle->areResultsCorrectedOnSubPhrases())
        {
            $term = urlencode($this->Helper()->getCorrectedQuery());
            $location =$this->Controller()->Front()->Router()->assemble(array('action'=>'index', 'controller'=>'search')) . "?sSearch=$term";
            if (!empty($location)) {
                return $this->Controller()->redirect($location);
            }
        }

        if($debug){
            $afterStart = microtime(true);
            $this->Helper()->addNotification("Search after response: " . $afterStart);
            $beforeUpdate = microtime(true);
        }

        $corrected = false;
        $articles = [];
        $no_result_articles = [];
        $sub_phrases = [];
        $totalHitCount = 0;
        if ($searchBundle->showSubphrases()) {
            $sub_phrase_queries = array_slice(array_filter($this->Helper()->getSubPhrasesQueries()), 0, $config->get('boxalino_search_subphrase_result_limit'));
            foreach ($sub_phrase_queries as $query){
                $ids = array_slice($this->Helper()->getSubPhraseFieldValues($query, self::BOXALINO_PRODUCT_VARIANT_ATTRIBUTE), 0, $config->get('boxalino_search_subphrase_product_limit'));
                $suggestion_articles = [];
                if (count($ids) > 0) {
                    $suggestion_articles = $this->BxData()->getLocalArticles($ids);
                }
                $hitCount = $this->Helper()->getSubPhraseTotalHitCount($query);
                $sub_phrases[] = array('hitCount'=> $hitCount, 'query' => $query, 'articles' => $suggestion_articles);
            }
            $facets = [];
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
                $ids = $this->Helper()->getHitFieldValues(self::BOXALINO_PRODUCT_VARIANT_ATTRIBUTE);
                if ($debug) {
                    $localTime = microtime(true);
                }
                $articles = $this->BxData()->getLocalArticles($ids);
                if ($debug) {
                    $this->Helper()->addNotification("Search getLocalArticles took: " . (microtime(true) - $localTime) * 1000 . "ms");
                    $this->Helper()->addNotification("Search beforeUpdateFacets took: " . (microtime(true) - $beforeUpdate) * 1000 . "ms");
                    $updateFacets = microtime(true);
                }
                $facets = $searchBundle->getFacetBundle()->updateFacetsWithResult();
                if ($debug) {
                    $this->Helper()->addNotification("Search updateFacetsWithResult took: " . (microtime(true) - $updateFacets) * 1000 . "ms");
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
                $facets = [];
            }
        }
        $params = $request->getParams();
        $params['sSearchOrginal'] = $term;
        $params['sSearch'] = $term;

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
        }

        $no_result_title = Shopware()->Snippets()->getNamespace('boxalino/intelligence')->get('search/noresult');
        $pageCounts = array_values(explode('|', $config->get('fuzzySearchSelectPerPage')));
        $templateProperties = array_merge(array(
            'bxFacets' => $searchBundle->getFacets(),
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
            'criteria' => $searchBundle->getCriteria(),
            'sortings' => $searchBundle->getStoreSortings(),
            'facets' => $facets,
            'sPage' => $request->getParam('sPage', 1),
            'sSort' => $request->getParam('sSort', 7),
            'sTemplate' => $params['sTemplate'],
            'sPerPage' => $pageCounts,
            'sRequests' => $params,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'pageSizes' => $pageCounts,
            'ajaxCountUrlParams' =>  [],
            'sSearchResults' => [
                'sArticles' => $articles,
                'sArticlesCount' => $totalHitCount
            ],
            'productBoxLayout' => $config->get('searchProductBoxLayout'),
            'bxHasOtherItemTypes' => false,
            'bxActiveTab' => (count($no_result_articles) > 0) ? $request->getParam('bxActiveTab', 'blog'): $request->getParam('bxActiveTab', 'article'),
            'bxSubPhraseResults' => $sub_phrases,
            'facetOptions' => $searchBundle->getFacetBundle()->getFacetOptions()
        ), $templateBlogSearchProperties);
        $this->View()->assign($templateProperties);

        if($debug){
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
    protected function getSearchTemplateProperties($hitCount)
    {
        $props = [];
        $total = $this->Helper()->getTotalHitCount('blog');
        if ($total == 0) {
            return $props;
        }
        $sPage = $this->Request()->getParam('sBlogPage', 1);
        $entity_ids = $this->Helper()->getEntitiesIds('blog');
        if (!count($entity_ids)) {
            return $props;
        }
        $ids = [];
        foreach ($entity_ids as $id) {
            $ids[] = str_replace('blog_', '', $id);
        }
        $count = count($ids);
        $numberPages = ceil($count > 0 ? $total / $hitCount : 0);
        $props['bxBlogCount'] = $total;
        $props['sNumberPages'] = $numberPages;
        $props['bxHasOtherItemTypes'] = true;

        $pages = [];
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
        $blogArticles = $this->BxData()->enhanceBlogArticles($this->Helper()->getBlogs($ids));
        $props['sBlogArticles'] = $blogArticles;

        return $props;
    }

    /**
     * @param $params
     * @return string
     */
    protected function assemble($params) {
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
     * @param $facet
     * @return array
     */
    protected function getValueIds($facet) {
        if ($facet instanceof Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup) {
            $ids = [];
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
    public function get($name)
    {
        return $this->container->get($name);
    }

    /**
     * strip the / otherwise broken urls would be created e.g. wrong pager urls
     * @return mixed|string
     */
    protected function getSearchTerm()
    {
        $term = $this->Request()->get('sSearch', '');
        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));
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
     * @param bool $return
     * @return mixed
     */
    protected function loadThemeConfig($return = false)
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

    /**
     * @param $articles
     * @param $categoryId
     * @return mixed
     */
    protected function convertArticlesResult($articles, $categoryId)
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


    /**
     * @param Enlight_View_Default $view
     * @return Enlight_View_Default
     */
    protected function prepareViewConfig($view) {
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

    /**
     * @param $data
     * @return mixed
     */
    protected function prepareVoucherTemplate($data){
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
