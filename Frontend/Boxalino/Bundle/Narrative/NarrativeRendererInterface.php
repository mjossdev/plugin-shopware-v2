<?php
interface Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_NarrativeRendererInterface
{
    public function renderElement($viewElement, $additionalParameter = [], $otherTemplateData = []);
    public function getLocalizedValue($values, $key=null);
    public function renderDependencies($dependencies);
}