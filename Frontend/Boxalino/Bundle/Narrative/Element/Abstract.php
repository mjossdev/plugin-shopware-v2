<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
 *
 */
abstract class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_ElementInterface
{

    CONST RENDER_NARRATIVE_ELEMENT_TYPE = 'default';

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    protected $helper = null;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxData
     */
    protected $dataHelper;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager
     */
    protected $resourceManager;

    /**
     * @var null | Shopware_Plugins_Frontend_Boxalino_Bundle_Search
     */
    protected $searchBundle = null;

    /**
     * Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract constructor.
     * @param $manager Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element
     * @throws Exception
     */
    public function __construct($manager)
    {
        $this->setHelper($manager->getHelper());
        $this->setSearchBundle($manager->getSearchBundle());
        $this->setResourceManager($manager->getResourceManager());

        $this->dataHelper = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
    }

    abstract function getElement($variantIndex, $index);

    public function getType()
    {
        return self::RENDER_NARRATIVE_ELEMENT_TYPE;
    }

    public function setHelper($helper)
    {
        $this->helper = $helper;
        return $this;
    }

    public function getHelper()
    {
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
