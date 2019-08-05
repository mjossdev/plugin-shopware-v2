<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search
{
    protected $p13nHelper;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface
     */
    protected $searchBundle = null;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Facet
     */
    protected $facetBundle = null;

    protected $request;
    protected $filters;
    protected $choice = '';
    protected $response = null;

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
     * adds a search request to Boxalino
     */
    public function addRequest()
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
     * Get nr of hits per type and choice
     * @param string $type
     * @param null $choice
     * @return int
     */
    public function getTotalHitCount($type = "product", $choice = null)
    {
        return $this->getResponse()->getTotalHitCount($type, $choice);
    }

    /**
     * @return string|null
     */
    public function getRedirectLink()
    {
        return $this->getResponse()->getRedirectLink();
    }

    /**
     * @return mixed|\com\boxalino\bxclient\v1\BxChooseResponse
     */
    public function getResponse()
    {
        if(is_null($this->response))
        {
            $this->response = $this->p13nHelper->getResponse();
        }

        return $this->response;
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

    public function getFacets($type="product", $choice=null, $variantIndex = 0)
    {
        return $this->p13nHelper->getFacets($type, $choice, $variantIndex);
    }

    /**
     * the choice MUST be set
     * @return Shopware_Plugins_Frontend_Boxalino_Bundle_Facet
     */
    public function getFacetBundle()
    {
        if(is_null($this->facetBundle))
        {
            $this->facetBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Facet($this);
            $this->facetBundle->setChoice($this->choice);
        }
        return $this->facetBundle;
    }

    /**
     * the choice MUST be set
     *
     * @return Shopware_Plugins_Frontend_Boxalino_Bundle_Facet
     * @throws Exception
     */
    public function getNarrativeBundle()
    {
        $narrativeBundle = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative($this);
        return $narrativeBundle->setChoice($this->choice);
    }

    public function getShopwareFacets()
    {
        return $this->getSearchBundle()->getFacets();
    }

    public function getStoreSortings()
    {
        return $this->getSearchBundle()->getStoreSortings();
    }

    public function getSort()
    {
        return $this->getSearchBundle()->getSort();
    }

    public function getContext()
    {
        return $this->getSearchBundle()->getContext();
    }

    public function getCriteria()
    {
        return $this->getSearchBundle()->getCriteria();
    }

    public function areResultsCorrectedOnSubPhrases()
    {
        return $this->p13nHelper->areResultsCorrectedAndAlsoProvideSubPhrases();
    }

    public function showSubphrases()
    {
        return (Shopware()->Config()->get('boxalino_search_subphrase_result_limit') > 0) && $this->p13nHelper->areThereSubPhrases();
    }

    /**
     * @param $notification
     * @param string $type
     */
    public function addNotification($notification, $type='debug') {
        $this->p13nHelper->addNotification($type, $notification);
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

    public function setChoice($choice)
    {
        $this->choice = $choice;
        return $this;
    }
}