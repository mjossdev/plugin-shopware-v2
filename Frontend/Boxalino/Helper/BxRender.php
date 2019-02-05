<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Helper_BxRender
 * Content rendering helper
 */
class Shopware_Plugins_Frontend_Boxalino_Helper_BxRender
{

    CONST RENDER_NARRATIVE_TYPE_BLOG = 'blog';
    CONST RENDER_NARRATIVE_TYPE_FACETS ='facets';
    CONST RENDER_NARRATIVE_TYPE_PRODUCT='product';
    CONST RENDER_NARRATIVE_TYPE_BANNER='banner';
    CONST RENDER_NARRATIVE_TYPE_VOUCHER='voucher';
    CONST RENDER_NARRATIVE_TYPE_LIST='list';

    protected $p13Helper;

    protected $dataHelper;

    protected $request;

    protected $searchInterceptor;

    protected $resourceManager;

    protected $renderingData = array();

    public function __construct($p13Helper, $searchInterceptor, $request)
    {
        $this->p13Helper = $p13Helper;
        $this->dataHelper = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();;
        $this->searchInterceptor = $searchInterceptor;
        $this->request = $request;
        $this->resourceManager = Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager::instance();
    }

    public function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function getDecodedValues($values)
    {
        if(is_array($values)) {
            foreach ($values as $i => $value) {
                if($this->isJson($value)) {
                    $values[$i] = json_decode($value, true);
                }
            }
        }
        return $values;
    }

