<?php
class Shopware_Controllers_Frontend_BxNarrative extends Enlight_Controller_Action {

    public function indexAction() {
        $this->getNarrativeAction();
    }

    protected function setRequestWithReferrerParams($request) {

        $address = $_SERVER['HTTP_REFERER'];
        $basePath = $request->getBasePath();
        $start = strpos($address, $basePath) + strlen($basePath);
        $end = strpos($address, '?');
        $length = $end ? $end - $start : strlen($address);
        $pathInfo = substr($address, $start, $length);
        $request->setPathInfo($pathInfo);
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));
        foreach ($params as $index => $param){
            $keyValue = explode("=", $param);
            $params[$keyValue[0]] = $keyValue[1];
            unset($params[$index]);
        }
        foreach ($params as $key => $value) {
            $request->setParam($key, $value);
            if($key == 'p') {
                $request->setParam('sPage', (int) $value);
            }
        }
        return $request;
    }

    protected function getDependencyElement($url, $type) {
        $element = '';
        if($type == 'css'){
            $element = "<link href=\"{$url}\" type=\"text/css\" rel=\"stylesheet\" />";
        } else if($type == 'js') {
            $element = "<script src=\"{$url}\" type=\"text/javascript\"></script>";
        }
        return $element;
    }

    protected function renderDependencies($dependencies) {
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

    public function getNarrativeAction() {
        try{
            $request = Shopware()->Front()->Request();
            $request = $this->setRequestWithReferrerParams($request);
            $params = $request->getParams();
            $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();

            $context  = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
            $criteria = Shopware()->Container()->get('shopware_search.store_front_criteria_factory')->createSearchCriteria($request, $context);
            $hitCount = $criteria->getLimit();
            $pageOffset = $criteria->getOffset();
            $orderParam = Shopware()->Container()->get('query_alias_mapper')->getShortAlias('sSort');
            $defaultSort = null;
            if(is_null($request->getParam($orderParam))) {
                $request->setParam('sSort', 7);
            }
            if(is_null($request->getParam('sSort')) && is_null($request->getParam($orderParam))) {
                if(Shopware()->Config()->get('boxalino_navigation_sorting')) {
                    $request->setParam('sSort', 7);
                } else {
                    $default = Shopware()->Container()->get('config')->get('defaultListingSorting');
                    $request->setParam('sSort', $default);
                }
            }

            $sort =  $this->getSortOrder($criteria, null, true);
            $narratives = $helper->getNarrative($hitCount, $pageOffset, $sort, $params);
            $dependencies = $this->renderDependencies($helper->getNarrativeDependencies());
            $searchInterceptor = Shopware()->Plugins()->Frontend()->Boxalino()->getSearchInterceptor();
            $bxData = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
            $bxRender = new Shopware_Plugins_Frontend_Boxalino_Helper_BxRender($helper, $bxData, $searchInterceptor, $request);

            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Views/emotion/');
            $this->View()->loadTemplate('frontend/plugins/boxalino/journey/main.tpl');

            $this->View()->assign('dependencies', $dependencies);
            $this->View()->assign('narrative', $narratives);
            $this->View()->assign('bxRender', $bxRender);
        }catch (\Exception $e) {
            var_dump($e->getMessage());exit;
        }

    }

    public function getSortOrder(Shopware\Bundle\SearchBundle\Criteria $criteria, $default_sort = null, $listing = false) {

        /* @var Shopware\Bundle\SearchBundle\Sorting\Sorting $sort */
        $sort = current($criteria->getSortings());
        $dir = null;
        $additionalSort = null;
        if($listing && is_null($default_sort) && Shopware()->Config()->get('boxalino_navigation_sorting')){
            return array();
        }
        switch ($sort->getName()) {
            case 'popularity':
                $field = 'products_sales';
                break;
            case 'prices':
                $field = 'products_bx_grouped_price';
                break;
            case 'product_name':
                $field = 'title';
                break;
            case 'release_date':
                $field = 'products_datum';
                $additionalSort = true;
                break;
            default:
                if ($listing == true) {
                    $default_sort = is_null($default_sort) ? $this->getDefaultSort() : $default_sort;
                    switch ($default_sort) {
                        case 1:
                            $field = 'products_datum';
                            $additionalSort = true;
                            break 2;
                        case 2:
                            $field = 'products_sales';
                            break 2;
                        case 3:
                        case 4:
                            if ($default_sort == 3) {
                                $dir = false;
                            }
                            $field = 'products_bx_grouped_price';
                            break 2;
                        case 5:
                        case 6:
                            if ($default_sort == 5) {
                                $dir = false;
                            }
                            $field = 'title';
                            break 2;
                        default:
                            if (Shopware()->Config()->get('boxalino_navigation_sorting') == false) {
                                $field = 'products_datum';
                                $additionalSort = true;
                                break 2;
                            }
                            break;
                    }
                }
                return array();
        }
        $sortReturn[] = array(
            'field' => $field,
            'reverse' => (is_null($dir) ? $sort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
        );
        if($additionalSort) {
            $sortReturn[] = array(
                'field' => 'products_changetime',
                'reverse' => (is_null($dir) ? $sort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
            );
        }
        return $sortReturn;
    }

    protected function getDefaultSort(){
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from(array('c_e' => 's_core_config_elements', array('c_v.value')))
            ->join(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id')
            ->where("name = ?", "defaultListingSorting");
        $result = $db->fetchRow($sql);
        return isset($result) ? unserialize($result['value']) : null;

    }

}