<?php
interface Shopware_Plugins_Frontend_Boxalino_Bundle_Sorting_BoxalinoSortingInterface
{

    const BOXALINO_BUNDLE_SORTING_DEFAULT = 7;

    /**
     * Flag if to add the additional sorting class
     *
     * @return bool
     */
    public function useAdditionalSorting();

    /**
     * Flag to mark the default sorting scenario
     *
     * @return bool
     */
    public function isDefault();

    /**
     * Name of the Boxalino field to be used for sorting
     *
     * @return string
     */
    public function getSortField();
}
