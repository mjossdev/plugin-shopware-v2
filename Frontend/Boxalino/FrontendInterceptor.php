<?php

/**
 * frontend interceptor
 */
class Shopware_Plugins_Frontend_Boxalino_FrontendInterceptor
    extends Shopware_Plugins_Frontend_Boxalino_Interceptor {

    private $_productRecommendations = array(
        'sRelatedArticles' => 'boxalino_accessories_recommendation',
        'sSimilarArticles' => 'boxalino_similar_recommendation'
    );

    private $_productRecommendationsGeneric = array(
        'sCrossBoughtToo' => 'boxalino_complementary_recommendation',
        'sCrossSimilarShown' => 'boxalino_related_recommendation'
    );

    protected static $similarReq = false;

    protected static $alsoReq = false;

    protected $ajaxEvents = ["ajaxAmount"];


    /**
     * add tracking, product recommendations
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function intercept(Enlight_Event_EventArgs $arguments) {

        $this->init($arguments);
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }

        $script = null;
        if(in_array($this->Request()->getActionName(), $this->ajaxEvents))
        {
            return $script;
        }

        switch ($this->Request()->getParam('controller')) {
            case 'detail':
                $sArticle = $this->View()->sArticle;
                if(is_null($sArticle) || !isset($sArticle['articleID']))break;
                $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
                if ($this->Config()->get('boxalino_detail_recommendation_ajax')) {
                    if(version_compare(Shopware::VERSION, '5.3.0', '>=')) {
                        $this->View()->extendsTemplate('frontend/plugins/boxalino/detail/index_ajax_5_3.tpl');
                    } else {
                        $this->View()->extendsTemplate('frontend/plugins/boxalino/detail/index_ajax.tpl');
                    }
                    if ($this->Config()->get('boxalino_detail_blog_recommendation')) {
                        $this->View()->assign('bx_load_blogs', true);
                    }
                } else {
                    $id = trim(strip_tags(htmlspecialchars_decode(stripslashes($this->Request()->sArticle))));
                    $choiceIds = array();
                    $recommendations = array_merge($this->_productRecommendations, $this->_productRecommendationsGeneric);
                    foreach ($recommendations as $articleKey => $configOption) {
                        if($this->Config()->get("{$configOption}_enabled")){
                            $excludes = array();
                            if ($articleKey == 'sRelatedArticles' || $articleKey == 'sSimilarArticles') {
                                if (isset($sArticle[$articleKey]) && is_array($sArticle[$articleKey])) {
                                    foreach ($sArticle[$articleKey] as $article) {
                                        $excludes[] = $article['articleID'];
                                    }
                                }
                            }
                            $choiceId = $this->Config()->get("{$configOption}_name");
                            $max = $this->Config()->get("{$configOption}_max");
                            $min = $this->Config()->get("{$configOption}_min");
                            $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, $excludes);
                            $choiceIds[$configOption] = $choiceId;
                        }
                    }

                    if (count($choiceIds)) {
                        foreach ($this->_productRecommendations as $articleKey => $configOption) {
                            if (array_key_exists($configOption, $choiceIds)) {
                                $hitIds = $this->Helper()->getRecommendation($choiceIds[$configOption]);
                                $sArticle[$articleKey] = array_merge($sArticle[$articleKey], $this->Helper()->getLocalArticles($hitIds));
                                $sArticle[$articleKey."Tracking"]= $this->getTrackingHtmlAttributes($choiceIds[$configOption]);
                            }
                        }
                    }
                    $this->View()->assign('sArticle', $sArticle);
                    if ($this->Config()->get('boxalino_detail_blog_recommendation')) {
                        $choiceId = $this->Config()->get('boxalino_detail_blog_recommendation_name');
                        $min = $this->Config()->get('boxalino_detail_blog_recommendation_min');
                        $max = $this->Config()->get('boxalino_detail_blog_recommendation_max');
                        $id = trim(strip_tags(htmlspecialchars_decode(stripslashes($this->Request()->sArticle))));
                        $relatedBlogs = $this->BxData()->getRelatedBlogs($id);
                        $contextParams = ["bx_{$choiceId}_$id" => $relatedBlogs];
                        $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, array(), true, $contextParams);
                        $fields = ['products_blog_title', 'products_blog_id', 'products_blog_category_id', 'products_blog_short_description', 'products_blog_media_id'];
                        $blogs = $this->Helper()->getRecommendationHitFieldValues($choiceId, $fields);
                        $blogArticles = $this->BxData()->transformBlog($blogs);
                        $this->View()->extendsTemplate('frontend/plugins/boxalino/detail/content.tpl');
                        $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
                        $this->View()->assign('sBlogArticles', $blogArticles);
                        $this->View()->assign('sBlogArticlesTracking', $this->getTrackingHtmlAttributes($choiceId));
                        $this->View()->assign('sBlogTitle', $this->Helper()->getSearchResultTitle($choiceId));
                    }
                }

                $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportProductView($sArticle['articleDetailsID']);
                break;
            case 'search':
                $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportSearch($this->Request());
                break;
            case 'cat':
                $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportCategoryView($this->Request()->sCategory);
                break;
            case 'recommendation':
                break;
            case 'checkout':
                $this->basket($arguments);
                $script = $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportPageView();
                break;
            case 'account':
                if ($_SESSION['Shopware']['sUserId'] != null && $this->Request()->getActionName() == "login") {
                    Shopware_Plugins_Frontend_Boxalino_EventReporter::reportTrackLogin($_SESSION['Shopware']['sUserId']);
                }
                $script = $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportPageView();
            default:
                $param = $this->Request()->getParam('callback');
                // skip ajax calls
                if (empty($param) && strpos($this->Request()->getPathInfo(), 'ajax') === false) {
                    $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportPageView();
                }
        }
        $this->addScript($script);
        return false;
    }

    public function get($name) {
        return Shopware()->Container()->get($name);
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return array
     */
    public function similarRecommendation(Enlight_Event_EventArgs $arguments) {
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_related_recommendation_hook')) {
            return $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            );
        }
        $choice_id = $this->Config()->get('boxalino_related_recommendation_name');
        if(!self::$similarReq) {
            $articleID = $arguments->getArgs()[0];
            $min = $this->Config()->get('boxalino_related_recommendation_min');
            $max = $this->Config()->get('boxalino_related_recommendation_max');
            $context = $articleID;
            $this->Helper()->getRecommendation($choice_id, $max, $min, 0, $context, 'product', false, array(), false);
            self::$similarReq = true;
        }
        $hitIds = $this->Helper()->getRecommendation($choice_id);
        $return = array();
        foreach ($hitIds as $ordernumber) {
            $id = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($ordernumber);
            $return[] = ['id' => $id, 'ordernumber' => $ordernumber, 'sales' => 1];
        }
        return $return;
    }

    /**
     * @param Enlight_Event_EventArgs $arguments
     * @return array
     */
    public function alsoBoughtRecommendation(Enlight_Event_EventArgs $arguments) {
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_complementary_recommendation_hook')) {
            return $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            );
        }
        $choice_id = $this->Config()->get('boxalino_complementary_recommendation_name');
        if(!self::$alsoReq) {
            $articleID = $arguments->getArgs()[0];
            $min = $this->Config()->get('boxalino_complementary_recommendation_min');
            $max = $this->Config()->get('boxalino_complementary_recommendation_max');
            $context = $articleID;
            $this->Helper()->getRecommendation($choice_id, $max, $min, 0, $context, 'product', false, array(), false);
            self::$alsoReq = true;
        }
        $hitIds = $this->Helper()->getRecommendation($choice_id);
        $return = array();
        foreach ($hitIds as $ordernumber) {
            $id = Shopware()->Modules()->Articles()->sGetArticleIdByOrderNumber($ordernumber);
            $return[] = ['id' => $id, 'ordernumber' => $ordernumber, 'sales' => 1];
        }
        return $return;
    }

    /**
     * basket recommendations
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function basket(Enlight_Event_EventArgs $arguments)
    {
        if ($this->Request()->getControllerName() != 'checkout') {
            return null;
        }
        if($this->Request()->getActionName() != 'ajaxCart' && $this->Request()->getActionName() != 'cart'){
            return null;
        }

        if($this->Request()->getActionName() == 'ajaxCart' && !$this->Config()->get('boxalino_cart_recommendation_enabled')){
            return null;
        }

        if($this->Request()->getActionName() == 'cart' && !$this->Config()->get('boxalino_cart_recommendation_checkout')){
            return null;
        }

        $viewData = $this->View()->getAssign();
        if(isset($viewData['bxBasket'])) {
            return null;
        }

        $basket = $this->Helper()->getBasket($arguments);
        $contextItems = $basket['content'];
        if (empty($contextItems)) return null;

        usort($contextItems, function($a, $b) {
            return $b['price'] - $a['price'];
        });

        $contextParams = array();
        $contextItems = array_map(function($contextItem) use (&$contextParams) {
            try{
                $article = Shopware()->Modules()->Articles()->sGetArticleById($contextItem['articleID']);
            } catch(\Exception $e) {
            }
            if($article) {
                foreach ($this->_productRecommendations as $key => $recommendation) {
                    if(!isset($contextParams['bx_' . $key . '_' . $article['articleID']])){
                        $contextParams['bx_' . $key . '_' . $article['articleID']] = array();
                    }
                    if(isset($article[$key])){
                        foreach ($article[$key] as $rec) {
                            $contextParams['bx_' . $key . '_' . $article['articleID']][] = $rec['articleID'];
                        }
                    }
                }
            }
            return ['id' => $contextItem['articleID'] ,'price' => $contextItem['price']];
        }, $contextItems);

        $choiceId = $this->Config()->get('boxalino_cart_recommendation_name');
        $max = $this->Config()->get('boxalino_cart_recommendation_max');
        $min = $this->Config()->get('boxalino_cart_recommendation_min');
        $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $contextItems, 'basket', false, array(), false, $contextParams);
        $hitIds = $this->Helper()->getRecommendation($choiceId);
        $trackingProperties = [
            "bx_request_uuid" => $this->Helper()->getRequestUuid($choiceId),
            'bx_request_groupby' => $this->Helper()->getRequestGroupBy($choiceId)
        ];

        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        if($this->Request()->getActionName() == 'ajaxCart') {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/checkout/ajax_cart.tpl');
        } else {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/checkout/cart.tpl');
        }

        $this->View()->assign($trackingProperties);
        $this->View()->assign('sRecommendations', $this->Helper()->getLocalArticles($hitIds));
        $this->View()->assign('bxBasket', true);

        return null;
    }

    /**
     * add "add to basket" tracking
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function addToBasket(Enlight_Event_EventArgs $arguments)
    {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        if ($this->Config()->get('boxalino_tracking_enabled')) {
            $article = $arguments->getArticle();
            $price = $arguments->getPrice();
            Shopware_Plugins_Frontend_Boxalino_EventReporter::reportAddToBasket(
                $article['articledetailsID'],
                $arguments->getQuantity(),
                $price['price'],
                Shopware()->Shop()->getCurrency()
            );
        }
        return $arguments->getReturn();
    }

    /**
     * add purchase tracking
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function purchase(Enlight_Event_EventArgs $arguments) {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        if ($this->Config()->get('boxalino_tracking_enabled')) {
            $products = array();
            foreach ($arguments->getDetails() as $detail) {
                $products[] = array(
                    'product' => $detail['articleDetailId'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['priceNumeric'],
                );
            }
            Shopware_Plugins_Frontend_Boxalino_EventReporter::reportPurchase(
                $products,
                $arguments->get("orderId"),
                $arguments->getSubject()->sAmount,
                Shopware()->Shop()->getCurrency()
            );
        }
        return $arguments->getReturn();
    }

    public function getBannerInfo($arguments) {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }
        $config = $arguments->getReturn();
        $data = $this->Helper()->addBanner($config);
        return $data;
    }

    /**
     * add script if tracking enabled
     * @param string $script
     * @return void
     */
    protected function addScript($script) {
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/index.tpl');
        } else {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/index_5_3.tpl');
        }
        if ($script != null && $this->Config()->get('boxalino_tracking_enabled')) {
            $this->View()->assign('report_script', $script);
        }
        $this->View()->assign('bxForce', false);
        $this->View()->assign('bxHelper', $this->Helper());
    }

    public function getTrackingHtmlAttributes($choiceId=null)
    {
        return [
            "bx_request_uuid" => $this->Helper()->getRequestUuid($choiceId),
            "bx_request_groupby" => $this->Helper()->getRequestGroupBy($choiceId)
        ];
    }

}