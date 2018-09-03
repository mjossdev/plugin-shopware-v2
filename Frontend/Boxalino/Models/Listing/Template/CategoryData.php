<?php

/**
 * Will modify the properties displayed on listing (categories)
 *
 * Class Shopware_Plugins_Frontend_Boxalino_Models_Listing_ViewData
 */
class Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData
{
    /**
     * to be used when addind dynamic fields in admin configuration (response, narative, etc) as extraInfo fields
     */
    CONST BX_CATEGORY_DATA_PREFIX = "bx-page-";

    /**
     * @var \Boxalino\Helper\P13NHelper|null
     */
    protected $p13nHelper;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * category properties which can be changed via response
     * the ones with missing values can be located in the extra info if they`re set or not
     *
     * @var array
     */
    protected $changeableParams = array(
        'name' => 'bx-page-title',
        'metaTitle' => 'bx-html-meta-title',
        'metaKeywords' => '',
        'metaDescription' => 'bx-html-meta-description',
        'cmsHeadline' => '',
        'cmsText' => '',
        'description' => 'bx-page-description'
    );

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
     * updates the data belonging to a category entity
     * @return $this
     */
    protected function updateData()
    {
        foreach($this->data['sCategoryContent'] as $key=>$value)
        {
            if(in_array($key, array_keys($this->changeableParams)))
            {
                $bxKeyMatch = $this->changeableParams[$key];
                if (empty($this->changeableParams[$key]))
                {
                    $bxKeyMatch = self::BX_CATEGORY_DATA_PREFIX . $key;
                }
                $bxValue = $this->p13nHelper->getExtraInfoWithKey($bxKeyMatch);
                $this->data['sCategoryContent'][$key] = $bxValue ? $bxValue : $this->data['sCategoryContent'][$key];
            }
        }

        return $this;
    }

}
