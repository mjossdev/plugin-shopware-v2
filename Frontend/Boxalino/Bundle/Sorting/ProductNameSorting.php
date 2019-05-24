<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_ProductNameSorting
    extends \Shopware\Bundle\SearchBundle\Sorting\ProductNameSorting
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{

    const BOXALINO_SORT_FIELD = "title";

    public function useAdditionalSorting()
    {
        return false;
    }

    public function isDefault()
    {
        return false;
    }

    public function getSortField()
    {
        return self::BOXALINO_SORT_FIELD;
    }
}
