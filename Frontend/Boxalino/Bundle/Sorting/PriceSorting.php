<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_PriceSorting
    extends \Shopware\Bundle\SearchBundle\Sorting\PriceSorting
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{

    const BOXALINO_SORT_FIELD = "products_bx_grouped_price";

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
