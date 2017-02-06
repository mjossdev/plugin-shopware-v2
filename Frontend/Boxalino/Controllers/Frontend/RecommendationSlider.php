<?php
class Shopware_Controllers_Frontend_RecommendationSlider extends Enlight_Controller_Action {

    public function indexAction() {
        $this->productStreamSliderRecommendationsAction();
    }
    
    public function productStreamSliderRecommendationsAction() {

        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $choiceId = $this->Request()->getQuery('bxChoiceId');
        $count = $this->Request()->getQuery('bxCount');
        $context = $this->Request()->getQuery('category_id');
        $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
        $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false);
        $articles = $helper->getRecommendation($choiceId);
        $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
        $this->View()->assign('articles', $articles);
        $this->View()->assign('productBoxLayout', "emotion");
    }

}