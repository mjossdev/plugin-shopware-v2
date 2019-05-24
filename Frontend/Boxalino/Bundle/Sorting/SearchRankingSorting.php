<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_SearchRankingSorting
    extends \Shopware\Bundle\SearchBundle\Sorting\SearchRankingSorting
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{
    const BOXALINO_SORT_FIELD = "boxalino";

    public function useAdditionalSorting()
    {
        return false;
    }

    public function isDefault()
    {
        return true;
    }

    public function getSortField()
    {
        return self::BOXALINO_SORT_FIELD;
    }
}
