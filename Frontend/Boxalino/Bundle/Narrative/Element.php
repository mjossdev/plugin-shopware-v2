<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element
 * Factory class used to initialize elements
 * Provides access to required entities/logic
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element
{

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    protected $helper = null;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Search
     */
    protected $searchBundle = null;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager
     */
    protected $resourceManager = null;

    /**
     * @param $p13nHelper Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     * @param $searchBundle Shopware_Plugins_Frontend_Boxalino_Bundle_Search
     * @param $resourceManager Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager
     */
    public function __construct($p13nHelper, $searchBundle, $resourceManager)
    {
        $this->setHelper($p13nHelper);
        $this->setSearchBundle($searchBundle);
        $this->setResourceManager($resourceManager);
    }

    /**
     * Creates elements for the view
     *
     * @param $type
     * @return null
     */
    public function create($type)
    {
        try {
            $elementClass = __CLASS__."_".ucfirst($type);
            if(!class_exists($elementClass))
            {
                throw new \Exception("BxNarrativeElement: the class definition does not exist: {$elementClass}");
            }
            $element =  new $elementClass($this);
            if(!$element instanceof Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_ElementInterface)
            {
                Shopware()->Container()->get('pluginlogger')->error("BxNarrativeViewElement: the class definition does not follow the required interface: {$elementClass}");
            }

            return $element;
        } catch (Exception $exception)
        {
            Shopware()->Container()->get('pluginlogger')->error($exception);
            return null;
        }

    }

    public function setHelper($helper)
    {
        $this->helper = $helper;
        return $this;
    }

    public function getHelper()
    {
        if(is_null($this->helper))
        {
            throw new \Exception("BxNarrativeElement: the P13nHelper is missing");
        }

        return $this->helper;
    }

    public function getSearchBundle()
    {
        return $this->searchBundle;
    }

    public function setSearchBundle($bundle)
    {
        $this->searchBundle = $bundle;
        return $this;
    }

    public function getResourceManager()
    {
        return $this->resourceManager;
    }

    public function setResourceManager($manager)
    {
        $this->resourceManager = $manager;
        return $this;
    }

}
