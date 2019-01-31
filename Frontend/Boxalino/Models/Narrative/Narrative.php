<?php

/**
 * The Narrative model will be used to show narratives server-side, without the use of an emotion
 * Currently, the main functions are stored in SearchInterceptor::narrative
 *
 * This class is to be used in the BxNarrative controller and in SearchEnterceptor
 * Class Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative
 */
class Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative
{

    CONST BOXALINO_NARRATIVE_EMOTION_TEMPLATE_DIR = "Views/emotion/";
    CONST BOXALINO_NARRATIVE_AJAX_TEMPLATE_MAIN = "frontend/plugins/boxalino/journey/main.tpl";
    CONST BOXALINO_NARRATIVE_SERVER_TEMPLATE_DIR = "Views/emotion/";
    CONST BOXALINO_NARRATIVE_SERVER_TEMPLATE_MAIN = "frontend/plugins/boxalino/narrative/main.tpl";
    CONST BOXALINO_NARRATIVE_SERVER_SCRIPTS_MAIN = "frontend/plugins/boxalino/narrative/script.tpl";

    protected $helper;
    protected $interceptor;
    protected $request;

    protected $choiceId;
    protected $isEmotion;
    protected $additionalChoiceIds;

    public function __construct($choiceId, $request, $isEmotion = false, $additionalChoiceIds = null)
    {
        $this->choiceId = $choiceId;
        if(empty($choiceId))
        {
            throw new Exception("The narrative can not be instantieted without a choice ID.");
        }

        $this->isEmotion = $isEmotion;
        $this->additionalChoiceIds = $additionalChoiceIds;
        $this->request = $request;
        $this->helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $this->interceptor = Shopware()->Plugins()->Frontend()->Boxalino()->getSearchInterceptor();
        $this->getRequestWithReferrerParams();
    }

    /**
     * Get narratives belonging to the given choice id
     * @return mixed
     */
    public function getNarratives()
    {
        list($options, $hitCount, $pageOffset, $sort) = $this->getPageSetup();
        return $this->helper->getNarrative($this->choiceId, $this->additionalChoiceIds, $options, $hitCount, $pageOffset, $sort, $this->request);
    }

    /**Get dependencies
     *
     * @return string
     */
    public function getDependencies()
    {
        return $this->renderDependencies($this->choiceId);
    }

    /**
     * Get renderer
     *
     * @return Shopware_Plugins_Frontend_Boxalino_Helper_BxRender
     */
    public function getRenderer()
    {
        return new Shopware_Plugins_Frontend_Boxalino_Helper_BxRender($this->helper, $this->interceptor, $this->request);
    }

    public function getAjaxEmotionTemplateDirectory()
    {
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . self::BOXALINO_NARRATIVE_EMOTION_TEMPLATE_DIR;
    }

    public function getServerSideTemplateDirectory()
    {
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . self::BOXALINO_NARRATIVE_SERVER_TEMPLATE_DIR;
    }

    public function getAjaxEmotionMainTemplate()
    {
        return self::BOXALINO_NARRATIVE_AJAX_TEMPLATE_MAIN;
    }

    public function getServerSideMainTemplate()
    {
        return self::BOXALINO_NARRATIVE_SERVER_TEMPLATE_MAIN;
    }

    public function getServerSideScriptTemplate()
    {
        return self::BOXALINO_NARRATIVE_SERVER_SCRIPTS_MAIN;
    }

    protected function getPageSetup()
    {
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
        $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->request, $context);
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();

        $sort =  $this->interceptor->BxData()->getSortOrder($criteria, null, true);
        $facets = $criteria->getFacets();
        $options = $this->interceptor->BxData()->getFacetConfig($facets, $this->request);

        $this->addOrderParamToRequest();

        return array($options, $hitCount, $pageOffset, $sort);

    }

    public function renderDependencies($choice_id)
    {
        $html = '';
        $dependencies = $this->helper->getNarrativeDependencies($choice_id);
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

    protected function getRequestWithReferrerParams()
    {
        $address = $_SERVER['HTTP_REFERER'];
        $basePath = $this->request->getBasePath();
        $start = strpos($address, $basePath) + strlen($basePath);
        $end = strpos($address, '?');
        $length = $end ? $end - $start : strlen($address);
        $pathInfo = substr($address, $start, $length);
        $this->request->setPathInfo($pathInfo);
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));
        foreach ($params as $index => $param){
            $keyValue = explode("=", $param);
            $params[$keyValue[0]] = $keyValue[1];
            unset($params[$index]);
        }
        foreach ($params as $key => $value) {
            $this->request->setParam($key, $value);
            if($key == 'p') {
                $this->request->setParam('sPage', (int) $value);
            }
        }
        return $this->request;
    }

    protected function addOrderParamToRequest()
    {
        $orderParam = Shopware()->Container()->get('query_alias_mapper')->getShortAlias('sSort');
        $default = null;
        if(is_null($this->request->getParam($orderParam))) {
            $this->request->setParam('sSort', 7);
        }
        if(is_null($this->request->getParam('sSort')) && is_null($this->request->getParam($orderParam))) {
            if(Shopware()->Config()->get('boxalino_navigation_sorting')) {
                $this->request->setParam('sSort', 7);
            } else {
                $default = Shopware()->Container()->get('config')->get('defaultListingSorting');
                $this->request->setParam('sSort', $default);
            }
        }

        return $this;
    }
}
