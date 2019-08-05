<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_List
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
{

    CONST RENDER_NARRATIVE_ELEMENT_TYPE   = 'list';

    public function getElement($variantIndex, $index=0)
    {
        $choice_id = $this->getHelper()->getResponse()->getChoiceIdFromVariantIndex($variantIndex);
        $request = $this->getSearchBundle()->getRequest();
        $criteria = $this->getSearchBundle()->getCriteria();

        return array(
            'sPage' =>  $request->getParam('sPage', 1),
            'sSort'=>  $request->getParam('sSort', 7),
            'baseUrl' => '/shopware_5_3v2/',
            'pages' => ceil($this->getHelper()->getTotalHitCount('product', $choice_id) / $criteria->getLimit()),
            'shortParameters' => Shopware()->Container()->get('query_alias_mapper')->getQueryAliases(),
            'listingMode' => 'full_page_reload',
            'criteria' => $criteria,
            'sortings' => $this->getSearchBundle()->getStoreSortings(),
            'pageSizes' => explode('|', Shopware()->Container()->get('config')->get('numberArticlesToShow'))
        );
    }

    public function prepareCollection($variantIndex)
    {
        $collection = $this->getResourceManager()->getResource($variantIndex, 'collection');
        if(is_null($collection)) {
            $choice_id = $this->getHelper()->getResponse()->getChoiceIdFromVariantIndex($variantIndex);
            $ids = $this->getHelper()->getHitFieldValues('products_ordernumber', 'product', $choice_id);
            $collection = $this->getHelper()->getLocalArticles($ids);
            $this->getHelper()->setResource($collection, $variantIndex, 'collection');
        }
    }
}
