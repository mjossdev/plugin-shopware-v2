<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_View
{
    /**
     * @var null
     */
    protected $dependencies = null;

    /**
     * @var Enlight_View
     */
    protected $view;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxNarrative
     */
    protected $helper;
    /**
     * @var
     */
    protected $elementManager;

    public function __construct()
    {
        $this->helper = new Shopware_Plugins_Frontend_Boxalino_Helper_BxNarrative();
    }

    public function createView($viewElement, $additionalParameter, $otherTemplateData = array())
    {
        $view =  new Enlight_View_Default(Shopware()->Container()->get('Template'));

        $this->applyThemeConfig($view);
        $this->assignSubRenderings($view, $viewElement);
        $this->assignTemplateData($view, $viewElement, $additionalParameter, $otherTemplateData);

        return $view;
    }

    public function applyThemeConfig(&$view)
    {
        $inheritance = Shopware()->Container()->get('theme_inheritance');

        /** @var \Shopware\Models\Shop\Shop $shop */
        $shop = Shopware()->Container()->get('Shop');
        $config = $inheritance->buildConfig($shop->getTemplate(), $shop, false);
        Shopware()->Container()->get('template')->addPluginsDir(
            $inheritance->getSmartyDirectories(
                $shop->getTemplate()
            )
        );

        $view->assign('theme', $config);
    }

    public function assignSubRenderings(&$view, $viewElement)
    {
        $subRenderings = array();
        if(isset($viewElement['subRenderings'][0]['rendering']['visualElements'])) {
            $subRenderings = $viewElement['subRenderings'][0]['rendering']['visualElements'];
        }
        $view->assign('bxSubRenderings', $subRenderings);
    }

    public function assignTemplateData(&$view, $viewElement, $additionalParameter, $otherTemplateData = array())
    {
        $format = $this->getHelper()->getFormatOfElement($viewElement);
        if(empty($format))
        {
            return null;
        }
        $element = $this->getElementByType($format);
        switch($format) {
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Product::RENDER_NARRATIVE_ELEMENT_TYPE:
                list($variantIndex, $index) = $this->getHelper()->getVariantAndIndex($viewElement, $additionalParameter);
                $data = $element->getElement($variantIndex, $index);
                $templateData = array_merge($otherTemplateData, array('sArticle'=>$data));
                $view->assign($templateData);
                break;
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_List::RENDER_NARRATIVE_ELEMENT_TYPE:
                $variantIndex =$this->getHelper()->getVariant($viewElement);
                $data = $element->getElement($variantIndex, 0);
                $view->assign($data);
                $element->prepareCollection($variantIndex);
                break;
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Facets::RENDER_NARRATIVE_ELEMENT_TYPE:
                $data = $element->getElement(0, 0);
                $view->assign($data);
                break;
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Banner::RENDER_NARRATIVE_ELEMENT_TYPE:
                $variantIndex =$this->getHelper()->getVariant($viewElement);
                $data = $element->getElement($variantIndex, 0);
                $view->assign('banner', $data);
                break;
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Blog::RENDER_NARRATIVE_ELEMENT_TYPE:
                list($variantIndex, $index) = $this->getHelper()->getVariantAndIndex($viewElement, $additionalParameter);
                $data = $element->getElement($variantIndex, $index);
                $view->assign($otherTemplateData);
                $view->assign('sArticle', $data);
                $view->assign('productBoxLayout', 'minimal');
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Voucher::RENDER_NARRATIVE_ELEMENT_TYPE:
                $variantIndex =$this->getHelper()->getVariant($viewElement);
                $data = $element->getElement($variantIndex, 0);
                $view->assign($otherTemplateData);
                $view->assign('voucher', $data);
            default:
                break;
        }
    }

    /**
     * It is needed to be set during the template rendering (facets, ex)
     *
     * @param $narratives
     * @return array
     * @throws Exception
     */
    public function preProcessElements($narratives)
    {
        $data = array();
        $visualElementTypes = array();
        $order = 0;
        foreach($narratives as $visualElement)
        {
            $visualElementTypes[$order]= $this->getHelper()->getFormatOfElement($visualElement['visualElement']);
            $order+=1;
        }

        foreach($visualElementTypes as $order=>$type)
        {
            $data[$type] = $this->getDataByType($type, $narratives[$order]);
        }

        return $data;
    }

    /**
     * Gets data for the narrative elements
     *
     * @param $type
     * @param $viewElement
     * @return array|bool|mixed|null
     * @throws Exception
     */
    public function getDataByType($type, $viewElement)
    {
        $data = [];
        if(empty($type))
        {
            return $data;
        }
        $element = $this->getElementByType($type);
        switch($type) {
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_List::RENDER_NARRATIVE_ELEMENT_TYPE:
                $variantIndex =$this->getHelper()->getVariant($viewElement);
                $data = $element->getElement($variantIndex, 0);
                break;
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Facets::RENDER_NARRATIVE_ELEMENT_TYPE:
                $data = $element->getElement(0, 0);
                break;
            case Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Banner::RENDER_NARRATIVE_ELEMENT_TYPE:
                $variantIndex =$this->getHelper()->getVariant($viewElement);
                $data = $element->getElement($variantIndex, 0);
                break;
            default:
                break;
        }

        return $data;
    }

    public function getElementByType($type)
    {
        return $this->getElementManager()->create($type);
    }

    public function getElementManager()
    {
        return $this->elementManager;
    }

    public function getHelper()
    {
        return $this->helper;
    }

    public function setElementManager($elementManager)
    {
        $this->elementManager = $elementManager;
        return $this;
    }


}