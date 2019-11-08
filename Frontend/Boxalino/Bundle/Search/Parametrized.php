<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_Parametrized
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_Parametrized
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearch
{
    protected $sSortRule = null;


    public function _request()
    {
        $this->setRequestWithRefererParams();
        $sSortParam = $this->getRequest()->getParam("sSort");
        $defaultListingSort = $this->getDefaultListingSorting();
        if(is_null($sSortParam))
        {
            $this->getRequest()->setParam("sSort", $defaultListingSort);
            if($this->getSortBundle()->getUseBoxalinoSort())
            {
                $defaultListingSort = null;
                $this->getRequest()->setParam("sSort", Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface::BOXALINO_BUNDLE_SORTING_DEFAULT);
            }

            $this->sSortRule = $defaultListingSort;
        }

        if (!$this->getRequest()->getParam('sCategory')) {
            $this->getRequest()->setParam('sCategory', $this->getContext()->getShop()->getCategory()->getId());
        }
    }

    public function getContext()
    {
        return $this->get('shopware_storefront.context_service')->getShopContext();
    }

    public function getQueryText()
    {
        $request = $this->getRequest();
        if(empty($request))
        {
            if(!empty($this->viewData)&&isset($this->viewData['sSearch']))
            {
                return $this->viewData["sSearch"];
            }

            return "";
        }

        $term = $request->get('sSearch', '');
        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));
        $term = str_replace('/', '', $term);

        return $term;
    }

    public function getOptions()
    {
        if($this->showFacets())
        {
            return parent::getOptions();
        }

        return [];
    }

    public function getFilters()
    {
        $filter = parent::getFilters();

        if($this->getIsStream())
        {
            $streamConfig = $this->dataHelper->getStreamById($this->stream);
            if($streamConfig[$this->stream]['conditions']) {
                $conditions = $this->dataHelper->unserialize(json_decode($streamConfig[$this->stream]['conditions'], true, 10, JSON_OBJECT_AS_ARRAY));
                $filter = $this->dataHelper->getConditionFilter($conditions);
                if(is_null($filter)) {
                    throw new Shopware_Plugins_Frontend_Boxalino_Bundle_NullException(
                        "BxListingError: the streaming filter is corrupt or missing integration scenario. Stream ID: " .$this->stream . "; conditions:" . $streamConfig[$this->stream]['conditions']
                    );
                }

                if(isset($filter['missing_condition']))
                {
                    unset($filter['missing_condition']);
                    Shopware()->Container()->get('pluginlogger')->warning(
                        "BxListingError: the requested stream is not fully integrated. Please contact Boxalino. Stream ID: " .$this->stream . "; conditions:" . $streamConfig[$this->stream]['conditions']
                    );
                }
            } else {
                $filter['products_stream_id'] = [$this->stream];
            }

            $filter['stream'] = true;
        }

        if(empty($this->viewData))
        {
            return $filter;
        }

        $keys = array_keys($this->viewData);
        foreach($keys as $key)
        {
            $brands = [];
            if(strpos($key, 'supplierID'))
            {
                $ids = explode(",", $this->viewData[$key]);
                foreach($ids as $id)
                {
                    $brands[] = $this->dataHelper->getSupplierName($id);
                }
                $filter['products_brand'] = $brands;
            }
        }

        if(isset($this->viewData['manufacturer']) && !empty($this->viewData['manufacturer'])) {
            $filter['products_brand'] = [$this->viewData['manufacturer']->getName()];
        }

        return $filter;
    }

}