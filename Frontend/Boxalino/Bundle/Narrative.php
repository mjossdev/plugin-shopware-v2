<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative
{

    CONST BOXALINO_NARRATIVE_CHOICE_DEFAULT = "narrative";

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface
     */
    protected $searchBundle = null;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper|null
     */
    protected $p13nHelper;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_BoxalinoNarrativeInterface
     */
    protected $narrativeBundle;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxData
     */
    protected $dataHelper;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Renderer
     */
    protected $renderer = null;

    /**
     * @var null
     */
    protected $additionalChoices = null;

    /**
     * @var array
     */
    protected $request = [];

    /**
     * @var null | string
     */
    protected $choiceId;

    protected $execute = true;


    public function __construct($type, $choiceId)
    {
        if(empty($choiceId))
        {
            throw new Exception("BxNarrativeBundle: The narrative can not be created without a choice ID.");
        }
        $this->choiceId = $choiceId;
        $this->p13nHelper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $this->dataHelper = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();

        $this->searchBundle =  new Shopware_Plugins_Frontend_Boxalino_Bundle_Search($this->p13nHelper, "parametrized");
        $this->searchBundle->setChoice($choiceId);

        $narrativeBundleName = __CLASS__."_".ucfirst($type);
        if(!class_exists($narrativeBundleName))
        {
            throw new \Exception("BxNarrativeBundle: the class definition does not exist: {$narrativeBundleName}");
        }

        $this->narrativeBundle =  new $narrativeBundleName();
        //$this->narrativeBundle->setChoice($choiceId);
        if(!$this->narrativeBundle instanceof Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_BoxalinoNarrativeInterface)
        {
            throw new \Exception("BxNarrativeBundle: the class definition does not follow the required interface: {$narrativeBundleName}");
        }
    }

    /**
     * makes the call to Boxalino
     */
    public function addRequest()
    {
        $this->_init();
        $this->p13nHelper->setRequest($this->getSearchBundle()->getRequest());
        $narratives = $this->p13nHelper->getNarrative(
            $this->choiceId,
            $this->getAdditionalChoices(),
            $this->getOptions(),
            $this->getHitCount(),
            $this->getPageOffset(),
            $this->getSearchBundle()->getSort(),
            $this->getRequest(),
            $this->getSearchBundle()->getFilters(),
            $this->getExecute()
        );

        $this->setRequest($this->getSearchBundle()->getRequest());
        if($this->getExecute())
        {
            $this->getBundle()->setResponse($narratives);
        }

        return $this;
    }

    /**
     * Accessing the response after the call has been done already (for partial views)
     *
     * @param null $choiceId
     * @return mixed
     */
    public function getResponse($choiceId = null)
    {
        if(is_null($choiceId))
        {
            $choiceId = $this->choiceId;
        }

        $narratives = $this->p13nHelper->getNarrative(
            $choiceId, null, [], $this->getHitCount(), $this->getPageOffset(), null, [], [], true
        );
        $this->getBundle()->setResponse($narratives);
        return $this;
    }

    /**
     * adding dependencies to the use-case manager
     *
     */
    public function addDependencies()
    {
        $this->getBundle()->setDependencies($this->p13nHelper->getNarrativeDependencies($this->choiceId));
    }

    /**
     * Skim through the request
     * Pre-process data required for the search bundle elements (sort bundle, facet bundle, etc)
     */
    protected function _init()
    {
        $this->getSearchBundle()->setRequest($this->getRequest());
        $this->getSearchBundle()->init();
        $this->getBundle()->setRenderer($this->getRenderer());
    }

    /**
     * @param $viewData
     */
    public function addViewData($viewData)
    {
        $this->searchBundle->addViewData($viewData);
        $this->getBundle()->setViewData($viewData);
    }

    public function getBundle()
    {
        return $this->narrativeBundle;
    }

    public function getPageOffset()
    {
        return $this->getSearchBundle()->getCriteria()->getOffset();
    }

    public function getHitCount()
    {
        return $this->getSearchBundle()->getCriteria()->getLimit();
    }

    public function getOptions()
    {
        $facets = $this->searchBundle->getShopwareFacets();
        return $this->dataHelper->getFacetConfig($facets, $this->getRequest());
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

    public function getExecute()
    {
        return $this->execute;
    }

    public function setExecute($execute)
    {
        $this->execute = $execute;
        return $this;
    }

    public function getSearchBundle()
    {
        return $this->searchBundle->getSearchBundle();
    }

    public function setAdditionalChoices($additionalChoices)
    {
        $this->additionalChoices = $additionalChoices;
        return $this;
    }

    public function getAdditionalChoices()
    {
        return $this->additionalChoices;
    }

    public function getRenderer()
    {
        if(is_null($this->renderer))
        {
            $this->renderer = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Renderer($this->getSearchBundle(), $this->p13nHelper);
        }

        return $this->renderer;
    }

    /**
     * @param $funName
     * @param $arguments
     * @return mixed
     */
    function __call($funName, $arguments=[])
    {
        try {
            return $this->getBundle()->$funName($arguments);
        } catch (\Exception $exception) {
            Shopware()->Container()->get('pluginlogger')->error($exception);
        }
    }

}