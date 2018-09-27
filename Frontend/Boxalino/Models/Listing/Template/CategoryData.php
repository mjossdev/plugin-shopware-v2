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
    CONST BX_CATEGORY_TEMPLATE_DATA_PREFIX = "bx-seo-";

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
        'metaKeywords' => 'bx-html-meta-tags-keywords',
        'metaDescription' => 'bx-html-meta-tags-description'
    );

    /**
     * some category attributes are to be excluded from manipulations
     *
     * @var array
     */
    protected $excludedParams = array('id', 'parentId', 'blog', 'path', 'media', 'attributes', 'childrenCount');

    /**
     * request parameters that should be ignored when creating the route
     *
     * @var array
     */
    protected $defaultRequestParams = array('module', 'controller', 'action','sCategory', 'rewriteUrl');

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
     * to the end of the list of breadcrumbs, add another breadcrumb piece
     *  - name as the title
     *  - url/link
     *
     * if it has been configured for the breadcrumb to overwrite the existing category, it won`t add new breadcrumb
     * The breadcrumb should be added only if there are other extra-info keys, otherwise it can not be connected to a dynamic category
     */
    protected function updateBreadcrumbs()
    {
        $breadcrumbValue = $this->p13nHelper->getExtraInfoWithKey(self::BX_CATEGORY_TEMPLATE_DATA_PREFIX . "breadcrumbs");
        if($breadcrumbValue)
        {
            return $this->prepareBreadcrumbs($breadcrumbValue);
        }
    }

    /**
     * update category data according to the response params
     * @return $this
     */
    protected function updateData()
    {
        if(!isset($data['sCategoryContent']))
        {
            return $this;
        }

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

    /**
     * selecting strategy for the breadcrumbs
     *
     * @param $params
     * @return Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData
     */
    protected function prepareBreadcrumbs($params)
    {
        $options = json_decode($params, true);
        if(isset($options[0]['replace']) && $options[0]['replace'])
        {
            return $this->replaceCurrentCategoryBreadcrumb($options[0]);
        }

        return $this->addNewBreadcrumb($options[0]);
    }

    /**
     * add a new breadcrumb to the list of breadcrumbs
     *
     * @param $options
     * @return $this
     */
    protected function addNewBreadcrumb($options)
    {
        end($this->data['sBreadcrumb']);
        $key = key($this->data['sBreadcrumb']);
        $breadcrumb = array(
            'name' => $options['label'],
            'link' => $options['link'] ? $options['link'] : $this->getLinkForBreadcrumb(),
            'blog' => false
        );

        $this->data['sBreadcrumb'][$key+1] = $breadcrumb;
        return $this;
    }

    /**
     * replace current category breadcrumb with the new one
     *
     * @param $options
     * @return $this
     */
    protected function replaceCurrentCategoryBreadcrumb($options)
    {
        $key = array_search($this->data['sCategoryContent']['id'], array_column($this->data['sBreadcrumb'], 'id'));
        $this->data['sBreadcrumb'][$key]['name'] = $options['label'];
        $this->data['sBreadcrumb'][$key]['link'] = $options['link'] ? $options['link'] : $this->getLinkForBreadcrumb();

        return $this;
    }

    /**
     * creating the link for the breadcrumb (if it was not provided)
     *
     * @TODO retrieve the SEO-friendly URL of the category
     * @return string
     */
    protected function getLinkForBreadcrumb()
    {
        $correlation = array_diff_key($this->data['params'], array_flip($this->defaultRequestParams));
        $catId = $this->data['sCategoryContent']['id'];
        $url = http_build_query($correlation);

        return "shopware.php?sViewPort=cat&sCategory={$catId}&{$url}";
    }
}
