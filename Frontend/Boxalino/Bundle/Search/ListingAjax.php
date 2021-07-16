<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_ListingAjax
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearch
{
    protected $sSortRule = null;

    public function init()
    {
        parent::init();
        $listingCount = $this->getRequest()->getActionName() == 'listingCount';
        if(!$listingCount || (!empty($this->stream) && !$this->config->get('boxalino_navigation_product_stream'))) {
            throw new Shopware_Plugins_Frontend_Boxalino_Bundle_NullException("BxListingAjaxError: the stream {$this->stream} can not be used. Please enable Boxalino Search - Navigation -Product-Stream");
        }

    }

    public function getContext()
    {
        return $this->get('shopware_storefront.context_service')->getShopContext();
    }

    public function _request()
    {
        $requestOrder = $this->getRequest()->getParam($this->getOrderParam());
        $this->sSortRule = $requestOrder;

        $sSortParam = $this->getRequest()->getParam("sSort");
        $defaultListingSort = $this->getDefaultListingSorting();
        if(is_null($requestOrder) && is_null($sSortParam))
        {
            $this->getRequest()->setParam("sSort", $defaultListingSort);
            if($this->getSortBundle()->getUseBoxalinoSort())
            {
                $defaultListingSort = null;
                $this->getRequest()->setParam("sSort", Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface::BOXALINO_BUNDLE_SORTING_DEFAULT);
            }
            $this->sSortRule = $defaultListingSort;
        }
    }

    public function getQueryText()
    {
        return $this->getRequest()->getParams()['q'];
    }

    public function getSort()
    {
        $this->getSortBundle()
            ->setSortId($this->sSortRule)
            ->setIsListing(true);

        return parent::getSort();
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
                    throw new Shopware_Plugins_Frontend_Boxalino_Bundle_NullException("BxListingAjaxError: the stream {$this->stream} can not be used. Please enable Boxalino Search - Navigatio -Product-Stream");
                }

                if(isset($filter['missing_condition']))
                {
                    unset($filter['missing_condition']);
                    Shopware()->Container()->get('pluginlogger')->warning(
                        "BxListingAjaxError: the requested stream is not fully integrated. Please contact Boxalino. Stream ID: " .$this->stream . "; conditions:" . $streamConfig[$this->stream]['conditions']
                    );
                }

            } else {
                $filter['products_stream_id'] = [$this->stream];
            }
        }

        if($supplier = $this->getRequest()->getParam('sSupplier')) {
            if(strpos($supplier, '|') === false){
                $supplier_name = $this->dataHelper->getSupplierName($supplier);
                $filter['products_brand'] = [$supplier_name];
            }
        }

        return $filter;
    }

}