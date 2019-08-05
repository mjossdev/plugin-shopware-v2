<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Product
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
{

    CONST RENDER_NARRATIVE_ELEMENT_TYPE = 'product';

    public function getElement($variantIndex, $index)
    {
        $product = false;
        $choice_id = $this->getHelper()->getResponse()->getChoiceIdFromVariantIndex($variantIndex);
        $ids = $this->getHelper()->getHitFieldValues('products_ordernumber', $this->getType(), $choice_id);
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->getResourceManager()->getResource($variantIndex, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product['ordernumber'] == $entity_id) {
                        return $product;
                    }
                }
            }

            $product = $this->getResourceManager()->getResource($entity_id, 'product');
            if(is_null($product)) {
                $product = reset($this->dataHelper->getLocalArticles([$entity_id]));
                $this->getResourceManager()->setResource($product, $entity_id, 'product');
            }
        }

        return $product;
    }

}
