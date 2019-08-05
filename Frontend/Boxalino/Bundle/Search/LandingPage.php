<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Search_LandingPage
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearch
{
    public function getContext()
    {
        return $this->get('shopware_storefront.context_service')->getProductContext();
    }

    public function _request()
    {
        $this->setRequestWithRefererParams();
        $requestOrder = $this->getRequest()->getParam($this->getOrderParam());
        $defaultListingSort = $this->getDefaultListingSorting();
        if(is_null($requestOrder))
        {
            $this->getRequest()->setParam("sSort", $defaultListingSort);
            if($this->getSortBundle()->getUseBoxalinoSort()){
                $this->getRequest()->setParam("sSort", Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface::BOXALINO_BUNDLE_SORTING_DEFAULT);
            }
        }
    }

    public function getQueryText()
    {
        return "";
    }

    public function getIsStream()
    {
        return false;
    }

    public function getSort()
    {
        $this->getSortBundle()
            ->setIsListing(true)
            ->setSortId(null);

        return parent::getSort();
    }

    public function getOverrideChoice()
    {
        return "landingpage";
    }

}
