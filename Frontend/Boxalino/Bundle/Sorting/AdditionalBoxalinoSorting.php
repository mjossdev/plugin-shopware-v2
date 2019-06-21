<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_AdditionalBoxalinoSorting
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{
    const BOXALINO_SORT_FIELD = "products_changetime";

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

    public function getName()
    {
        return self::BOXALINO_SORT_FIELD;
    }
}
