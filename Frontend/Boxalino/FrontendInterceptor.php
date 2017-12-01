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
                            }
                        }
                    }
                    $this->View()->assign('sArticle', $sArticle);
                }
                if ($this->Config()->get('boxalino_detail_blog_recommendation')) {
                    $choiceId = $this->Config()->get('boxalino_detail_blog_recommendation_name');
                    $min = $this->Config()->get('boxalino_detail_blog_recommendation_min');
                    $max = $this->Config()->get('boxalino_detail_blog_recommendation_max');
                    $id = trim(strip_tags(htmlspecialchars_decode(stripslashes($this->Request()->sArticle))));
                    $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $id, 'product', false, array(), true);
                    $blogIds = $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $id, 'product', true, array(), true);
                    foreach ($blogIds as $index => $id) {
                        $blogIds[$index] = str_replace('blog_', '', $id);
                    }
                    $this->View()->extendsTemplate('frontend/plugins/boxalino/detail/content.tpl');
                    $this->View()->extendsTemplate('frontend/plugins/boxalino/_includes/product_slider_item.tpl');
                    $blogArticles = $this->Helper()->getBlogs($blogIds);
                    $blogArticles = $this->enhanceBlogArticles($blogArticles);
                    $this->View()->assign('sBlogArticles', $blogArticles);
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
                $action = $this->Request()->getParam('action');
                if ($action == 'viewed' || $action == 'bought') {
                    $configOption = $action == 'viewed' ? $this->_productRecommendationsGeneric['sCrossSimilarShown'] :
                        $this->_productRecommendationsGeneric['sCrossBoughtToo'];
                    if ($this->Config()->get("{$configOption}_enabled")) {
                        $hitIds = $this->Helper()->getRecommendation($this->Config()->get("{$configOption}_name"));
                        $this->View()->assign("{$action}Articles", $this->Helper()->getLocalArticles($hitIds));
                    }
                } else {
                    return null;
                }
                break;
            case 'checkout':
            case 'account':
                if ($_SESSION['Shopware']['sUserId'] != null) {
                    $script = Shopware_Plugins_Frontend_Boxalino_EventReporter::reportLogin($_SESSION['Shopware']['sUserId']);
                }
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

    private function enhanceBlogArticles($blogArticles) {
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);

        $context = $this->Bootstrap()->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->Bootstrap()->get('shopware_storefront.media_service')->getList($mediaIds, $context);


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
            $media = $this->get('legacy_struct_converter')->convertMediaStruct($media);

            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }

    /**
     * @return mixed|string
     */
    protected function getSearchTerm() {
        $term = $this->Request()->get('sSearch', '');

        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));

        // we have to strip the / otherwise broken urls would be created e.g. wrong pager urls
        $term = str_replace('/', '', $term);

        return $term;
    }
    
    /**
     * basket recommendations
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function basket(Enlight_Event_EventArgs $arguments) {

        $this->init($arguments);
        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_cart_recommendation_enabled')) {
            return null;
        }

        if($this->Request()->getActionName() != 'ajaxCart'){
            return null;
        }

        $choiceId = $this->Config()->get('boxalino_cart_recommendation_name');
        $basket = $this->Helper()->getBasket($arguments);
        $contextItems = $basket['content'];
        if (empty($contextItems)) return null;
        
        usort($contextItems, function($a, $b) {
            return $b['price'] - $a['price'];
        });
        $contextItems = array_map(function($contextItem) {
            return ['id' => $contextItem['articleID'] ,'price' => $contextItem['price']];
        }, $contextItems);
        $max = $this->Config()->get('boxalino_cart_recommendation_max');
        $min = $this->Config()->get('boxalino_cart_recommendation_min');
        $this->Helper()->getRecommendation($choiceId, $max, $min, 0, $contextItems, 'basket', false);
        $hitIds = $this->Helper()->getRecommendation($choiceId);
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/checkout/ajax_cart.tpl');
        $this->View()->assign('sRecommendations', $this->Helper()->getLocalArticles($hitIds));
        return null;
    }

    /**
     * add "add to basket" tracking
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function addToBasket(Enlight_Event_EventArgs $arguments) {

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
                $arguments->getSubject()->sOrderNumber,
                $arguments->getSubject()->sAmount,
                Shopware()->Shop()->getCurrency()
            );
        }
        return $arguments->getReturn();
    }

    public function getBannerInfo() {
        if (!$this->Config()->get('boxalino_active')) {
            return null;
        }

        $data = $this->Helper()->addBanner();

        return $data;
    }

    /**
     * add script if tracking enabled
     * @param string $script
     * @return void
     */
    protected function addScript($script) {
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/index.tpl');
        if ($script != null && $this->Config()->get('boxalino_tracking_enabled')) {
            $this->View()->assign('report_script', $script);
        }
        $force = false;
        if($_REQUEST['dev_bx_debug'] == 'true') {
            $force = true;
        }
        $this->View()->assign('bxForce', $force);
        $this->View()->assign('bxHelper', $this->Helper());
    }
    
}