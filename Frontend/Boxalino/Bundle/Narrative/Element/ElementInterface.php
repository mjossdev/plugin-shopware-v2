<?php

interface Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Element_ElementInterface
{
    public function getElement($variantIndex, $index);
    public function getType();
}
