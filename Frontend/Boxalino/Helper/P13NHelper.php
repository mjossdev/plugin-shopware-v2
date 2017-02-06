<?php

class Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper {

    private static $instance = null;

    /**
     * @var null
     */
    private static $bxClient = null;
    
    /**
     * @var Enlight_Controller_Request_Request
     */
    private $request;

    /**
     * @var Shopware_Components_Config
     */
    private $config;


    /**
     * @var
     */
    private $currentSearchChoice;

    /**
     * @var
     */
    private $navigation;

    /**
     * @var bool
     */
    private $relaxationEnabled = false;

    /**
     * @var array
     */
    private static $choiceContexts = array();

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper constructor.
     */
    private function __construct() {
        $this->config = Shopware()->Config();
        $libPath = __DIR__ . '/../lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
        $this->initializeBXClient();
    }

    /**
     * @param $queryText
     * @return mixed|string
     */
    public function getSearchChoice($queryText) {

        if($queryText == null) {
            $choice = $this->config->get('boxalino_navigation_widget_name');
            if($choice == null) {
                $choice = "navigation";
            }
            $this->currentSearchChoice = $choice;
            $this->navigation = true;
            return $choice;
        }

        $choice = $this->config->get('boxalino_search_widget_name');
        if($choice == null) {
            $choice = "search";
        }
        $this->currentSearchChoice = $choice;
        return $choice;
    }

    /**
     * @param $queryText
     * @param int $pageOffset
     * @param $hitCount
     * @param string $type
     * @param null $sort
     * @param array $options
     */
    public function addSearch($queryText = "", $pageOffset = 0, $hitCount = 10, $type = "product", $sort = null, $options = array()){

        $choiceId = $this->getSearchChoice($queryText);
        $returnFields = $this->getReturnFields($type);
        $lang = $this->getShortLocale();
        $bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($lang, $queryText, $hitCount, $choiceId);
        $filters = $this->getSystemFilters($type, $queryText);
        $bxRequest->setFilters($filters);
        $bxRequest->setGroupBy($this->getEntityIdFieldName($type));
        $bxRequest->setReturnFields($returnFields);
        $bxRequest->setOffset($pageOffset);
        $facets = $this->prepareFacets($options);
        $bxRequest->setFacets($facets);

        if($sort != null && isset($sort['field'])){
            $sortFields = new \com\boxalino\bxclient\v1\BxSortFields($sort['field'], $sort['reverse']);
            $bxRequest->setSortFields($sortFields);
        }

        self::$bxClient->addRequest($bxRequest);
        self::$choiceContexts[$choiceId][] = $type;
    }

    /**
     * @param $options
     * @return \com\boxalino\bxclient\v1\BxFacets
     */
    protected function prepareFacets($options) {
        $bxFacets = new \com\boxalino\bxclient\v1\BxFacets();

        foreach ($options as $fieldName => $option) {
            if ($fieldName == 'category') {
                $bxFacets->addCategoryFacet($option['value']);
                continue;
            }
            if ($fieldName == 'discountedPrice') {
                $bxFacets->addPriceRangeFacet($option['value']);
                continue;
            }
            $value = isset($option['value']) && count($option['value']) ? $option['value'] : null;
            $type = isset($option['type']) ? $option['type'] : 'list';
            $bounds = isset($option['bounds']) ? $option['bounds'] : false;
            $label = isset($option['label']) ? $option['label'] : $fieldName;
            $bxFacets->addFacet($fieldName, $value, $type, $label, 2, $bounds);
        }
        return $bxFacets;
    }

    public function getLocalArticles($ids = array()) {

        $articles = array();
        foreach ($ids as $id) {
            $articleNew = Shopware()->Modules()->Articles()->sGetPromotionById('fix', 0, $id);
            if (!empty($articleNew['articleID'])) {
                $articles[] = $articleNew;
            }
        }
        return $articles;
    }
    public function getFacets($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        $facets = self::$bxClient->getResponse()->getFacets($this->currentSearchChoice, true, $count);
        if(empty($facets)){
            return null;
        }
        return $facets;
    }
    
    protected function getReturnFields($type = "product"){
        $returnFields = array($this->getEntityIdFieldName($type));
        if($type == 'product'){
            $returnFields = array_merge($returnFields, ['id', 'categories', 'score', 'products_bx_type', 'title', 'discountedPrice', 'products_ordernumber']);
        }else{
            $returnFields = array_merge($returnFields, ['id', 'categories', 'score', 'products_bx_type', 'products_blog_title', 'products_blog_id']);
        }
        $additionalFields = explode(',', $this->config->get('boxalino_returned_fields'));
        if(isset($additionalFields) && $additionalFields[0] != ''){
            $returnFields = array_merge($returnFields, $additionalFields);
        }
        return $returnFields;
    }

