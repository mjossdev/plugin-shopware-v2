<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Facets
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
{

    CONST RENDER_NARRATIVE_ELEMENT_TYPE  = 'facets';

    public function getElement($variantIndex=0, $index=0)
    {
        $facets = $this->getSearchBundle()->getFacetBundle()->updateFacetsWithResult();
        return array(
            'facets' => $facets,
            'bxFacets' => $this->getHelper()->getFacets('product'),
            'criteria' =>  $this->getSearchBundle()->getCriteria(),
            'listingMode' => 'full_page_reload',
            'sSort'=> $this->getSearchBundle()->getRequest()->getParam('sSort', 7),
            'facetOptions'=> $this->getSearchBundle()->getFacetBundle()->getFacetOptions(),
            'shortParameters' => Shopware()->Container()->get('query_alias_mapper')->getQueryAliases()
        );
    }

}
