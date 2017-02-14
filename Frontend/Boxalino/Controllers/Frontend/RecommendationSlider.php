<?php
class Shopware_Controllers_Frontend_RecommendationSlider extends Enlight_Controller_Action {

    public function indexAction() {
        $this->productStreamSliderRecommendationsAction();
    }
    
    public function productStreamSliderRecommendationsAction() {

        $benchmark = Shopware_Plugins_Frontend_Boxalino_Benchmark::instance();
        $benchmark->startRecording(__FUNCTION__);
        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $choiceId = $this->Request()->getQuery('bxChoiceId');
        $count = $this->Request()->getQuery('bxCount');
        $context = $this->Request()->getQuery('category_id');
        $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
        $benchmark->log("before setRecommendation on p13n");
        $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false);
        $benchmark->log("after setRecommendation on p13n");
        $benchmark->log("before getRecommendation on p13n");
        $articles = $helper->getRecommendation($choiceId);
        $benchmark->log("after getRecommendation on p13n");
        $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
        $this->View()->assign('articles', $articles);
        $this->View()->assign('productBoxLayout', "emotion");
        $benchmark->endRecording();
    }

}