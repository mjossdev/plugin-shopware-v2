<?php

use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\Sorting\Sorting;
use Shopware\Bundle\SearchBundle\SortingInterface;

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSorting
 * Generic class to return the sorting compatible with the Boxalino Server
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSorting
{

    protected $selectedSort;
    protected $field;
    protected $reverse = null;

    public function __construct(Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface $selectedSort, $field = null, $direction = null)
    {
        $this->field = $field;
        $this->selectedSort = $selectedSort;
        $this->reverse = $direction;
        if(is_null($direction))
        {
            $this->reverse = $selectedSort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC;
        }

    }

    public function getSort()
    {
        if($this->selectedSort->isDefault())
        {
            return [];
        }

        $sortReturn[] = array(
            'field' => $this->selectedSort->getSortField(),
            'reverse' => $this->reverse
        );

        if($this->selectedSort->useAdditionalSorting())
        {
            $additionalSort = new Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_AdditionalBoxalinoSorting();
            $sortReturn[] = array(
                'field' => $additionalSort->getSortField(),
                'reverse' => $this->reverse
            );
        }

        return $sortReturn;
    }

}