    /**
     * @return mixed|string
     */
    public function getEntityIdFieldName($type = 'product') {

        $entityIdFieldName = $this->config->get('boxalino_entity_id');
        if (!isset($entityIdFieldName) || $entityIdFieldName === '') {
            if ($type == 'product'){
                $entityIdFieldName = 'products_group_id';
            } else if($type == 'blog'){
                $entityIdFieldName = 'products_blog_id';
            } else {
                $entityIdFieldName = 'id';
            }
        }
        return $entityIdFieldName;
    }
    
    protected function initializeBXClient() {

        $account = $this->config->get('boxalino_account');
        $password = $this->config->get('boxalino_password');
        $isDev = $this->config->get('boxalino_dev');
        $host = $this->config->get('boxalino_host');
        $p13n_username = $this->config->get('boxalino_p13_user_name');
        $p13n_password = $this->config->get('boxalino_p13_user_password');
        $domain = $this->config->get('boxalino_domain');
        self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password);
//            self::$bxClient->setTimeout($this->scopeConfig->getValue('bxGeneral/advanced/thrift_timeout',$this->scopeStore))->setRequestParams($this->request->getParams());
    }

    /**
     * @return null|Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    public static function instance() {
        if (self::$instance == null)
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper();
        return self::$instance;
    }

    private function getSystemFilters($type = 'product', $query = ''){
        $filters = array();
//        if($query == "") {
//        } else {
//        }
        $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_bx_type', array($type));

        return $filters;
    }

    public function autocomplete($queryText, $with_blog){
        $choice = $this->getSearchChoice($queryText);
        $auto_complete_choice = $this->config->get('boxalino_autocomplete_widget_name');
        $textual_Limit = 6;//$this->config->get('boxalino_textual_suggestion_limit');
        $product_limit = 5;//$this->config->get('boxalino_product_suggestion_limit');
        $searches = !$with_blog ? array('product') : array('product','blog');
        $bxRequests = array();
        foreach ($searches as $search){
            $bxRequest = new \com\boxalino\bxclient\v1\BxAutocompleteRequest($this->getShortLocale(),
                $queryText, $textual_Limit, $product_limit, $auto_complete_choice,
                $choice
            );

            $searchRequest = $bxRequest->getBxSearchRequest();
            $return_fields = $this->getReturnFields($search);
            $searchRequest->setReturnFields(array_merge(array($this->getEntityIdFieldName($search)), $return_fields));
            $searchRequest->setGroupBy($this->getEntityIdFieldName($search));
            $searchRequest->setFilters($this->getSystemFilters($search));
            $bxRequests[] = $bxRequest;
        }

        self::$bxClient->setAutocompleteRequests($bxRequests);
        self::$bxClient->autocomplete();


        $template_properties = array();
        foreach ($searches as $index => $search) {
            $bxAutocompelteRespomse = self::$bxClient->getAutocompleteResponse($index);
            $template_properties = array_merge($template_properties, $this->createAjaxData($bxAutocompelteRespomse, $queryText, $search));
        }
        return $template_properties;
    }

    protected function getBlogs($ids){
        $blogs = array();
        foreach ($ids as $id) {
            $blogArticleQuery = Shopware()->Models()->getRepository('Shopware\Models\Blog\Blog')->getDetailQuery($id);
            $blog = $blogArticleQuery->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            if(isset($blog['id'])){
                $blogs[] = $blog;
            }
        }
        return $blogs;
    }

    protected function createAjaxData($autocompleteResponse, $queryText, $type = 'product'){

        $choice = $this->getSearchChoice($queryText);
        $suggestions = array();
        $totalHitCount = 0;
        foreach ($autocompleteResponse->getTextualSuggestions() as $suggestion) {
            $hits = $autocompleteResponse->getTextualSuggestionTotalHitCount($suggestion, true);
            $totalHitCount += $hits;
            $suggestions[] = array('html' => $autocompleteResponse->getHighlightedSuggestions($suggestion), 'hits' => $hits);
        }
        $hitIds = $autocompleteResponse->getBxSearchResponse()->getHitIds($choice, true, 0, 10, $this->getEntityIdFieldName($type));
        if($type == 'product'){
            $sResults = $this->getLocalArticles($hitIds);
            $router = Shopware()->Front()->Router();
            foreach ($sResults as $key => $result) {
                $sResults[$key]['name'] = $result['articleName'];
                $sResults[$key]['link'] = $router->assemble(array(
                    'controller' => 'detail',
                    'sArticle' => $result['articleID'],
                    'title' => $result['articleName']
                ));
            }
            return array(
                'sSearchRequest' => array('sSearch' => $queryText),
                'sSearchResults' => array(
                    'sResults' => $sResults,
                    'sArticlesCount' => $totalHitCount,
                    'sSuggestions' => $suggestions
                )
            );
        }else{

            $blog_ids = array();
            foreach ($hitIds as $index => $id){
                $blog_ids[$index] = preg_replace('/^blog_/', '', $id);
            }
            $router =  Shopware()->Router();
            $blogs = array_map(function($blog) use ($router) {
                return array(
                    'id' => $blog['id'],
                    'title' => $blog['title'],
                    'link' => $router->assemble(array(
                        'sViewport' => 'blog', 'action' => 'detail', 'blogArticle' => $blog['id']
                    ))
                );
            }, $this->getBlogs($blog_ids));
            $total = count($blog_ids);
            return array(
                'bxBlogSuggestions' => $blogs,
                'bxBlogSuggestionTotal' => $total
            );
        }
    }
    public function getRecommendation($choiceId, $max = 5, $min = 5, $offset = 0, $context = array(), $type = '', $execute = true) {

        if(!$execute){
            if ($max >= 0) {
                $bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->getShortLocale(), $choiceId, $max, $min);
                $bxRequest->setGroupBy($this->getEntityIdFieldName());
                $filters = $this->getSystemFilters();
                $bxRequest->setReturnFields(array($this->getEntityIdFieldName()));
                $bxRequest->setOffset($offset);
                if ($type === 'basket' && is_array($context)) {
                    $basketProducts = array();
                    foreach ($context as $product) {
                        $basketProducts[] = $product;
                    }
                    $bxRequest->setBasketProductWithPrices($this->getEntityIdFieldName(), $basketProducts);
                } elseif ($type === 'product' && !is_array($context)) {
                    $bxRequest->setProductContext('id', $context);
                } elseif ($type === 'category' && $context != null) {
                    $filterField = "category_id";
                    $filterValues = is_array($context) ? $context : array($context);
                    $filters[] = new \com\boxalino\bxclient\v1\BxFilter($filterField, $filterValues);
                }
                $bxRequest->setFilters($filters);
                self::$bxClient->addRequest($bxRequest);
            }
            return array();
        }
        $ids = self::$bxClient->getResponse()->getHitIds($choiceId);
        return $this->getLocalArticles($ids);
    }
    
    public function getResponse(){
        return self::$bxClient->getResponse();
    }
    public function getEntitiesIds($type = "product"){

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getHitIds($this->currentSearchChoice, true, $count, 10, $this->getEntityIdFieldName());
    }

    public function getSubPhraseEntitiesIds($queryText, $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getSubPhraseHitIds($queryText, $this->currentSearchChoice, $count, $this->getEntityIdFieldName());
    }
    public function getSubPhrasesQueries($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getSubPhrasesQueries($this->currentSearchChoice, $count);
    }
    public function areThereSubPhrases($type = "product") {

        $count = array_search($type = "product", self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->areThereSubPhrases($this->currentSearchChoice, $count);
    }
    public function getSubPhraseTotalHitCount($queryText, $type = "product") {

        $count = array_search($type = "product", self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getSubPhraseTotalHitCount($queryText, $this->currentSearchChoice, $count);
    }

    public function getTotalHitCount($type = "product"){

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getTotalHitCount($this->currentSearchChoice, true, $count);
    }
    /**
     * Sets request instance
     *
     * @param Enlight_Controller_Request_Request $request
     */
    public function setRequest(Enlight_Controller_Request_Request $request) {
        $this->request = $request;
    }

    /**
     * Returns request instance
     *
     * @return Enlight_Controller_Request_Request
     */
    public function Request() {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getShortLocale() {
        $locale = Shopware()->Shop()->getLocale();
        $shortLocale = $locale->getLocale();
        $position = strpos($shortLocale, '_');
        if ($position !== false)
            $shortLocale = substr($shortLocale, 0, $position);
        return $shortLocale;
    }

    public function getSearchLimit() {
        return $this->config->get('maxlivesearchresults', 6);
    }

    public function debug($a, $b = null) {
        if ($this->isDebug()) {
            echo '<pre>';
            var_dump($a, $b);
            echo '</pre>';
        }
    }

    public function debugProtected($a, $b = null) {
        if (!$this->isDebugProtected()) return;

        $this->debug($a, $b);
    }

    private function isDebug() {
        if (!$this->Request()) return false;

        return $this->Request()->getQuery('dev_bx_disp', false) == 'true';
    }

    private function isDebugProtected() {
        if (!$this->Request()) return false;

        return $this->Request()->getQuery('bx_debug_auth', false) == $this->config->get('boxalino_password');
    }

    public static function getAccount() {
        $config = Shopware()->Config();
        return $config->get('boxalino_dev') == 1 ? 
            $config->get('boxalino_account') . '_dev' : 
            $config->get('boxalino_account');
    }
    
    public function getBasket($arguments = null) {
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        if ($arguments !== null && (!$basket || !$basket['content'])) {
            $basket = $arguments->getSubject()->View()->sBasket;
        }
        return $basket;
    }
    
    public function newTiming($name) {
        $then = microtime(true);
        return function() use($name, $then) {
            $took = microtime(true) - $then;
            $this->debug("timing $name -- took [ms]", ($took * 1000));
        };
    }


    /**
     * @param $ids
     * @return mixed
     */
    public function getAjaxResult($ids) {
        return Shopware()->Container()->get('legacy_struct_converter')->convertListProductStructList(
            Shopware()->Container()->get('shopware_storefront.list_product_service')->getList(
                $ids,
                Shopware()->Container()->get('shopware_storefront.context_service')->getProductContext()
            )
        );
    }
    
}