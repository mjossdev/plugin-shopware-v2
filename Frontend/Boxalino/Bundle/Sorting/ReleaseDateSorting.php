<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_ReleaseDateSorting
    extends \Shopware\Bundle\SearchBundle\Sorting\ReleaseDateSorting
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{
    const BOXALINO_SORT_FIELD = "products_datum";

    public function useAdditionalSorting()
    {
        return true;
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
