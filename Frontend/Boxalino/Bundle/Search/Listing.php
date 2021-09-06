<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_Listing
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearch
{
    protected $sSortRule = null;

    public function init()
    {
        parent::init();
        if(!empty($this->stream) && !$this->config->get('boxalino_navigation_product_stream'))
        {
            throw new Shopware_Plugins_Frontend_Boxalino_Bundle_NullException("BxListingError: the stream {$this->stream} can not be used. Please enable Boxalino Search - Navigatio -Product-Stream");
        }
    }

    public function _request()
    {
        $requestOrder = $this->getRequest()->getParam($this->getOrderParam());
        $defaultListingSort = $this->getDefaultListingSorting();
        $this->sSortRule = $requestOrder;
        if(is_null($this->sSortRule))
        {
            $sSortValue = $this->_getConfiguredSortRuleOnListing();
            if(is_null($sSortValue))
            {
                $specialCase = $this->config->get('boxalino_navigation_special_enabled');
                $ids = explode(',', $this->config->get('boxalino_navigation_exclude_ids'));
                $sSortValue = $this->getBoxalinoDefaultListingSortingValue();
                if($specialCase && in_array($this->getRequest()->getParam('sCategory'), $ids))
                {
                    $sSortValue = $defaultListingSort;
                }
            }

            $this->getRequest()->setParam("sSort", $sSortValue);
            $this->sSortRule = $sSortValue;
        }

        $sSortParam = $this->getRequest()->getParam("sSort");
        if(is_null($requestOrder) && is_null($sSortParam))
        {
            $this->getRequest()->setParam("sSort", $defaultListingSort);
            if($this->getSortBundle()->getUseBoxalinoSort())
            {
                $defaultListingSort = null;
                $this->getRequest()->setParam("sSort", $this->getBoxalinoDefaultListingSortingValue());
            }
            $this->sSortRule = $defaultListingSort;
        }
    }

    public function getContext()
    {
        return $this->get('shopware_storefront.context_service')->getProductContext();
    }

    public function getQueryText()
    {
        return "";
    }

    public function getSort()
    {
        $this->getSortBundle()
            ->setSortId($this->sSortRule)
            ->setIsListing(true);

        return $this->getSortBundle()->getSort();
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
        }

        if(empty($this->viewData))
        {
            return $filter;
        }

        if(isset($this->viewData['manufacturer']) && !empty($this->viewData['manufacturer'])) {
            $filter['products_brand'] = [$this->viewData['manufacturer']->getName()];
        }

        return $filter;
    }

    /**
     * Check in DB if there is a custom sorting configured on the category (ex: newest first)
     * Solution proposed by @mjossdev
     */
    protected function _getConfiguredSortRuleOnListing()
    {
        /** @var Doctrine\DBAL\Connection $connection */
        $connection = $this->get('dbal_connection');
        $categoryId = $this->getRequest()->getParam('sCategory');
        $sortingIdsStr = $connection->createQueryBuilder()
            ->select('sorting_ids')
            ->from('s_categories')
            ->where('id = :id')
            ->setParameter('id', $categoryId)
            ->execute()
            ->fetchColumn();
        $sortingIds = array_values(array_filter(explode('|', $sortingIdsStr)));
        if (count($sortingIds) > 0)
        {
            return $sortingIds[0];
        }

        return null;
    }


}