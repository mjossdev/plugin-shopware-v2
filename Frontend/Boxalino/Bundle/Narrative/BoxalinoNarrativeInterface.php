<?php
interface Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_BoxalinoNarrativeInterface
{
    public function getContent();
    public function render(&$view);
    public function getNarratives();
    public function getDependencies();
    public function getRenderer();
}