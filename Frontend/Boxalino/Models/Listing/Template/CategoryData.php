<?php

/**
 * Will modify the properties displayed on listing (categories)
 *
 * Class Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData
 */
class Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData
{
    /**
     * to be used when addind dynamic fields in admin configuration (response, narative, etc) as extraInfo fields
     */
    CONST BX_CATEGORY_TEMPLATE_DATA_PREFIX = "bx-cat-view-";

    /**
     * @var \Boxalino\Helper\P13NHelper|null
     */
    protected $p13nHelper;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * category properties which can be changed via response (by default)
     *
     * @var array
     */
    protected $defaultParams = array(
        'name' => 'bx-page-title',
        'metaTitle' => 'bx-html-meta-title',
        'metaDescription' => 'bx-html-meta-description',
        'description' => 'bx-page-description'
    );

    /**
     * some category attributes are to be excluded from manipulations
     *
     * @var array
     */
    protected $excludedParams = array('id', 'parentId', 'blog', 'path');

    public function __construct($helper, $data = array())
    {
        $this->data = $data;
        $this->p13nHelper = $helper;
    }

    /**
     * before updating, check if there are the extra info keys
     */
    public function update()
    {
        try {
            $this->updateData();
            $this->updateBreadcrumbs();
        } catch(\Exception $e) {
            /** @TODO consider types of exceptions to be caught */
            return $this->data;
        }

        return $this->data;
    }

    /**
     * the latest element of the breadcrumb list is the category view
     * it will be updated with the category name / page title
     */
    protected function updateBreadcrumbs()
    {
        $breadcrumb = end($this->data['sBreadcrumb']);
        $key = key($this->data['sBreadcrumb']);
        $breadcrumb['name'] = $this->data['sCategoryContent']['name'];

        $this->data['sBreadcrumb'][$key] = $breadcrumb;
        return $this;

    }

    /**
     * update category data according to the response params
     * @return $this
     */
    protected function updateData()
    {
        foreach($this->data['sCategoryContent'] as $key=>$value)
        {
            if(in_array($key, $this->excludedParams))
            {
                continue;
            }

            $bxKeyMatch = self::BX_CATEGORY_TEMPLATE_DATA_PREFIX . $key;
            if(isset($this->defaultParams[$key]))
            {
                $bxKeyMatch = $this->defaultParams[$key];
            }

            $bxValue = $this->p13nHelper->getExtraInfoWithKey($bxKeyMatch);
            $this->data['sCategoryContent'][$key] = $bxValue ? $bxValue : $this->data['sCategoryContent'][$key];
        }

        return $this;
    }
}
