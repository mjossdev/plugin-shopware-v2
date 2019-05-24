<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search
{
    protected $p13nHelper;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface
     */
    protected $searchBundle = null;
    protected $request;
    protected $filters;

    public function __construct(Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper $p13NHelper, $type)
    {
        $this->p13nHelper = $p13NHelper;
        $searchBundleName = __CLASS__."_".ucfirst($type);
        if(!class_exists($searchBundleName))
        {
            throw new \Exception("BxSearchBundle: the class definition does not exist: {$searchBundleName}");
        }
        $this->searchBundle =  new $searchBundleName();
        if(!$this->searchBundle instanceof Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface)
        {
            throw new \Exception("BxSearchBundle: the class definition does not follow the required interface: {$searchBundleName}");
        }
    }


    /**
     * makes the call to Boxalino
     */
    public function execute()
    {
        $this->_init();
        $this->p13nHelper->setRequest($this->getSearchBundle()->getRequest());
        $this->p13nHelper->addSearch(
            $this->getSearchBundle()->getQueryText(),
            $this->getSearchBundle()->getPageOffset(),
            $this->getSearchBundle()->getHitCount(),
            $this->getSearchBundle()->getType(),
            $this->getSearchBundle()->getSort(),
            $this->getSearchBundle()->getOptions(),
            $this->getSearchBundle()->getFilters(),
            $this->getSearchBundle()->getIsStream(),
            $this->getSearchBundle()->getOverrideChoice()
        );
        $this->setRequest($this->getSearchBundle()->getRequest());
    }

    /**
     * Skim through the request
     * Pre-process data required for the search bundle elements (sort bundle, facet bundle, etc)
     */
    protected function _init()
    {
        $this->getSearchBundle()->setRequest($this->getRequest());
        $this->getSearchBundle()->init();
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

    public function getSearchBundle()
    {
        return $this->searchBundle;
    }

    public function addViewData($viewData)
    {
        $this->getSearchBundle()->setViewData($viewData);
    }

    public function executeBlog()
    {
        $hitCount = $this->searchBundle->getHitCount();
        $blogOffset = ($this->getRequest()->getParam('sBlogPage', 1) -1)*($hitCount);
        $this->p13nHelper->addSearch($this->getSearchBundle()->getQueryText(), $blogOffset, $hitCount, Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface::BOXALINO_BUNDLE_SEARCH_TYPE_BLOG);
    }
}