<?php
abstract class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearch
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface
{
    protected $container;
    protected $sortBundle;
    protected $dataHelper;
    protected $config;

    protected $context;
    protected $searchQuery;
    protected $sort;
    protected $request;
    protected $criteria = null;
    protected $stream = null;
    protected $options = array();
    protected $filters = array();
    protected $showFacets = false;
    protected $viewData = array();

    public function __construct()
    {
        $this->config = Shopware()->Config();
        $this->container = Shopware()->Container();
        $this->sortBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting();
        $this->dataHelper = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
    }

    abstract function getContext();
    abstract function getQueryText();
    abstract function _request();

    public function init()
    {
        $this->_request();
        $this->getSortBundle()->setCriteria($this->getCriteria());
        $this->setStream($this->checkStreamIdOnCategory());
        $this->setShowFacets($this->checkFacetsVisibilityOnCategory());
    }

    public function getCriteria()
    {
        if(is_null($this->criteria))
        {
            $this->criteria = $this->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->getRequest(), $this->getContext());
            $this->criteria->removeCondition("term");
            $this->criteria->removeBaseCondition("search");
        }

        return $this->criteria;
    }

    public function getSort()
    {
        return $this->getSortBundle()->getSort();
    }

    public function getOptions()
    {
        return $this->dataHelper->getFacetConfig($this->getFacets(), $this->getRequest());
    }

    public function getPageOffset()
    {
        return $this->getCriteria()->getOffset();
    }

    public function getHitCount()
    {
        return $this->getCriteria()->getLimit();
    }

    public function getType()
    {
        return self::BOXALINO_BUNDLE_SEARCH_TYPE_PRODUCT;
    }

    public function getOverrideChoice()
    {
        return null;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function getFacets()
    {
        return $this->getCriteria()->getFacets();
    }

    public function checkFacetsVisibilityOnCategory()
    {
        return $this->dataHelper->categoryShowFilter($this->getRequest()->getParam('sCategory', null));
    }

    public function checkStreamIdOnCategory()
    {
        return $this->dataHelper->findStreamIdByCategoryId($this->getRequest()->getParam('sCategory', null));;
    }

    public function setShowFacets($value)
    {
        $this->showFacets = $value;
        return $this;
    }

    public function showFacets()
    {
        return $this->showFacets;
    }

    public function get($name)
    {
        return $this->container->get($name);
    }

    public function getOrderParam()
    {
        return $this->get('query_alias_mapper')->getShortAlias('sSort');
    }

    public function getDefaultListingSorting()
    {
        return $this->get('config')->get('defaultListingSorting');
    }

    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getSortBundle()
    {
        return $this->sortBundle;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    public function setStream($value)
    {
        $this->stream = $value;
        return $this;
    }

    public function getIsStream()
    {
        return !is_null($this->stream);
    }

    public function setViewData($data=array())
    {
       $this->viewData = $data;
       return $this;
    }

}