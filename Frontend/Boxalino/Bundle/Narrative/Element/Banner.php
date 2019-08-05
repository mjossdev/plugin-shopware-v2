<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Banner
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
{
    CONST RENDER_NARRATIVE_ELEMENT_TYPE  = 'banner';

    public function getElement($variantIndex, $index=0)
    {
        $choiceId = $this->getHelper()->getVariantChoiceId($variantIndex);
        return $this->getHelper()->getBannerData($choiceId);
    }

}
