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
        if($id == 'sCategory') {
            $exception = new \Exception("Request with empty parameters from : " . $_SERVER['HTTP_REFERER']);
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
            return;
        } else if($id == '') {
            return;
        }
        $categoryId = $this->request->getParam('sCategory');
        $number = $this->Request()->getParam('number', null);
        $selection = $this->Request()->getParam('group', array());
        if (!$this->isValidCategory($categoryId)) {
            $categoryId = 0;
        }
        $this->config->offsetSet('similarLimit', 0);

        try{
            $sArticles = Shopware()->Modules()->Articles()->sGetArticleById(
                $id,
                $categoryId,
                $number,
                $selection
            );
        }catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
            $sArticles = [];
        }
        $boughtArticles = [];
        $viewedArticles = [];
        $sRelatedArticles = isset($sArticles['sRelatedArticles']) ? $sArticles['sRelatedArticles'] : [];
        $sSimilarArticles = isset($sArticles['sSimilarArticles']) ? $sArticles['sSimilarArticles'] : [];

        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->Request());
        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if ($this->config->get("{$recommendation}_enabled")) {
                $choiceId = $this->config->get("{$recommendation}_name");
                $max = $this->config->get("{$recommendation}_max");
                $min = $this->config->get("{$recommendation}_min");
                $excludes = array();
                if ($var_name == 'sRelatedArticles' ||$var_name == 'sSimilarArticles') {
                    foreach ($$var_name as $article) {
                        $excludes[] = $article['articleID'];
                    }
                }
                $helper->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, $excludes);
                $choiceIds[$recommendation] = $choiceId;
            }
        }

        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if (isset($choiceIds[$recommendation])) {
                $hitIds = $helper->getRecommendation($choiceIds[$recommendation]);
                $articles = array_merge($$var_name, $helper->getLocalArticles($hitIds));
                $sArticles[$var_name] = $articles;
            }
        }

        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/detail/recommendation.tpl');
        $this->View()->assign('sArticle', $sArticles);
    }

    /**
     * Recommendation for boxalino emotion slider
     */
    public function productStreamSliderRecommendationsAction() {
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $t1 = microtime(true);
        }
        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->request);
        $choiceId = $this->Request()->getQuery('bxChoiceId');
        $count = $this->Request()->getQuery('bxCount');
        $context = $this->Request()->getQuery('category_id');
        $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
        $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false);
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $helper->addNotification("Recommendation Slider before response took: " . (microtime(true) - $t1) * 1000 . "ms.");
        }
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $t2 = microtime(true);
        }
        $hitsIds = $helper->getRecommendation($choiceId);
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $helper->addNotification("Recommendation Slider response took: " . (microtime(true) - $t2) * 1000 . "ms.");
        }
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $t3 = microtime(true);
        }
        if($hitsIds) {
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            if ($_REQUEST['dev_bx_debug'] == 'true') {
                $t4 = microtime(true);
            }
            $this->View()->assign('articles', $helper->getLocalArticles($hitsIds));
            if ($_REQUEST['dev_bx_debug'] == 'true') {
                $helper->addNotification("Recommendation Slider getLocalArticles took: " . (microtime(true) - $t4) * 1000 . "ms. IDS: " .json_encode($hitsIds));
            }
            $this->View()->assign('productBoxLayout', "emotion");
        }
        if ($_REQUEST['dev_bx_debug'] == 'true') {
            $helper->addNotification("Recommendation Slider after response took: " . (microtime(true) - $t3) * 1000 . "ms.");
            $helper->addNotification("Recommendation Slider took in total:" . (microtime(true) - $t1) * 1000 . "ms.");
            $helper->callNotification(true);
        }
    }

    private function isValidCategory($categoryId) {
        $defaultShopCategoryId = Shopware()->Shop()->getCategory()->getId();

        /**@var $repository \Shopware\Models\Category\Repository*/
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $categoryPath = $repository->getPathById($categoryId);

        if (!$categoryPath) {
            return true;
        }

        if (!array_key_exists($defaultShopCategoryId, $categoryPath)) {
            return false;
        }

        return true;
    }

    public function portfolioRecommendationAction() {

        try{
            $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
            $helper->setRequest($this->request);
            $choiceId = $this->Request()->getQuery('bxChoiceId');
            $count = $this->Request()->getQuery('bxCount');
            $context = $this->Request()->getQuery('category_id');
            $account_id = $this->Request()->getQuery('account_id');
            $context = Shopware()->Shop()->getCategory()->getId() == $context ? null : $context;
            $refer = $this->Request()->getParam('category');
            if($account_id) {
                $contextParam = array('_system_customerid' => $account_id);
            } else {
                $contextParam = array('_system_customerid' => $helper->getCustomerID());
            }
            $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false, array(), false, $contextParam, true);
            $hitsIds = $helper->getRecommendation($choiceId);
            $articles = $helper->getLocalArticles($hitsIds);
            if($choiceId == 'rebuy') {

                $purchaseDates = $helper->getRecommendationHitFieldValues($choiceId, 'purchase_date');
                foreach ($articles as $i => $article) {
                    $add = array_shift($purchaseDates);
                    $date = reset($add['purchase_date']);
                    if(getdate(strtotime($date))['year'] != 1970) {
                        $article['bxTransactionDate'] = $date;
                        $articles[$i] = $article;
                    }
                }
            }
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/product-box/box-emotion.tpl');
            $this->View()->assign('articles', $articles);
            $this->View()->assign('withAddToBasket', true);
            $this->View()->assign('productBoxLayout', "emotion");
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }

    }


    public function blogRecommendationAction() {

        try{
            $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
            $choiceId = 'read_portfolio';
            $min = 10;
            $max = 10;
            $context = $this->Request()->getQuery('category_label');
            $helper->getRecommendation($choiceId, $max, $min, 0, $context, 'portfolio_blog', false, array(), true);
            $blogIds = $helper->getRecommendation($choiceId, $max, $min, 0, $context, 'portfolio_blog', true, array(), true);
            foreach ($blogIds as $index => $id) {
                $blogIds[$index] = str_replace('blog_', '', $id);
            }
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
            $blogArticles = $helper->getBlogs($blogIds);
            $blogArticles = $this->enhanceBlogArticles($blogArticles);
            $this->View()->assign('articles', $blogArticles);
            $this->View()->assign('bxBlogRecommendation', true);
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }
    }

    private function enhanceBlogArticles($blogArticles) {
        if(empty($blogArticles)) {
            return $blogArticles;
        }
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);

        $context = Shopware()->Container()->get('shopware_storefront.context_service')->getShopContext();
        $medias = Shopware()->Container()->get('shopware_storefront.media_service')->getList($mediaIds, $context);


        foreach ($blogArticles as $key => $blogArticle) {
            //adding number of comments to the blog article
            $blogArticles[$key]["numberOfComments"] = count($blogArticle["comments"]);

            //adding thumbnails to the blog article
            if (empty($blogArticle["media"][0]['mediaId'])) {
                continue;
            }

            $mediaId = $blogArticle["media"][0]['mediaId'];

            if (!isset($medias[$mediaId])) {
                continue;
            }

            /**@var $media \Shopware\Bundle\StoreFrontBundle\Struct\Media*/
            $media = $medias[$mediaId];
            $media = Shopware()->Container()->get('legacy_struct_converter')->convertMediaStruct($media);

            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }
}