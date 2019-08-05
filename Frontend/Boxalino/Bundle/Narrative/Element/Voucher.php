<?php

class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Voucher
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_Abstract
{

    CONST RENDER_NARRATIVE_ELEMENT_TYPE = 'voucher';

    public function getElement($variantIndex, $index=0)
    {
        $choiceId = $this->getHelper()->getVariantChoiceId($variantIndex);
        return $this->getHelper()->getVoucherResponse($choiceId);
    }

}
