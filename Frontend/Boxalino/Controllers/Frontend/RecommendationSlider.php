<?php
class Shopware_Controllers_Frontend_RecommendationSlider extends Enlight_Controller_Action {

    /**
     * @var sMarketing
     */
    protected $marketingModule;

    /**
     * @var array
     */
    private $_productRecommendations = array(
        'sRelatedArticles' => 'boxalino_accessories_recommendation',
        'sSimilarArticles' => 'boxalino_similar_recommendation',
        'boughtArticles' => 'boxalino_complementary_recommendation',
        'viewedArticles' => 'boxalino_related_recommendation'
    );

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    public function indexAction() {

        $this->productStreamSliderRecommendationsAction();
    }

    public function detailAction() {
        $choiceIds = array();
        $this->config = Shopware()->Config();
        $this->marketingModule = Shopware()->Modules()->Marketing();
        $id = $this->request->getParam('articleId');
        $sArticles = Shopware()->Modules()->Articles()->sGetPromotionById('fix', 0, $id);
        $viewedArticles = $this->getViewedRecommendations($id);
        $boughtArticles = $this->getBoughtRecommendations($id);
        $sRelatedArticles = isset($sArticles['sRelatedArticles']) ? $sArticles['sRelatedArticles'] : [];
        $sSimilarArticles = isset($sArticles['sSimilarArticles']) ? $sArticles['sSimilarArticles'] : [];

        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        foreach ($this->_productRecommendations as $var_name => $recommendation){
            if($this->config->get("{$recommendation}_enabled")){
                $choiceId = $this->config->get("{$recommendation}_name");
                $max = $this->config->get("{$recommendation}_max");
                $min = $this->config->get("{$recommendation}_min");
                $excludes = array_keys($$var_name);
                $helper->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, $excludes);
                $choiceIds[$recommendation] = $choiceId;
            }
        }

        foreach ($this->_productRecommendations as $var_name => $recommendation){
            if (isset($choiceIds[$recommendation])) {
                $hitIds = $helper->getRecommendation($choiceIds[$recommendation]);
                $checkIds = array_flip($hitIds);
                foreach ($$var_name as $index => $article) {
                    if (isset($checkIds[$index])) {
                        unset($hitIds[$checkIds[$index]]);
                    }
                }
                $articles = array_merge($$var_name, $helper->getLocalArticles($hitIds));
                $sArticles[$var_name] = $articles;
            }
        }

        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/detail/recommendation.tpl');
        $this->View()->assign('sArticle', $sArticles);
    }

    private function getViewedRecommendations($id){

        $maxPages = (int) $this->config->get('similarViewedMaxPages', 10);
        $perPage = (int) $this->config->get('similarViewedPerPage', 4);

        $this->marketingModule->sBlacklist[] = null;
        $this->marketingModule->sBlacklist[] = (int) $id;
        $articles = $this->marketingModule->sGetSimilaryShownArticles($id, $maxPages * $perPage);

        $numbers = array_column($articles, 'number');
        $result = $this->getPromotions($numbers);
        return $result;
    }

    private function getBoughtRecommendations($id){

        $maxPages = (int) $this->config->get('alsoBoughtMaxPages', 10);
        $perPage = (int) $this->config->get('alsoBoughtPerPage', 4);

        $this->marketingModule->sBlacklist[] = null;
        $this->marketingModule->sBlacklist[] = $id;
        $articles = $this->marketingModule->sGetAlsoBoughtArticles($id, $maxPages * $perPage);
        $this->marketingModule->sBlacklist[] = null;

        $numbers = array_column($articles, 'number');
        $result = $this->getPromotions($numbers);
        return $result;
    }

    /**
     * @param string[] $numbers
     * @return array[]
     */
    private function getPromotions($numbers)
    {
        if (empty($numbers)) {
            return [];
        }

        $context = $this->get('shopware_storefront.context_service')->getShopContext();
        $products = $this->get('shopware_storefront.list_product_service')
            ->getList($numbers, $context);

        return $this->get('legacy_struct_converter')->convertListProductStructList($products);
    }

    /**
     * Recommendation for boxalino emotion slider
     */
    public function productStreamSliderRecommendationsAction() {

        $benchmark = Shopware_Plugins_Frontend_Boxalino_Benchmark::instance();
        $benchmark->startRecording(__FUNCTION__);
        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->request);
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