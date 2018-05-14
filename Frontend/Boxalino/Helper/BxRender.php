<?php

class Shopware_Plugins_Frontend_Boxalino_Helper_BxRender{

    protected $p13Helper;

    protected $dataHelper;

    protected $request;

    protected $searchInterceptor;

    public function __construct($p13Helper, $dataHelper, $searchInterceptor, $request)
    {
        $this->p13Helper = $p13Helper;
        $this->dataHelper = $dataHelper;
        $this->searchInterceptor = $searchInterceptor;
        $this->request = $request;
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    protected function getDecodedValues($values) {
        if(is_array($values)) {
            foreach ($values as $i => $value) {
                if($this->isJson($value)) {
                    $values[$i] = json_decode($value, true);
                }
            }
        }
        return $values;
    }

    public function renderElement($viewElement, $additionalParameter = array()) {

        $html = '';
        if($viewElement) {
            $view = $this->createView($viewElement, $additionalParameter);
            $parameters = $viewElement['parameters'];
            foreach ($parameters as $parameter) {
                $paramName = $parameter['name'];
                $assignValues = $this->getDecodedValues($parameter['values']);
                $assignValues = sizeof($assignValues) == 1 ? reset($assignValues) : $assignValues;
                $view->assign($paramName, $assignValues);
                if (strpos($paramName, 'shopware_smarty_function_') === 0) {
                    $function = substr($paramName, strlen('shopware_smarty_function_'));
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
            $html = $view->render();
        }
        return $html;
    }

    protected function createView($viewElement, $additionalParameter) {
        $view =  new Enlight_View_Default(Shopware()->Container()->get('Template'));
        $view = $this->prepareView($view, $viewElement, $additionalParameter);
        return $view;
    }

    protected function prepareView($view, $viewElement, $additionalParameter) {
        $this->applyThemeConfig($view);
        $this->assignSubRenderings($view, $viewElement);
        $this->assignTemplateData($view, $viewElement, $additionalParameter);
        $templateDir = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $view->addTemplateDir($templateDir . 'Views/emotion/');
        return $view;
    }

    protected function assignTemplateData(&$view, $viewElement, $additionalParameter) {
        switch($viewElement['format']) {
            case 'product':
                if(!empty($additionalParameter)) {
                    $index = isset($additionalParameter['list_index']) ? $additionalParameter['list_index'] : 0;
                    $view->assign('sArticle', $this->getViewElementProduct($index));
                }
                break;
            case 'filter_panel':
                $this->getFilterPanelData($view);
                break;
            case 'banner':
                $this->getFilterPanelData($view);
                break;
            case 'blog':
                $this->getBlogData($view);
            default:
                break;
        }
    }

    protected function getFilterPanelData(&$view) {
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();

        $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->request, $context);

        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $criteria->getFacets();
        $facets = $this->searchInterceptor->updateFacetsWithResult($facets, $context, $this->request);
        $view->assign('facets', $facets);
        $view->assign('criteria', $criteria);
        $view->assign('facetOptions', $this->searchInterceptor->getFacetOptions());
    }

    protected function applyThemeConfig(&$view) {
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

    protected function assignSubRenderings(&$view, $viewElement) {
        $subRenderings = array();
        if(isset($viewElement['subRenderings'][0]['rendering']['visualElements'])) {
            $subRenderings = $viewElement['subRenderings'][0]['rendering']['visualElements'];
        }
        $view->assign('bxSubRenderings', $subRenderings);
        $view->assign('bxRender', $this);
    }

    protected function getViewElementProduct($index) {
        $numbers = $this->p13Helper->getHitFieldValues('products_ordernumber');
        $product = null;
        foreach ($numbers as $i => $number) {
            if($i == $index) {
                $product = reset($this->p13Helper->getLocalArticles([$number]));
                break;
            }
        }
        return $product;
    }
}