<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_Search
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearch
{

    public function getContext()
    {
        return $this->get('shopware_storefront.context_service')->getShopContext();
    }

    public function _request()
    {
        $sSortParam = $this->getRequest()->getParam("sSort");
        $defaultListingSort = $this->getDefaultListingSorting();
        if(is_null($sSortParam))
        {
            $this->getRequest()->setParam("sSort", $defaultListingSort);
            if($this->getSortBundle()->getUseBoxalinoSort())
            {
                $this->getRequest()->setParam("sSort", Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface::BOXALINO_BUNDLE_SORTING_DEFAULT);
            }
        }
    }

    public function getQueryText()
    {
        $term = $this->getRequest()->get('sSearch', '');
        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));
        $term = str_replace('/', '', $term);

        return $term;
    }

    public function getOptions()
    {
        return $this->dataHelper->getFacetConfig($this->getFacets(), $this->getRequest(), "products_bx_purchasable");
    }

    public function getIsStream()
    {
        return false;
    }

}