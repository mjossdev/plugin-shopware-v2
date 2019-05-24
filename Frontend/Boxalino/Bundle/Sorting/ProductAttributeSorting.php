<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_ProductAttributeSorting
    extends \Shopware\Bundle\SearchBundle\Sorting\ProductAttributeSorting
    implements Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{
    /**
     * @var string
     */
    protected $field;

    const BOXALINO_SORT_FIELD = "products_";

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
        return self::BOXALINO_SORT_FIELD . $this->field;
    }

}
