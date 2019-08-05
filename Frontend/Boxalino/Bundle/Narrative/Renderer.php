<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Renderer
 *
 * Narrative renderer logic
 * Creates the visual element manager
 * Creates the element factory
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Renderer
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_NarrativeRendererInterface
{

    CONST RENDER_NARRATIVE_VAR_TEMPLATE = "shopware_smarty_function_loadTemplate";
    CONST RENDER_NARRATIVE_VAR_FUNCTION = "shopware_smarty_function_";

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    protected $p13nHelper;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Bundle_Search
     */
    protected $searchBundle;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_View
     */
    protected $viewManager;

    /**
     * @var mixed PluginLogger
     */
    protected $logger;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxNarrative
     */
    protected $helper;

    public function __construct($searchBundle, $p13nHelper)
    {
        $this->p13nHelper = $p13nHelper;
        $this->searchBundle = $searchBundle;
        $this->helper = new Shopware_Plugins_Frontend_Boxalino_Helper_BxNarrative();
        $this->viewManager = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_View();
        $this->viewManager->setElementManager(new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element($p13nHelper, $searchBundle, Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager::instance()));
        $this->logger = Shopware()->Container()->get('pluginlogger');
    }

    public function renderElement($viewElement, $additionalParameter = [], $otherTemplateData = [])
    {
        $html = '';
        if(empty($viewElement))
        {
            return $html;
        }

        if($this->missingTemplate($viewElement))
        {
            $this->logger->warning("BxNarrativeRenderer: Template has not been defined on " . $this->searchBundle->getRequest()->getRequestUri());
            return $html;
        }

        $view = $this->viewManager->createView($viewElement, $additionalParameter, $otherTemplateData);
        $parameters = $viewElement['parameters'];
        foreach ($parameters as $parameter) {
            $paramName = $parameter['name'];
            $assignValues = $this->helper->getDecodedValues($parameter['values']);
            $assignValues = sizeof($assignValues) == 1 ? reset($assignValues) : $assignValues;
            if(is_array($assignValues))
            {
                $assignValues = $this->getLocalizedValue($assignValues);
            }
            $view->assign($paramName, $assignValues);
            if (strpos($paramName, self::RENDER_NARRATIVE_VAR_FUNCTION) === 0) {
                $function = substr($paramName, strlen(self::RENDER_NARRATIVE_VAR_FUNCTION));
                foreach ($parameter['values'] as $value) {
                    if($function == 'assign') {
                        $args = [json_decode($value, true)];
                    } else {
                        $args = [$value];
                    }
                    call_user_func_array(array($view, $function), $args);
                }
            }
        }

        try{
            $view->assign('bxRender', $this);
            $html = $view->render();
        } catch(\Exception $e) {
            $this->logger->error("BxNarrativeRenderer: Exception " . $e->getMessage() . "on the element: " . json_encode($viewElement, true));
        }

        return $html;
    }

    public function getLocalizedValue($values, $key=null) {
        return $this->getHelper()->getResponse()->getLocalizedValue($values, $key);
    }


    public function loadViewElements($narratives)
    {
        return $this->getViewManager()->preProcessElements($narratives);
    }

    public function renderDependencies($dependencies)
    {
        $html = '';
        if(isset($dependencies['js'])) {
            foreach ($dependencies['js'] as $js) {
                $url = $js;
                $html .= $this->getDependencyElement($url, 'js');
            }
        }
        if(isset($dependencies['css'])) {
            foreach ($dependencies['css'] as $css) {
                $url = $css;
                $html .= $this->getDependencyElement($url, 'css');
            }
        }
        return $html;
    }

    protected function getDependencyElement($url, $type)
    {
        $element = '';
        if($type == 'css'){
            $element = "<link href=\"{$url}\" type=\"text/css\" rel=\"stylesheet\" />";
        } else if($type == 'js') {
            $element = "<script src=\"{$url}\" type=\"text/javascript\"></script>";
        }
        return $element;
    }

    protected function missingTemplate($element)
    {
        $parameters = $element['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == self::RENDER_NARRATIVE_VAR_TEMPLATE) {
                return false;
                break;
            }
        }

        return true;
    }

    public function getHelper()
    {
        return $this->p13nHelper;
    }

    public function getViewManager()
    {
        return $this->viewManager;
    }

}