    public function renderElement($viewElement, $additionalParameter = array())
    {
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
            try{
                $html = $view->render();
            }catch(\Exception $e) {
                var_dump($e->getMessage());
                var_dump($additionalParameter);
                var_dump($viewElement);exit;
            }
        }
        return $html;
    }

    protected function createView($viewElement, $additionalParameter) {
        $view =  new Enlight_View_Default(Shopware()->Container()->get('Template'));
        $this->applyThemeConfig($view);
        $this->assignSubRenderings($view, $viewElement);
        $this->assignTemplateData($view, $viewElement, $additionalParameter);
        return $view;
    }

    public function assignTemplateData(&$view, $viewElement, $additionalParameter)
    {
        $format = $this->getFormatOfElement($viewElement);
        switch($format) {
            case self::RENDER_NARRATIVE_TYPE_PRODUCT:
                if(!empty($additionalParameter)) {
                    $data = $this->getViewElementProduct($viewElement, $additionalParameter);
                    $view->assign('sArticle', $data);
                } else {
                    $data = $this->getListingData($viewElement);
                    $view->assign($data);
                    $this->prepareCollection($viewElement);
                }
                break;
            case self::RENDER_NARRATIVE_TYPE_LIST:
                $data = $this->getListingData($viewElement);
                $view->assign($data);
                $this->prepareCollection($viewElement);
                break;
            case self::RENDER_NARRATIVE_TYPE_FACETS:
                $filterData = $this->getFilterPanelData();
                $view->assign($filterData);
                break;
            case self::RENDER_NARRATIVE_TYPE_BANNER:
                $bannerData = $this->getBannerData($viewElement);
                $view->assign('banner', $bannerData);
                break;
            case self::RENDER_NARRATIVE_TYPE_BLOG:
                $data = $this->getBlogArticle($viewElement, $additionalParameter);
                $view->assign('sArticle', $data);
                $view->assign('productBoxLayout', 'minimal');
            case self::RENDER_NARRATIVE_TYPE_VOUCHER:
                $voucherData = $this->getVoucherData($viewElement);
                $view->assign('voucher', $voucherData);
            default:
                break;
        }
    }

    protected function getBlogArticle($visualElement, $additionalParameter) {
        $product = false;
        list($variantIndex, $index) = $this->getVariantAndIndex($visualElement, $additionalParameter);
        $choiceId = $this->p13Helper->getVariantChoiceId($variantIndex);
        $ids = $this->p13Helper->getHitFieldValues('id', 'blog', $choiceId);
        foreach ($ids as $i => $id) {
            $ids[$i] = str_replace('blog_', '', $id);
        }
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->resourceManager->getResource($variantIndex, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product['id'] == $entity_id){
                        return $product;
                    }
                }
            }

            $product = $this->resourceManager->getResource($entity_id, 'blog');
            if(is_null($product)) {
                $articles = $this->dataHelper->enhanceBlogArticles($this->p13Helper->getBlogs([$entity_id]));
                $product = reset($articles);
                $this->resourceManager->setResource($product, $entity_id, 'blog');
            }
        }
        return $product;
    }

    protected function getBannerData($visualElement)
    {
        if(isset($this->renderingData[self::RENDER_NARRATIVE_TYPE_BANNER]))
        {
            return $this->renderingData[self::RENDER_NARRATIVE_TYPE_BANNER];
        }
        $variantIndex =$this->getVariant($visualElement);
        $choiceId = $this->p13Helper->getVariantChoiceId($variantIndex);
        return $this->p13Helper->getBannerData($choiceId);
    }

    protected function getVoucherData($visualElement)
    {
        if(isset($this->renderingData[self::RENDER_NARRATIVE_TYPE_VOUCHER]))
        {
            return $this->renderingData[self::RENDER_NARRATIVE_TYPE_VOUCHER];
        }
        $variantIndex =$this->getVariant($visualElement);
        $choiceId = $this->p13Helper->getVariantChoiceId($variantIndex);
        return $this->p13Helper->getVoucherResponse($choiceId);
    }

    protected function getFilterPanelData()
    {
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
        $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->request, $context);

        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $criteria->getFacets();
        $facets = $this->searchInterceptor->updateFacetsWithResult($facets, $context, $this->request);

        return array(
            'facets' => $facets,
            'bxFacets' => $this->p13Helper->getFacets('product'),
            'criteria' =>  $criteria,
            'listingMode' => 'full_page_reload',
            'sSort'=> $this->request->getParam('sSort', 7),
            'facetOptions'=> $this->searchInterceptor->getFacetOptions(),
            'shortParameters' => Shopware()->Container()->get('query_alias_mapper')->getQueryAliases()
        );
    }

    protected function getListingData($visualElement)
    {
        $variantIndex =$this->getVariant($visualElement);

        $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variantIndex);
        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
        $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($this->request, $context);
        $sortingIds = Shopware()->Container()->get('config')->get('searchSortings');
        $sortingIds = array_filter(explode('|', $sortingIds));
        $service = Shopware()->Container()->get('shopware_storefront.custom_sorting_service');
        $sortings = $service->getList($sortingIds, $context);

        return array(
            'sPage' => $this->request->getParam('sPage', 1),
            'sSort'=> $this->request->getParam('sSort', 7),
            'baseUrl' => '/shopware_5_3v2/',
            'pages' => ceil($this->p13Helper->getTotalHitCount('product', $choice_id) / $criteria->getLimit()),
            'shortParameters' => Shopware()->Container()->get('query_alias_mapper')->getQueryAliases(),
            'listingMode' => 'full_page_reload',
            'sortings' => $sortings,
            'criteria' => $criteria,
            'pageSizes' => explode('|', Shopware()->Container()->get('config')->get('numberArticlesToShow'))
        );
    }

    protected function applyThemeConfig(&$view)
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

    protected function assignSubRenderings(&$view, $viewElement)
    {
        $subRenderings = array();
        if(isset($viewElement['subRenderings'][0]['rendering']['visualElements'])) {
            $subRenderings = $viewElement['subRenderings'][0]['rendering']['visualElements'];
        }
        $view->assign('bxSubRenderings', $subRenderings);
        $view->assign('bxRender', $this);
    }

    protected function getViewElementProduct($visualElement, $additionalParameter)
    {
        $product = false;
        list($variantIndex, $index) = $this->getVariantAndIndex($visualElement, $additionalParameter);
        $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variantIndex);
        $ids = $this->p13Helper->getHitFieldValues('products_ordernumber', 'product', $choice_id);
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->resourceManager->getResource($variantIndex, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product['ordernumber'] == $entity_id){
                        return $product;
                    }
                }
            }

            $product = $this->resourceManager->getResource($entity_id, 'product');
            if(is_null($product)) {
                $product = reset($this->p13Helper->getLocalArticles([$entity_id]));
                $this->resourceManager->setResource($product, $entity_id, 'product');
            }
        }
        return $product;
    }

    protected function prepareCollection($visualElement)
    {
        $variantIndex =$this->getVariant($visualElement['parameters']);
        $collection = $this->resourceManager->getResource($variantIndex, 'collection');
        if(is_null($collection)) {
            $choice_id = $this->p13Helper->getResponse()->getChoiceIdFromVariantIndex($variantIndex);
            $ids = $this->p13Helper->getHitFieldValues('products_ordernumber', 'product', $choice_id);
            $collection = $this->p13Helper->getLocalArticles($ids);
            $this->resourceManager->setResource($collection, $variantIndex, 'collection');
        }
    }

    protected function getVariant($visualElement)
    {
        $variantIndex = 0;
        $parameters = isset($visualElement['visualElement']) ? $visualElement['visualElement']['parameters'] : $visualElement['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'variant') {
                $variantIndex = reset($parameter['values']);
                break;
            }
        }

        return $variantIndex;
    }

    protected function getVariantAndIndex($visualElement, $additionalParameter=array())
    {
        $variantIndex = 0;
        $parameters = isset($visualElement['visualElement']) ? $visualElement['visualElement'] : $visualElement['parameters'];

        $index = isset($additionalParameter['list_index']) ? $additionalParameter['list_index'] : 0;
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'variant') {
                $variantIndex = reset($parameter['values']);
            } else if($parameter['name'] == 'index') {
                $index = reset($parameter['values']);
            }
        }

        return array($variantIndex, $index);
    }

    public function getLocalizedValue($values) {
        return $this->p13Helper->getResponse()->getLocalizedValue($values);
    }

    public function setDataForRendering($renderingData)
    {
        $this->renderingData = $renderingData;
        return $this;
    }

    public function getTemplateDataToBeAssigned($narratives)
    {
        $data = array();
        $visualElementTypes = array();
        $order = 0;
        foreach($narratives as $visualElement)
        {
            $visualElementTypes[$order]= $this->getFormatOfElement($visualElement['visualElement']);
            $order+=1;
        }

        foreach($visualElementTypes as $order=>$type)
        {
            $data[$type] = $this->getDataByType($type, $narratives[$order]);
        }

        return $data;
    }

    /**
     * Gets data for the narrative ellements
     * @param $type
     * @param $viewElement
     * @return array|bool|mixed|null
     */
    public function getDataByType($type, $viewElement)
    {
        $data = array();
        switch($type) {
            case self::RENDER_NARRATIVE_TYPE_PRODUCT:
                $data = $this->getViewElementProduct($viewElement);
                break;
            case self::RENDER_NARRATIVE_TYPE_LIST:
                $data = $this->getListingData($viewElement);
                break;
            case self::RENDER_NARRATIVE_TYPE_FACETS:
                $data = $this->getFilterPanelData();
                break;
            case self::RENDER_NARRATIVE_TYPE_BANNER:
                $data = $this->getBannerData($viewElement);
                break;
            case self::RENDER_NARRATIVE_TYPE_BLOG:
                $data = $this->getBlogArticle($viewElement);
                break;
            case self::RENDER_NARRATIVE_TYPE_VOUCHER:
                $data = $this->getVoucherData($viewElement);
            default:
                break;
        }

        return $data;
    }

    protected function getFormatOfElement($element)
    {
        $type='';
        $parameters = $element['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'format') {
                $type  = reset($parameter['values']);
                break;
            }
        }

        return $type;
    }
}
