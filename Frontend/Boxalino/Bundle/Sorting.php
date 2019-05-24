<?php

use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\Sorting\Sorting;
use Shopware\Bundle\SearchBundle\SortingInterface;

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting
 * Boxalino Sorting lets the products order be the one recommended (via rank) by the Boxalino servers
 *
 * The Boxalino Sorting bundle is an extended implementation over the default Shopware Bundle
 * All Boxalino Sorting types implement Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface interface
 *
 * For other custom sorting options, just contact us at support@boxalino.com
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting
{

    protected $criteria;
    protected $sortId = null;
    protected $listing = false;
    protected $useBoxalinoSort = null;
    protected $db;
    protected $request = null;

    public function __construct()
    {
        $this->useBoxalinoSort = Shopware()->Config()->get('boxalino_navigation_sorting');
        $this->db = Shopware()->Db();
    }

    /**
     * @return array
     */
    public function getSort()
    {
        if($this->listing && is_null($this->sortId) && $this->useBoxalinoSort)
        {
            return array();
        }

        /* @var Shopware\Bundle\SearchBundle\Sorting\Sorting $sort */
        $sort = current($this->criteria->getSortings());
        if(empty($sort))
        {
            $sort = $this->getDefaultBoxalinoSort();
        }

        if($sort instanceof Shopware\Bundle\SearchBundle\Sorting\Sorting)
        {
            $sort = $this->getBoxalinoSortType(get_class($sort), $sort);
        }

        if($sort->isDefault()) {
            if(!$this->listing)
            {
                return array();
            }

            $defaultSort = is_null($this->sortId) ? $this->getDefaultSort() : $this->getSearchCustomSort($this->sortId);
            if(is_null($defaultSort) && $this->useBoxalinoSort == false)
            {
                $sort = new Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_ReleaseDateSorting();
            } elseif(is_null($defaultSort)) {
                return array();
            } else {
                $sort = $defaultSort;
            }
        }

        return $this->getSorting($sort);
    }

    protected function getDefaultSort()
    {
        if(!is_null($this->sortId))
        {
            return $this->getSearchCustomSort($this->sortId);
        }

        $sql = $this->db->select()
            ->from(array('c_e' => 's_core_config_elements', array('c_v.value')))
            ->join(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id')
            ->where("name = ?", "defaultListingSorting");
        $result = $this->db->fetchRow($sql);
        if(isset($result))
        {
            return $this->getSearchCustomSort(unserialize($result['value']));
        }

        return  null;
    }

    /**
     * @param $id
     * @return Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_SearchRankingSorting|null
     */
    protected function getSearchCustomSort($id)
    {
        $sql = $this->db->select()
            ->from(array('c_s' => 's_search_custom_sorting', array('c_s.sortings')))
            ->where("c_s.id = ?", $id);
        $result = $this->db->fetchRow($sql);

        if(!isset($result))
        {
            Shopware()->Container()->get('pluginlogger')->info("BxSorting: there is no default sorting configured for the store.");
            return null;
        }

        $sortData = json_decode($result['sortings'],true);
        $sortClass = array_keys($sortData)[0];
        $sort = $this->getBoxalinoSortType($sortClass);
        $sort->setDirection($sortData[$sortClass]['direction']);

        return $sort;
    }

    protected function getBoxalinoSortType($shpwSortClass, $default = null)
    {
        $type = array_pop(explode("\\", $shpwSortClass));
        $boxalinoSortDI = "Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_" . $type;
        if(class_exists($boxalinoSortDI))
        {
            $sortingDI = new $boxalinoSortDI();
            if(!is_null($default))
            {
                $sortingDI->setDirection($default->getDirection());
            }
            return $sortingDI;
        }

        if(is_null($default))
        {
            return $this->getDefaultBoxalinoSort();
        }

        return $default;
    }

    public function getDefaultBoxalinoSort()
    {
        return new Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_SearchRankingSorting(Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC);
    }

    public function getSorting($sort)
    {
        $boxalinoSorting = new Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSorting($sort);
        return $boxalinoSorting->getSort();
    }

    public function setCriteria(Shopware\Bundle\SearchBundle\Criteria $criteria)
    {
        $this->criteria = $criteria;
        return $this;
    }

    public function setIsListing($listing)
    {
        $this->listing = $listing;
        return $this;
    }

    public function setSortId($sortId)
    {
        $this->sortId = $sortId;
        return $this;
    }

    public function getUseBoxalinoSort()
    {
        return $this->useBoxalinoSort;
    }

}
