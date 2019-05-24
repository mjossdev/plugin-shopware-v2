<?php
interface Shopware_Plugins_Frontend_Boxalino_Bundle_Search_BoxalinoSearchInterface
{
    const BOXALINO_BUNDLE_SEARCH_TYPE_PRODUCT = "product";
    const BOXALINO_BUNDLE_SEARCH_TYPE_BLOG = "blog";
    const BOXALINO_BUNDLE_SEARCH_TYPE_CATEGORY = "category";

    public function getCriteria();
    public function getContext();
    public function getQueryText();
    public function getPageOffset();
    public function getHitCount();
    public function getSort();
    public function getType();
    public function getOptions();
    public function getFilters();
    public function getIsStream();
    public function getOverrideChoice();
    public function getFacets();
    public function setViewData($data=array());
    public function showFacets();
    public function getRequest();
    public function init();
}