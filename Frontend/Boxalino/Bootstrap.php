<?php
class Shopware_Plugins_Frontend_Boxalino_Bootstrap
    extends Shopware_Components_Plugin_Bootstrap {

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
     */
    private $searchInterceptor;
    /**
     * @var Shopware_Plugins_Frontend_Boxalino_FrontendInterceptor
     */
    private $frontendInterceptor;

    public function __construct($name, $info = null) {
        parent::__construct($name, $info);

        $this->searchInterceptor = new Shopware_Plugins_Frontend_Boxalino_SearchInterceptor($this);
        $this->frontendInterceptor = new Shopware_Plugins_Frontend_Boxalino_FrontendInterceptor($this);
    }

    public function getSearchInterceptor() {
        return $this->searchInterceptor;
    }

    public function getCapabilities() {
        return array(
            'install' => true,
            'update' => true,
            'enable' => true
        );
    }

    public function getLabel() {
        return 'Boxalino';
    }

    public function getVersion() {
        return '1.6.20';
    }

    public function getInfo() {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'author' => 'Boxalino AG',
            'copyright' => 'Copyright © 2014, Boxalino AG',
            'description' => 'Integrates Boxalino search & recommendation into Shopware.',
            'support' => 'support@boxalino.com',
            'link' => 'http://www.boxalino.com/',
        );
    }

    /**
     * @return \Shopware\Components\Model\ModelManager
     */
    protected function getEntityManager() {
        return Shopware()->Models();
    }

    public function install() {
        try {
            $this->registerEvents();
            $this->createConfiguration();
            $this->addNarrativeAttributesOnCategory();
            $this->applyBackendViewModifications();
            $this->createDatabase();
            $this->registerCronJobs();
            $this->registerEmotions();
            $this->registerSnippets();
        } catch (Exception $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }

        return array('success' => true, 'invalidateCache' => array('backend', 'proxy','template'));
    }

    public function update($version) {
        try {
            $this->registerEvents();
            $this->createConfiguration();
            $this->addNarrativeAttributesOnCategory();
            $this->applyBackendViewModifications();
            $this->createDatabase();
            $this->registerEmotions();
            $this->registerSnippets();
        } catch (Exception $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }

        return array('success' => true, 'invalidateCache' => array('backend', 'proxy','template'));
    }

    public function uninstall() {
        try {
            $this->removeDatabase();
            $this->removeSnippets();
        } catch (Exception $e) {
            $this->logException($e, __FUNCTION__);
            return false;
        }
        return array('success' => true, 'invalidateCache' => array('frontend'));
    }

    public function addNarrativeAttributesOnCategory()
    {
        $service = $this->get('shopware_attribute.crud_service');
        $service->update('s_categories_attributes', 'narrative_choice', 'string', [
            'label' => 'Boxalino Widget Choice',
            'displayInBackend' => true,
            'position' => 400,
        ], null, true);

        $service->update('s_categories_attributes', 'narrative_additional_choice', 'string', [
            'label' => 'Boxalino Additional Choice Data (additional choices for narrative, hit count for productfinder)',
            'displayInBackend' => true,
            'position' => 410,
        ], null, true);
    }

    private function registerSnippets() {
        $dir = __DIR__ . '/snippets.json';
        $fields = json_decode(file_get_contents($dir), true);
        $shops = $this->getShops();
        foreach ($shops as $shop_id => $shop) {
            $snippetHelper = new Shopware_Plugins_Frontend_Boxalino_Helper_SnippetHelper('boxalino/intelligence', $shop_id, $shop['locale_id']);
            if (isset($fields[$shop['locale']])) {
                foreach ($fields[$shop['locale']] as $field) {
                    $key = key($field);
                    $snippetHelper->add($key, $field[$key]);
                }
            }
        }
    }

    public function removeSnippets($removeDirty = false) {
        Shopware_Plugins_Frontend_Boxalino_Helper_SnippetHelper::removeAll('boxalino/intelligence');
    }

    private function registerCronJobs() {
        $this->createCronJob(
            'BoxalinoExport',
            'BoxalinoExportCron',
            24 * 60 * 60,
            true
        );

        $this->subscribeEvent(
            'Shopware_CronJob_BoxalinoExportCron',
            'onBoxalinoExportCronJob'
        );

        $this->createCronJob(
            'BoxalinoExportDelta',
            'BoxalinoExportCronDelta',
            60 * 60,
            true
        );

        $this->subscribeEvent(
            'Shopware_CronJob_BoxalinoExportCronDelta',
            'onBoxalinoExportCronJobDelta'
        );
    }

    public function addJsFiles(Enlight_Event_EventArgs $args) {
        $jsFiles = array(
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jquery.bx_register_add_article.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jquery.search_enhancements.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoFacets.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jssor.slider-26.2.0.min.js'
        );
        return new Doctrine\Common\Collections\ArrayCollection($jsFiles);
    }

    public function addLessFiles(Enlight_Event_EventArgs $args) {
        $less = array(
            new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/cart_recommendations.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/search.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/portfolio.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/productfinder.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/blog_recommendations.less'),
                __DIR__
            )
        );
        return new Doctrine\Common\Collections\ArrayCollection($less);
    }

    public function onBoxalinoExportCronJob(Shopware_Components_Cron_CronJob $job) {
        return $this->runBoxalinoExportCronJob();
    }

    public function onBoxalinoExportCronJobDelta(Shopware_Components_Cron_CronJob $job) {
        return $this->runBoxalinoExportCronJob(true);
    }

    private function updateCronExport() {
        Shopware()->Db()->query('TRUNCATE `cron_exports`');
        Shopware()->Db()->query('INSERT INTO `cron_exports` values(NOW())');
    }

    private function canRunDelta() {
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from('cron_exports', array('export_date'))
            ->limit(1);
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            $row = $stmt->fetch();
            $dbdate = strtotime($row['export_date']);
            $wait_time = Shopware()->Config()->get('boxalino_export_cron_schedule');
            if(time() - $dbdate < ($wait_time * 60)){
                return false;
            }
        }
        return true;
    }

    private function runBoxalinoExportCronJob($delta = false) {

        if($delta && !$this->canRunDelta()) {
            Shopware()->PluginLogger()->info("BxLog: Delta Export Cron is not allowed to run yet.");
            return true;
        }
        $tmpPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $exporter = new Shopware_Plugins_Frontend_Boxalino_DataExporter($tmpPath, $delta);
        $exporter->run();
        if(!$delta) {
            $this->updateCronExport();
        }
        return true;
    }

    private function createDatabase() {
        $db = Shopware()->Db();
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier('exports') .
            ' ( ' . $db->quoteIdentifier('export_date') . ' DATETIME)'
        );
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier('cron_exports') .
            ' ( ' . $db->quoteIdentifier('export_date') . ' DATETIME)'
        );
    }

    private function removeDatabase() {
        $db = Shopware()->Db();
        $db->query(
            'DROP TABLE IF EXISTS ' . $db->quoteIdentifier('exports')
        );
        $db->query(
            'DROP TABLE IF EXISTS ' . $db->quoteIdentifier('cron_exports')
        );
    }

    private function registerEvents() {

        // search results and autocompletion results
        $this->subscribeEvent('Enlight_Controller_Action_Frontend_Search_DefaultSearch', 'onSearch', 10);
        $this->subscribeEvent('Enlight_Controller_Action_Frontend_AjaxSearch_Index', 'onAjaxSearch');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Widgets_Recommendation', 'onRecommendation');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Widgets_Emotion', 'onEmotion');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Frontend_Blog', 'onBlog');

        // service extension
        $this->subscribeEvent('Enlight_Bootstrap_AfterInitResource_shopware_storefront.', 'onAjaxSearch');

        // all frontend views to inject appropriate tracking, product and basket recommendations
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Frontend', 'onFrontend');

        // add to basket and purchase tracking
        $this->subscribeEvent('Shopware_Modules_Basket_AddArticle_FilterSql', 'onAddToBasket');
        $this->subscribeEvent('Shopware_Modules_Order_SaveOrder_ProcessDetails', 'onPurchase');

        // backend indexing menu and running indexer
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_BoxalinoExport', 'boxalinoBackendControllerExport');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_BoxalinoConfig', 'boxalinoBackendControllerConfig');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Customer', 'onBackendCustomerPostDispatch');
        $this->subscribeEvent('Theme_Compiler_Collect_Plugin_Javascript', 'addJsFiles');
        $this->subscribeEvent('Theme_Compiler_Collect_Plugin_Less', 'addLessFiles');

        // add relevance (boxalino sort option) to listing
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatchSecure_Backend_Performance', 'onBackendPerformance');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_BoxalinoPerformance', 'boxalinoBackendControllerPerformance');

        // emotion backend
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Emotion', 'onPostDispatchBackendEmotion');

        //sMarketing recommendation overwrite
        $this->subscribeEvent('sMarketing::sGetAlsoBoughtArticles::replace', 'alsoBoughtRec');
        $this->subscribeEvent('sMarketing::sGetSimilaryShownArticles::replace', 'similarRec');
        $this->subscribeEvent('Shopware_Controllers_Frontend_Listing::indexAction::replace', 'onListingHook');
        $this->subscribeEvent('Shopware_Controllers_Widgets_Listing::listingCountAction::replace', 'onAjaxListingHook');
        $this->subscribeEvent('Shopware_Controllers_Frontend_Listing::getEmotionConfiguration::replace', 'onEmotionConfiguration');
        $this->subscribeEvent('Shopware_Controllers_Frontend_Listing::manufacturerAction::replace', 'onManufacturer');
    }

    public function alsoBoughtRec(Enlight_Hook_HookArgs $arguments){
        try{
            $arguments->setReturn($this->frontendInterceptor->alsoBoughtRecommendation($arguments));
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, Shopware()->Front()->Request()->getRequestUri());
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
        return null;
    }

    public function similarRec(Enlight_Hook_HookArgs $arguments){
        try{
            $arguments->setReturn($this->frontendInterceptor->similarRecommendation($arguments));
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, Shopware()->Front()->Request()->getRequestUri());
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
        return null;
    }

    private function registerEmotions() {

        $this->registerSliderEmotion();
        $this->registerPortfolioEmotion();
        $this->registerBannerEmotion();
        $this->registerLandingPageEmotion();
        $this->registerVoucherEmotion();
        $this->registerCPOFinderEmotion();
        $this->registerNarrativeEmotion();

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Widgets_Campaign',
            'extendsEmotionTemplates'
        );
        $this->subscribeEvent(
            'Shopware_Controllers_Widgets_Emotion_AddElement',
            'convertEmotion'
        );
        $this->registerController('Frontend', 'RecommendationSlider');
        $this->registerController('Frontend', 'BxDebug');
        $this->registerController('Frontend', 'BxNotification');
        $this->registerController('Frontend', 'BxNarrative');
    }

    public function onPostDispatchBackendEmotion(Enlight_Event_EventArgs $args) {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/');
        if ($args->getRequest()->getActionName() === 'index') {
            $view->extendsTemplate('backend/boxalino_emotion/app.js');
            $view->extendsTemplate('backend/boxalino_narrative/app.js');
        }
    }

    public function getShortLocale()
    {
        $locale = Shopware()->Shop()->getLocale();
        $shortLocale = $locale->getLocale();
        $position = strpos($shortLocale, '_');
        if ($position !== false)
            $shortLocale = substr($shortLocale, 0, $position);
        return $shortLocale;
    }

    public function registerNarrativeEmotion() {
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino Narrative',
            'template' => 'boxalino_narrative',
            'description' => 'Dynamic rendering of visual elements.',
            'convertFunction' => null
        ));
        if ($component->getFields()->count() == 0) {
            $component->createComboBoxField(
                array(
                    'name' => 'render_option',
                    'fieldLabel' => 'Render Option',
                    'store' => 'Shopware.apps.BoxalinoNarrative.store.List',
                    'displayField' => 'render_option',
                    'valueField' => 'id',
                    'allowBlank' => false
                )
            );
            $component->createTextField(array(
                'name' => 'choiceId',
                'fieldLabel' => 'Choice id',
                'supportText' => 'Choice ID used for the narrative. If left empty \'narrative\' will be used.',
                'allowBlank' => false
            ));
            $component->createTextField(array(
                'name' => 'additional_choiceId',
                'fieldLabel' => 'Additional Choice id',
                'supportText' => 'Additional Choice IDs for the narrative. Write multiple Choice IDs separated by comma.',
                'allowBlank' => true
            ));
        }
    }

    /**
     * Voucher Recommendation Emotion
     */
    public function registerVoucherEmotion() {
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino Voucher',
            'template' => 'boxalino_voucher_recommendations',
            'description' => 'Recommending voucher to the user.',
            'convertFunction' => null
        ));
        if ($component->getFields()->count() == 0) {
            $component->createTextField(array(
                'name' => 'choiceId',
                'fieldLabel' => 'Choice id',
                'allowBlank' => false
            ));
            $component->createHtmlEditorField(array(
                'name' => 'template',
                'fieldLabel' => 'Template',
                'allowBlank' => true
            ));
        }
    }

    /**
     * Slider Recommendation Emotion
     */
    public function registerSliderEmotion(){
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino Slider Recommendations',
            'template' => 'boxalino_slider_recommendations',
            'description' => 'Display Boxalino product recommendations as slider.',
            'convertFunction' => null
        ));
        if ($component->getFields()->count() == 0) {
            $component->createTextField(array(
                'name' => 'choiceId',
                'fieldLabel' => 'Choice id',
                'allowBlank' => false
            ));
            $component->createNumberField(array(
                'name' => 'article_slider_max_number',
                'fieldLabel' => 'Maximum number of articles',
                'allowBlank' => false,
                'defaultValue' => 10
            ));
            $component->createTextField(array(
                'name' => 'article_slider_title',
                'fieldLabel' => 'Title',
                'supportText' => 'Title to be displayed above the slider'
            ));
            $component->createCheckboxField(array(
                'name' => 'article_slider_arrows',
                'fieldLabel' => 'Display arrows',
                'defaultValue' => 1
            ));
            $component->createCheckboxField(array(
                'name' => 'article_slider_numbers',
                'fieldLabel' => 'Display numbers',
                'defaultValue' => 0
            ));
            $component->createNumberField(array(
                'name' => 'article_slider_scrollspeed',
                'fieldLabel' => 'Scroll speed',
                'allowBlank' => false,
                'defaultValue' => 500
            ));
            $component->createHiddenField(array(
                'name' => 'article_slider_rotatespeed',
                'fieldLabel' => 'Rotation speed',
                'allowBlank' => false,
                'defaultValue' => 5000
            ));
        }
    }

    /**
     * Banner Emotion
     */
    public function registerBannerEmotion(){
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino Banner',
            'template' => 'boxalino_banner',
            'description' => 'Display Boxalino banner.',
            'convertFunction' => null
        ));
        if ($component->getFields()->count() == 0) {
            $component->createTextField(array(
                'name' => 'choiceId_banner',
                'fieldLabel' => 'Choice id for banner',
                'allowBlank' => false,
                'defaultValue' => 'banner',
                'position' => 0
            ));
            $component->createTextField(array(
                'name' => 'min_banner',
                'fieldLabel' => 'Minimum Number of Slides',
                'allowBlank' => false,
                'defaultValue' => 1,
                'position' => 1
            ));
            $component->createNumberField(array(
                'name' => 'max_banner',
                'fieldLabel' => 'Maximum number of slides',
                'allowBlank' => false,
                'defaultValue' => 10,
                'position' => 2
            ));
        }
    }

    /**
     * Portfolio Emotion
     */
    public function registerPortfolioEmotion(){
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino Portfolio Recommendations',
            'template' => 'boxalino_portfolio_recommendations',
            'description' => 'Display Boxalino product recommendations for specific customer.',
            'convertFunction' => null
        ));
        if ($component->getFields()->count() == 0) {
            $component->createTextField(array(
                'name' => 'choiceId_portfolio',
                'fieldLabel' => 'Choice id for portfolio',
                'allowBlank' => false,
                'position' => 0
            ));
            $component->createTextField(array(
                'name' => 'choiceId_re-buy',
                'fieldLabel' => 'Choice id for re-buy',
                'allowBlank' => false,
                'position' => 1
            ));
            $component->createNumberField(array(
                'name' => 'article_slider_max_number_rebuy',
                'fieldLabel' => 'Maximum number of articles for re-buy',
                'allowBlank' => false,
                'defaultValue' => 10,
                'position' => 2
            ));
            $component->createTextField(array(
                'name' => 'choiceId_re-orient',
                'fieldLabel' => 'Choice id for re-orient',
                'allowBlank' => false,
                'position' => 3
            ));
            $component->createNumberField(array(
                'name' => 'article_slider_max_number_reorient',
                'fieldLabel' => 'Maximum number of articles for re-orient',
                'allowBlank' => false,
                'defaultValue' => 10,
                'position' => 4
            ));
        }
    }

    public function registerLandingPageEmotion() {
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino LandingPage',
            'template' => 'boxalino_landingpage',
            'description' => 'Display Boxalino LandingPage.',
            'convertFunction' => null
        ));
    }

    public function registerCPOFinderEmotion() {
        $component = $this->createEmotionComponent(array(
            'name' => 'Boxalino CPO Finder',
            'template' => 'boxalino_product_finder',
            'description' => 'Boxalino CPO Finder',
            'convertFunction' => null
        ));
        if ($component->getFields()->count() == 0) {
            $component->createComboBoxField(
                array(
                    'name' => 'widget_type',
                    'fieldLabel' => 'Widget',
                    'store' => 'Shopware.apps.BoxalinoEmotion.store.List',
                    'displayField' => 'widget_type',
                    'valueField' => 'id',
                    'allowBlank' => false
                )
            );
            $component->createComboBoxField(array(
                "name" => "cpo_finder_link",
                "fieldLabel" => "Product Finder Category",
                "supportText" => "Select category to link to product finder",
                "xtype" => "emotion-components-fields-category-selection"
            ));
            $component->createNumberField(array(
                'name' => 'cpo_finder_page_size',
                'fieldLabel' => 'Hit count',
                'allowBlank' => true,
                'supportText' => 'Number of products which are shown',
                'defaultValue' => 20
            ));
            $component->createTextField(array(
                'name' => 'choice_id_productfinder',
                'fieldLabel' => 'Choice ID',
                'supportText' => 'Override Choide ID for this widget',
                'allowBlank' => true
            ));
        }
    }

    public function disableHttpCache() {
        $httpCache = $this->HttpCache();
        if($httpCache) {
            $httpCache->disableControllerCache();
        }
    }

    public function convertEmotion($args) {
        $data = $args->getReturn();

        if ($args['element']['component']['template'] == "boxalino_landingpage") {
            $this->disableHttpCache();
            $data['view'] = $this->onLandingPage($args);
            return $data;
        }

        if ($args['element']['component']['template'] == "boxalino_narrative") {
            if($data['render_option'] == 1) {
                $narrativeData = $this->onNarrative($data);
                $data = array_merge($data, $narrativeData);
            }
            return $data;
        }

        if ($args['element']['component']['template'] == "boxalino_product_finder") {
            $this->disableHttpCache();
            Shopware()->PluginLogger()->info("bootstrap HTTP_REFERER: " . json_encode($_SERVER['HTTP_REFERER']));
            $data['category_id'] = $this->getEmotionCategoryId($args['element']['emotionId']);
            $locale = substr(Shopware()->Shop()->getLocale()->toString(), 0, 2);
            $data['locale'] = $locale;
            $data = array_merge($data, $this->onCPOFinder($data));

            Shopware()->PluginLogger()->info("=============================================================");
            return $data;
        }

        if ($args['element']['component']['name'] == "Boxalino Banner") {
            $this->disableHttpCache();
            $data = $this->onBanner($args);
            return $data;
        }

        if ($args['element']['component']['name'] == "Boxalino Voucher") {
            $httpCache = $this->HttpCache();
            if($httpCache){
                $httpCache->disableControllerCache();
            }
            $data = $this->onVoucher($args);
            return $data;
        }

        if ($args['element']['component']['name'] == "Boxalino Portfolio Recommendations") {
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t1 = microtime(true);
                $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
                $helper->addNotification("convertEmotion start at: " . $t1);
            }

            $data['portfolio'] = $this->onPortfolioRecommendation($args);
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t3 = microtime(true);
            }
            $data['lang'] = $this->getShortLocale();
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t1 = (microtime(true) - $t1) * 1000 ;
                $t3 = (microtime(true) - $t3) * 1000 ;
                $helper = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::instance();
                $helper->addNotification("Post PortfolioRecommendation took: " . $t3 . "ms.");
                $helper->addNotification("Total time of Portfolio: " . $t1 . "ms.");
                $helper->callNotification(true);
            }
            return $data;
        }
        if ($args['element']['component']['name'] != "Boxalino Slider Recommendations") {
            return $data;
        }
        $emotionRepository = Shopware()->Models()->getRepository('Shopware\Models\Emotion\Emotion');
        if(version_compare(Shopware::VERSION, '5.3.0', '>=')){
            $emotionModel = $emotionRepository->findOneBy(array('id' => $args['element']['emotionId']));
            $categoryId = $emotionModel->getCategories()->first()->getId();
        } else {
            $categoryId = $args->getSubject()->getEmotion($emotionRepository)[0]['categories'][0]['id'];
        }
        $query = array(
            'controller' => 'RecommendationSlider',
            'module' => 'frontend',
            'action' => 'productStreamSliderRecommendations',
            'bxChoiceId' => $data['choiceId'],
            'bxCount' => $data['article_slider_max_number'],
            'category_id' => $categoryId
        );

        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? true : false;
        $url = Shopware()->Front()->Router()->assemble($query);
        if($secure){
            if(strpos($url, 'https:') === false){
                $url = str_replace('http:', 'https:', $url);
            }
        }else{
            if(strpos($url, 'http:') === false){
                $url = str_replace('https:', 'http:', $url);
            }
        }
        $data["ajaxFeed"] = $url;
        return $data;
    }

    public function boxalinoBackendControllerExport() {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . "/Controllers/backend/BoxalinoExport.php";
    }

    public function boxalinoBackendControllerConfig() {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . "/Controllers/backend/BoxalinoConfig.php";
    }

    public function boxalinoBackendControllerPerformance() {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . "/Controllers/backend/BoxalinoPerformance.php";

    }

    public function onNarrative($data) {
        try{
            return $this->searchInterceptor->narrative($data);
        }catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onBlog(Enlight_Event_EventArgs $arguments) {

        if($arguments->getRequest()->getActionName() == 'detail'){
            try{
                $this->searchInterceptor->blog($arguments);
            }catch (\Exception $e) {
                $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
            }
        }
    }

    public function onBackendPerformance(Enlight_Event_EventArgs $arguments){
        try {
            $controller = $arguments->getSubject();
            $view = $controller->View();
            $view->addTemplateDir($this->Path() . 'Views/');
            if ($arguments->getRequest()->getActionName() === 'load') {
                $view->extendsTemplate('backend/boxalino_performance/store/listing_sorting.js');
            }
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onEmotion(Enlight_Event_EventArgs $arguments) {
        $view = $arguments->getSubject()->View();
        $view->addTemplateDir($this->Path() . 'Views/emotion/');
        $view->extendsTemplate('frontend/plugins/boxalino/listing/product-box/box-emotion.tpl');
        $view->extendsTemplate('frontend/plugins/boxalino/detail/config_upprice.tpl');
        $view->extendsTemplate('frontend/plugins/boxalino/detail/actions.tpl');
    }

    public function onLandingPage(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->landingPage($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onVoucher(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->voucher($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onPortfolioRecommendation(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->portfolio($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onBanner(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->getBannerInfo($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onCPOFinder($data) {
        try {
            return $this->searchInterceptor->CPOFinder($data);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onRecommendation(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->intercept($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onSearch(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->search($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    protected $listingHook = true;
    public function onListingHook(Enlight_Hook_HookArgs $arguments){
        if(!Shopware()->Config()->get('boxalino_active') || !Shopware()->Config()->get('boxalino_navigation_enabled')) {
            $this->listingHook = false;
        }
        $arguments->getSubject()->executeParent(
            $arguments->getMethod(),
            $arguments->getArgs()
        );
        if($arguments->getSubject()->Response()->isRedirect()) {
            return;
        }
        try {
            $listingReturn = null;
            if($this->showListing && $this->listingHook) {
                $listingReturn = $this->searchInterceptor->listing($arguments);
            }
            if(is_null($listingReturn) && $this->listingHook) {
                $this->listingHook = false;
                $arguments->setReturn(
                    $arguments->getSubject()->executeParent(
                        $arguments->getMethod(),
                        $arguments->getArgs()
                    ));
            } else {
                $arguments->setReturn($listingReturn);
            }
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
            $this->listingHook = false;
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
    }


    protected function isLandingPage($categoryId) {
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('e_c' => 's_emotion_categories'), array())
            ->joinLeft(array('e_e' => 's_emotion_element'), 'e_c.emotion_id = e_e.emotionID', array())
            ->joinLeft(array('comp' => 's_library_component'), 'e_e.componentID = comp.id AND comp.template = \'boxalino_landingpage\'')
            ->where('e_c.category_id = ?', $categoryId);
        $result = $db->query($sql);
        return $result->rowCount() > 0;
    }

    protected $showListing = true;
    public function onEmotionConfiguration(Enlight_Hook_HookArgs $arguments) {
        if($arguments->getArgs()[1] === false) {
            $request = $arguments->getSubject()->Request();
            if($request->getParam('sPage') && $this->isLandingPage($request->getParam('sCategory'))) {
                $request->setParam('sPage', 0);
            }
            $return = $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            );
            if($this->listingHook) {
                $request = $arguments->getSubject()->Request();
                $id = $request->getParam('sCategory', null);
                $this->showListing = $return['showListing'];
                if($this->searchInterceptor->findStreamIdByCategoryId($id)) {
                    $this->showListing = true;
                }
                $return['showListing'] = false;
            }
            $arguments->setReturn($return);
        } else {
            $arguments->setReturn($arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            ));
        }
    }

    public function onManufacturer(Enlight_Hook_HookArgs $arguments) {
        if(!Shopware()->Config()->get('boxalino_active') || !Shopware()->Config()->get('boxalino_navigation_enabled')) {
            $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            );
        } else {
            try{
                $this->searchInterceptor->listing($arguments);
            }catch (\Exception $e) {
                $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                );
            }
        }

    }

    public function onAjaxListingHook(Enlight_Hook_HookArgs $arguments){
        try {
            $ajaxListingReturn = $this->searchInterceptor->listingAjax($arguments);
            if(is_null($ajaxListingReturn)) {
                $arguments->setReturn(
                    $arguments->getSubject()->executeParent(
                        $arguments->getMethod(),
                        $arguments->getArgs()
                    ));
            } else {
                $arguments->setReturn($ajaxListingReturn);
            }
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
    }

    public function onAjaxSearch(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->ajaxSearch($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onFrontend(Enlight_Event_EventArgs $arguments) {
        try {
            $this->onBasket($arguments);
            return $this->frontendInterceptor->intercept($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onBasket(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->basket($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onAddToBasket(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->addToBasket($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onPurchase(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->purchase($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    private function checkForOldConfig(){
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('c_c_e'=> 's_core_config_elements'))
            ->join(array('c_c_f' => 's_core_config_forms'), 'c_c_e.form_id = c_c_f.id')
            ->where('c_c_f.name = ?', 'Boxalino')
            ->where('c_c_e.name = ?', 'boxalino_form_username');
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            $row = $stmt->fetch();
            $sql = 'DELETE FROM s_core_config_elements WHERE s_core_config_elements.form_id = ?';
            $db->query($sql, array($row['form_id']));
        }
    }

    public function createConfiguration() {
        $this->checkForOldConfig();
        $scopeShop = Shopware\Models\Config\Element::SCOPE_SHOP;
        $scopeLocale = Shopware\Models\Config\Element::SCOPE_LOCALE;

        $dir = __DIR__ . '/config.json';
        $fields = json_decode(file_get_contents($dir), true);
        $form = $this->Form();
        foreach($fields as $i => $f) {
            $type = 'text';
            $name = 'boxalino_' . $f['name'];
            if (array_key_exists('type', $f)) {
                $type = $f['type'];
                unset($f['type']);
            }
            unset($f['name']);
            if (!array_key_exists('value', $f)) {
                $f['value'] = '';
            }
            if (!array_key_exists('scope', $f)) {
                $f['scope'] = $scopeShop;
            }
            $present = $form->getElement($name);
            if ($present) {
                $f['value'] = $present->getValue();
            }
            $f['position'] = $i;
            $form->setElement($type, $name, $f);
        }
    }

    /**
     * Called when the BackendCustomerPostDispatch Event is triggered
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendCustomerPostDispatch(Enlight_Event_EventArgs $args) {

        /**@var $view Enlight_View_Default*/
        $view = $args->getSubject()->View();

        // Add template directory
        $view->addTemplateDir($this->Path() . 'Views/');

        //if the controller action name equals "load" we have to load all application components
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/customer/model/customer_preferences/attribute.js');
            $view->extendsTemplate('backend/customer/model/customer_preferences/list.js');
            $view->extendsTemplate('backend/customer/view/list/customer_preferences/list.js');
            $view->extendsTemplate('backend/customer/view/detail/customer_preferences/window.js');
            $view->extendsTemplate('backend/boxalino_export/view/main/window.js');
            $view->extendsTemplate('backend/boxalino_config/view/main/window.js');

            //if the controller action name equals "index" we have to extend the backend customer application
            if ($args->getRequest()->getActionName() === 'index') {
                $view->extendsTemplate('backend/customer/customer_preferences_app.js');
                $view->extendsTemplate('backend/boxalino_export/boxalino_export_app.js');
                $view->extendsTemplate('backend/boxalino_config/boxalino_config_app.js');
            }
        }
    }

    /**
     * @param $exception
     */
    public function logException(\Exception $exception, $context, $uri = null) {
        Shopware()->PluginLogger()->error("BxExceptionLog: Exception on \"{$context}\" [uri: {$uri} line: {$exception->getLine()}, file: {$exception->getFile()}] with message : " . $exception->getMessage() . ', stack trace: ' . $exception->getTraceAsString());
    }

    /**
     * @throws Exception
     */
    private function applyBackendViewModifications() {
        try {
            if(is_null($this->Menu()->findOneBy(array('label' => 'Boxalino Export')))) {
                $parent = $this->Menu()->findOneBy(array('label' => 'import/export'));
                $this->createMenuItem(array('label' => 'Boxalino Export', 'class' => 'sprite-cards-stack', 'active' => 1,
                    'controller' => 'BoxalinoExport', 'action' => 'index', 'parent' => $parent));
            }
            if(is_null($this->Menu()->findOneBy(array('label' => 'Boxalino Configuration Helper')))) {
                $parent = $this->Menu()->findOneBy(array('label' => 'Grundeinstellungen'));
                $this->createMenuItem(array('label' => 'Boxalino Configuration Helper', 'class' => 'sprite-wrench-screwdriver', 'active' => 1,
                    'controller' => 'BoxalinoConfig', 'action' => 'index', 'parent' => $parent));
            }
        } catch (Exception $e) {
            Shopware()->PluginLogger()->error('can\'t create menu entry: ' . $e->getMessage());
            throw new Exception('can\'t create menu entry: ' . $e->getMessage());
        }
    }

    private function getShops() {
        $shops = array();
        $db = $this->Application()->Db();
        $select = $db->select()
            ->from(array('c_s' => 's_core_shops'))
            ->joinLeft(array('c_l' => 's_core_locales'),
                'c_l.id = c_s.locale_id',
                array('c_l.locale', 'c_s.id', 'c_s.locale_id')
            );
        $stmt = $db->query($select);
        while ($row = $stmt->fetch()) {
            $shops[$row['id']] = $row;
        }
        return $shops;
    }

    private function getEmotionCategoryId($emotionId){
        $categoryId = null;
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from(array('e_c' => 's_emotion_categories'), array('category_id'))
            ->where('e_c.emotion_id = ?', $emotionId);

        if($result = $db->fetchRow($sql)){
            $categoryId = $result['category_id'];
        } else {
            $categoryId = Shopware()->Shop()->getCategory()->getId();
        }
        return $categoryId;
    }

}
