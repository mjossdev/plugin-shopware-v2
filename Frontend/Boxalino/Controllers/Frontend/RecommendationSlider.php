<?php
use Shopware\Components\CSRFWhitelistAware;
class Shopware_Controllers_Frontend_RecommendationSlider extends Enlight_Controller_Action
    implements CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return [
            'index',
            'detail',
            'productStreamSliderRecommendations',
            'portfolioRecommendation',
            'blogRecommendation',
            'detailBlogRecommendation'

        ];
    }

    protected $emotionSliderParams = ['bxChoiceId', 'bxCount', 'category_id'];

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

    public function detailAction()
    {
        $choiceIds = array();
        $this->config = Shopware()->Config();
        $id = $this->request->getParam('articleId');
        if($id == 'sCategory') {
            return;
        } else if($id == '') {
            return;
        }
        $categoryId = $this->request->getParam('sCategory');
        $number = $this->Request()->getParam('number', null);
        $selection = $this->Request()->getParam('group', array());

        $bxData = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
        if (!$bxData->isValidCategory($categoryId)) {
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
        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->Request());

        $allowDuplicatesOnPDPRecommendations = (bool) $this->config->get("boxalino_allow_duplicate_on_pdp");
        $contextParams = $this->prepareContextParams($sArticles);
        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if ($this->config->get("{$recommendation}_enabled")) {
                $choiceId = $this->config->get("{$recommendation}_name");
                $max = $this->config->get("{$recommendation}_max");
                $min = $this->config->get("{$recommendation}_min");
                $helper->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, [], false, $contextParams, false, $allowDuplicatesOnPDPRecommendations);
                $choiceIds[$recommendation] = $choiceId;
            }
        }

        foreach ($this->_productRecommendations as $var_name => $recommendation) {
            if (isset($choiceIds[$recommendation])) {
                $hitIds = $helper->getRecommendation($choiceIds[$recommendation], 0, 0, 0, 0, null, true, [], false, [], false, $allowDuplicatesOnPDPRecommendations);
                $sArticles[$var_name] = array_merge($helper->getLocalArticles($hitIds));
                $sArticles[$var_name."Tracking"] = $this->getTrackingHtmlAttributes($helper, $choiceIds[$recommendation]);
            }
        }

        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/detail/recommendation.tpl');
        $this->View()->assign('sArticle', $sArticles);
    }

    /**
     * Setting details about related/similar products defined on article definition
     *
     * @param $article
     * @return array
     */
    protected function prepareContextParams($article)
    {
        $contextParams = array();
        foreach ($this->_productRecommendations as $key => $recommendation) {
            if(!isset($contextParams['bx_' . $key . '_' . $article['articleID']]) && isset($article[$key]) && !empty($article[$key])){
                $contextParams['bx_' . $key . '_' . $article['articleID']] = array();
                foreach ($article[$key] as $rec) {
                    $contextParams['bx_' . $key . '_' . $article['articleID']][] = $rec['articleID'];
                }
            }
        }

        return $contextParams;
    }

    /**
     * Recommendation for boxalino emotion slider
     */
    public function productStreamSliderRecommendationsAction()
    {
        $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
        $helper->setRequest($this->request);
        $choiceId = $this->Request()->getQuery('bxChoiceId');
        $count = $this->Request()->getQuery('bxCount');
        $context = $this->Request()->getQuery('category_id');
        if(is_null($context))
        {
            $context = Shopware()->Shop()->getCategory()->getId();
        }
        $requestContextParams = array_diff($this->Request()->getParams(), $this->emotionSliderParams);
        $helper->getRecommendation($choiceId, $count, $count, 0, $context, 'category', false, [], false, $requestContextParams);
        $hitsIds = $helper->getRecommendation($choiceId);
        $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
        $this->View()->addTemplateDir($path . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/recommendation_slider/product_stream_slider_recommendations.tpl');

        if(!empty($hitsIds)) {
            $this->View()->assign('articles', $helper->getLocalArticles($hitsIds));
            $this->View()->assign('title', $helper->getSearchResultTitle($choiceId));
            $this->View()->assign($this->getTrackingHtmlAttributes($helper, $choiceId));
            $this->View()->assign('productBoxLayout', "emotion");
        }
    }

    public function portfolioRecommendationAction()
    {
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
            $this->View()->assign($this->getTrackingHtmlAttributes($helper, $choiceId));
            $this->View()->assign('withAddToBasket', true);
            $this->View()->assign('productBoxLayout', "emotion");
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }

    }

    public function blogRecommendationAction()
    {
        try{
            $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
            $bxData = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
            $choiceId = 'read_portfolio';
            $min = 10;
            $max = 10;
            $context = $this->Request()->getQuery('category_label');
            $helper->getRecommendation($choiceId, $max, $min, 0, $context, 'portfolio_blog', false, array(), true);
            $fields = ['products_blog_title', 'products_blog_id', 'products_blog_category_id', 'products_blog_short_description', 'products_blog_media_id'];
            $blogs = $helper->getRecommendationHitFieldValues($choiceId, $fields);
            $blogArticles = $bxData->transformBlog($blogs);
            $this->View()->loadTemplate('frontend/_includes/product_slider_items.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
            $this->View()->assign('articles', $blogArticles);
            $this->View()->assign($this->getTrackingHtmlAttributes($helper, $choiceId));
            $this->View()->assign('bxBlogRecommendation', true);
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }
    }

    public function detailBlogRecommendationAction()
    {
        try{
            $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
            $bxData = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
            $choiceId = Shopware()->Config()->get('boxalino_detail_blog_recommendation_name');
            $min = Shopware()->Config()->get('boxalino_detail_blog_recommendation_min');
            $max = Shopware()->Config()->get('boxalino_detail_blog_recommendation_max');
            $context = $this->Request()->getQuery('articleId');
            $relatedBlogs = $bxData->getRelatedBlogs($context);
            $contextParams = ["bx_{$choiceId}_$context" => $relatedBlogs];
            $helper->getRecommendation($choiceId, $max, $min, 0, $context, 'product', false, array(), true, $contextParams);
            $fields = ['products_blog_title', 'products_blog_id', 'products_blog_category_id', 'products_blog_short_description', 'products_blog_media_id'];
            $blogs = $helper->getRecommendationHitFieldValues($choiceId, $fields);
            $articles = $bxData->transformBlog($blogs);

            $this->View()->loadTemplate('frontend/plugins/boxalino/detail/ajax_blog_rec.tpl');
            $path = Shopware()->Plugins()->Frontend()->Boxalino()->Path();
            $this->View()->addTemplateDir($path . 'Views/emotion/');
            $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
            $this->View()->assign('articles', $articles);
            $this->View()->assign('bxBlogRecommendation', true);
            $this->View()->assign('fixedImage', true);
            $this->View()->assign('productBoxLayout', 'emotion');
            $this->View()->assign('Data', ['article_slider_arrows' => 1]);
            $this->View()->assign('sBlogTitle', $helper->getSearchResultTitle($choiceId));
            $this->View()->assign($this->getTrackingHtmlAttributes($helper, $choiceId));
        } catch(\Exception $exception) {
            Shopware()->Plugins()->Frontend()->Boxalino()->logException($exception, __FUNCTION__, $this->request->getRequestUri());
        }
    }

    protected function getTrackingHtmlAttributes($helper, $choiceId=null)
    {
        return [
            "bx_request_uuid" => $helper->getRequestUuid($choiceId),
            "bx_request_groupby" => $helper->getRequestGroupBy($choiceId)
        ];
    }

}