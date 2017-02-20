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
     * @var Shopware_Plugins_Frontend_Boxalino_Benchmark
     */
    private $benchmark;

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper constructor.
     */
    private function __construct() {
        $this->benchmark = Shopware_Plugins_Frontend_Boxalino_Benchmark::instance();
        $this->config = Shopware()->Config();
        $libPath = __DIR__ . '/../lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
        $this->initializeBXClient();
    }

    /**
     * initializeBXClient
     */
    protected function initializeBXClient() {

        $account = $this->config->get('boxalino_account');
        $password = $this->config->get('boxalino_password');
        $isDev = $this->config->get('boxalino_dev');
        $host = $this->config->get('boxalino_host');
        $p13n_username = $this->config->get('boxalino_p13_user_name');
        $p13n_password = $this->config->get('boxalino_p13_user_password');
        $domain = $this->config->get('boxalino_domain');
        self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password);
        self::$bxClient->setTimeout(1000);
    }

    /**
     * @return null|Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    public static function instance() {
        if (self::$instance == null)
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper();
        return self::$instance;
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

        $this->benchmark->log("Start of addSearch");
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
        $this->benchmark->log("End of addSearch");
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

    /**
     * @param string $type
     * @return null
     */
    public function getFacets($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        $facets = self::$bxClient->getResponse()->getFacets($this->currentSearchChoice, true, $count);
        if(empty($facets)){
            return null;
        }
        return $facets;
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getReturnFields($type = "product"){
        $returnFields = array($this->getEntityIdFieldName($type));
        if($type == 'product'){
            $returnFields = array_merge($returnFields, ['id', 'score', 'products_brand', 'products_bx_type', 'title', 'discountedPrice', 'products_ordernumber']);
        }else{
            $returnFields = array_merge($returnFields, ['id', 'score', 'products_bx_type', 'products_blog_title', 'products_blog_id', 'products_blog_category_id']);
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

    /**
     * @param string $type
     * @param string $query
     * @return array
     */
    private function getSystemFilters($type = 'product', $query = ''){
        $filters = array();
        if ($query == "") {
            $category_id = $this->Request()->getParam('sCategory');
            if ($category_id != Shopware()->Shop()->getCategory()->getId()) {
                $filters[] = new \com\boxalino\bxclient\v1\BxFilter('category_id', array($category_id));
            }
        }
        $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_bx_type', array($type));
        if ($type == 'blog') {
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_blog_active', array('1'));
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_blog_shop_id', array(Shopware()->Shop()->getCategory()->getId()));
        }
        return $filters;
    }

    /**
     * @param $queryText
     * @param $with_blog
     * @return array
     */
    public function autocomplete($queryText, $with_blog){
        $this->benchmark->log("Start autocomplete p13nHelper");
        $choice = $this->getSearchChoice($queryText);
        $auto_complete_choice = $this->config->get('boxalino_autocomplete_widget_name');
        $textual_Limit = $this->config->get('boxalino_textual_suggestion_limit', 3);
        $product_limit = $this->config->get('boxalino_product_suggestion_limit', 3);
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
        $this->benchmark->log("End autocomplete p13nHelper");
        return $template_properties;
    }

    /**
     * @param $ids
     * @return array
     */
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

    /**
     * @param $autocompleteResponse
     * @param $queryText
     * @param string $type
     * @return array
     */
    protected function createAjaxData($autocompleteResponse, $queryText, $type = 'product'){

        $choice = $this->getSearchChoice($queryText);
        $suggestions = array();
        $hitIds = array();
        foreach ($autocompleteResponse->getTextualSuggestions() as $i => $suggestion) {
            $hits = $autocompleteResponse->getTextualSuggestionTotalHitCount($suggestion);
            $suggestions[] = array('html' => $autocompleteResponse->getTextualSuggestionHighlighted($suggestion), 'hits' => $hits);
            if ($suggestion == $queryText) {
                $hitIds = $autocompleteResponse->getBxSearchResponse($suggestion)->getHitIds($choice, true, 0, 10, 'products_ordernumber');
            }
        }
        if (empty($hitIds)) {
            $hitIds = $autocompleteResponse->getBxSearchResponse()->getHitIds($choice, true, 0, 10, 'products_ordernumber');
        }

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
                    'sArticlesCount' => $autocompleteResponse->getBxSearchResponse()->getTotalHitCount($this->currentSearchChoice),
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

    public function getRequest($index){
        return self::$bxClient->getRequest($index);
    }
    public function getResponse(){
        return self::$bxClient->getResponse();
    }
    
    /**
     * @param $choiceId
     * @param int $max
     * @param int $min
     * @param int $offset
     * @param array $context
     * @param string $type
     * @param bool $execute
     * @return array
     */
    public function getRecommendation($choiceId, $max = 5, $min = 5, $offset = 0, $context = array(), $type = '', $execute = true) {

        if(!$execute){
            if ($max >= 0) {
                $bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->getShortLocale(), $choiceId, $max, $min);
                $bxRequest->setGroupBy($this->getEntityIdFieldName());
                $filters = $this->getSystemFilters();
                $bxRequest->setReturnFields($this->getReturnFields());
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
        $benchmark = Shopware_Plugins_Frontend_Boxalino_Benchmark::instance();
        $benchmark->log("before get hit ids for recommendation");
        $order_number = self::$bxClient->getResponse()->getHitIds($choiceId, true, 0, 10, 'products_ordernumber');
        $benchmark->log("after get hit ids for recommendation");
        $benchmark->log("before getLocalArticles");
        $articles = $this->getLocalArticles($order_number);
        $benchmark->log("after getLocalArticles");
        return $articles;
    }

    /**
     * Flush BxClient responses
     */
    public function flushResponses() {
        self::$bxClient->flushResponses();
    }

    /**
     * Reset BxClient requests
     */
    public function resetRequests() {
        self::$bxClient->resetRequests();
    }

    public function getFieldsValues($type = "product", $field = 'id') {
        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getHitIds($this->currentSearchChoice, true, $count, 10, $field);
    }
    /**
     * @param string $type
     * @return mixed
     */
    public function getEntitiesIds($type = "product"){

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getHitIds($this->currentSearchChoice, true, $count, 10, $this->getEntityIdFieldName($type));
    }

    /**
     * @param $queryText
     * @param string $type
     * @return mixed
     */
    public function getSubPhraseEntitiesIds($queryText, $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getSubPhraseHitIds($queryText, $this->currentSearchChoice, $count, 'products_ordernumber');
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getSubPhrasesQueries($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getSubPhrasesQueries($this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function areThereSubPhrases($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->areThereSubPhrases($this->currentSearchChoice, $count);
    }

    /**
     * @param $queryText
     * @param string $type
     * @return mixed
     */
    public function getSubPhraseTotalHitCount($queryText, $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getSubPhraseTotalHitCount($queryText, $this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
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

    /**
     * @param $ids
     * @return mixed
     */
    public function getLocalArticles($ids) {

        return Shopware()->Container()->get('legacy_struct_converter')->convertListProductStructList(
            Shopware()->Container()->get('shopware_storefront.list_product_service')->getList(
                $ids,
                Shopware()->Container()->get('shopware_storefront.context_service')->getProductContext()
            )
        );
    }

}