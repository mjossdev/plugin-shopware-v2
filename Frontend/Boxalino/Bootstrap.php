<?php
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bootstrap
 */
class Shopware_Plugins_Frontend_Boxalino_Bootstrap
    extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
     */
    protected $searchInterceptor;
    /**
     * @var Shopware_Plugins_Frontend_Boxalino_FrontendInterceptor
     */
    protected $frontendInterceptor;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_DataExporter
     */
    protected $dataExporter;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_NarrativeRendererInterface
     */
    protected $narrativeRenderer;

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
        return '5.7.5';
    }

    public function getInfo() {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel(),
            'author' => 'Boxalino AG',
            'copyright' => 'Copyright © 2021, Boxalino AG',
            'description' => 'Integrates Boxalino Services in a Shopware 5 project.',
            'support' => 'support@boxalino.com',
            'link' => 'https://www.boxalino.com/',
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
            $this->addCustomPluginEvents();
            $this->createConfiguration();
            $this->addNarrativeAttributesOnCategory();
            $this->addNarrativeAttributesOnDetail();
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
            $this->addCustomPluginEvents();
            $this->createConfiguration();
            $this->addNarrativeAttributesOnCategory();
            $this->addNarrativeAttributesOnDetail();
            $this->applyBackendViewModifications();
            $this->removeDatabase($version);
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
            $this->removeDatabase($this->getVersion());
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
        $service->update('s_categories_attributes', 'narrative_choice', TypeMapping::TYPE_STRING, [
            'label' => 'Boxalino Widget Choice',
            'displayInBackend' => true,
            'position' => 400,
        ], null, true);

        $service->update('s_categories_attributes', 'narrative_additional_choice', TypeMapping::TYPE_STRING, [
            'label' => 'Boxalino Additional Choice Data',
            'supportText' => 'Additional choices for narrative, divided by comma; OR hit count for productfinder',
            'displayInBackend' => true,
            'position' => 410,
        ], null, true);

        $service->update('s_categories_attributes', 'narrative_replace_main', TypeMapping::TYPE_BOOLEAN, [
            'label' => 'Boxalino Narrative Replace Main',
            'supportText' => 'If checked - the entire page content is replaced by the narrative; if unchecked - the narrative will be used to extend the default view.',
            'displayInBackend' => true,
            'position' => 420,
        ], null, true);
    }

    public function addNarrativeAttributesOnDetail()
    {
        $service = $this->get('shopware_attribute.crud_service');
        $service->update('s_articles_attributes', 'narrative_choice', TypeMapping::TYPE_STRING, [
            'label' => 'Boxalino Widget Choice',
            'displayInBackend' => true,
            'position' => 400,
            'custom' => true,
        ], null, true);

        $service->update('s_articles_attributes', 'narrative_additional_choice', TypeMapping::TYPE_STRING, [
            'label' => 'Boxalino Additional Choices',
            'supportText' => 'Additional choices for narrative, divided by comma',
            'displayInBackend' => true,
            'position' => 410,
            'custom' => true,
        ], null, true);

        $service->update('s_articles_attributes', 'narrative_replace_main', TypeMapping::TYPE_BOOLEAN, [
            'label' => 'Boxalino Narrative Replace Main',
            'supportText' => 'If checked - the entire page content is replaced by the narrative; if unchecked - the narrative will be used to extend the default view.',
            'displayInBackend' => true,
            'position' => 420,
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
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoApiHelper.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoApiAcRenderer.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoApiAc.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoFinder.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoFinderFn.js',
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
        Shopware()->Db()->query('TRUNCATE `boxalino_cronexports`');
        Shopware()->Db()->query('INSERT INTO `boxalino_cronexports` values(NOW())');
    }

    public function addCustomPluginEvents()
    {
        $this->subscribeEvent("Enlight_Bootstrap_InitResource_boxalino_intelligence.service_exporter", "getExporterService");
        $this->subscribeEvent("Enlight_Bootstrap_InitResource_boxalino_intelligence.service_narrative_renderer", "getNarrativeRendererService");
        $this->subscribeEvent('Shopware_Console_Add_Command','onAddConsoleCommand');
    }

    public function getExporterService()
    {
        $this->dataExporter = new Shopware_Plugins_Frontend_Boxalino_DataExporter();
        Shopware()->Container()->get("events")->notify(
            'Enlight_Bootstrap_BeforeSetResource_boxalino_intelligence.service_exporter', ['subject' => $this]
        );
        Shopware()->Container()->set("boxalino_intelligence.service_exporter", $this->getDataExporter());

        return $this->getDataExporter();
    }

    public function getNarrativeRendererService()
    {
        $this->narrativeRenderer = new Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Renderer();
        Shopware()->Container()->get("events")->notify(
            'Enlight_Bootstrap_BeforeSetResource_boxalino_intelligence.service_narrative_renderer', ['subject' => $this]
        );
        Shopware()->Container()->set("boxalino_intelligence.service_narrative_renderer", $this->getNarrativeRenderer());

        return $this->getNarrativeRenderer();
    }

    public function onAddConsoleCommand()
    {
        return new ArrayCollection(
            [
                new Shopware_Plugins_Frontend_Boxalino_Command_FullDataSync()
            ]
        );
    }

    /**
     * a delta can only run if it`s been at least 1h after a full data sync
     * @return bool
     */
    private function canRunDelta() {
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from('boxalino_cronexports', array('export_date'))
            ->order("export_date DESC")
            ->limit(1);
        $lastExec = $db->fetchOne($sql);
        $dbdate = strtotime($lastExec);
        $wait_time = Shopware()->Config()->get('boxalino_export_cron_schedule');
        if(time() - $dbdate < ($wait_time * 60)){
            return false;
        }
        return true;
    }

    private function runBoxalinoExportCronJob($delta = false)
    {
        if ($delta && !$this->canRunDelta()) {
            Shopware()->Container()->get('pluginlogger')->info("BxLog: Delta Export Cron is not allowed to run yet.");
            return true;
        }

        $tmpPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $config = new Shopware_Plugins_Frontend_Boxalino_Helper_BxIndexConfig();
        $errorMessages = array();
        $successMessages = array();
        foreach ($config->getAccounts() as $account) {
            try {
                $exporter = Shopware()->Container()->get('boxalino_intelligence.service_exporter');
                $exporter->setAccount($account);
                $exporter->setDelta($delta);
                $output = $exporter->run();
                $successMessages[] = $output;
            } catch (\Throwable $e) {
                $errorMessages[] = $e->getMessage();
                continue;
            }
        }

        if (!$delta) {
            $this->updateCronExport();
        }

        echo implode("\n", $successMessages);

        if (empty($errorMessages)) {
            return true;
        }

        throw new \Exception("Boxalino Export failed with messages: %s", implode(",", $errorMessages));
    }

    /**
     * Creates boxalino_exports, boxalino_cronexports
     */
    protected function createDatabase()
    {
        $db = Shopware()->Db();
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier("boxalino_exports")
            . '('
            . $db->quoteIdentifier('account') . ' VARCHAR(128) NOT NULL, '
            . $db->quoteIdentifier('type') . ' VARCHAR(128) NOT NULL, '
            . $db->quoteIdentifier('export_date') . ' DATETIME, '
            . $db->quoteIdentifier('status') . ' VARCHAR(128) NOT NULL '
            .')'
        );

        $db->query('CREATE UNIQUE INDEX boxalino_exports_account_type_indx ON boxalino_exports (account, type);');

        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier("boxalino_cronexports") .
            ' ( ' . $db->quoteIdentifier('export_date') . ' DATETIME)'
        );
    }

    private function removeDatabase($version)
    {
        $tableNames = ["boxalino_exports", "boxalino_cronexports"];
        if(version_compare($version, '1.6.23', '<='))
        {
            $tableNames = ["exports", "cronexports"];
        }

        $db = Shopware()->Db();
        if(version_compare($version, '1.6.29', '>'))
        {
            $db->query('DROP INDEX boxalino_exports_account_type_indx ON boxalino_exports;');
        }

        foreach($tableNames as $table)
        {
            $db->query('DROP TABLE IF EXISTS ' . $db->quoteIdentifier($table));
        }

        $db->query('DELETE FROM s_crontab WHERE action="BoxalinoExportCron";');
        $db->query('DELETE FROM s_crontab WHERE action="BoxalinoExportCronDelta";');
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
            $component->createTextField(array(
                'name' => 'slider_filters',
                'fieldLabel' => 'Additional Slider Filters (if needed)',
                'supportText' => 'Divide values comma, fields by semicolon: field1 - value1, value2; field2 - value1, value2;',
                'allowBlank' => true
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
                $narrativeData = $this->onNarrativeEmotion($data);
                $data = array_merge($data, $narrativeData);
            }
            return $data;
        }

        if ($args['element']['component']['template'] == "boxalino_product_finder") {
            $this->disableHttpCache();
            Shopware()->Container()->get('pluginlogger')->info("bootstrap HTTP_REFERER: " . json_encode($_SERVER['HTTP_REFERER']));
            $data['category_id'] = $this->getEmotionCategoryId($args['element']['emotionId']);
            $locale = substr(Shopware()->Shop()->getLocale()->toString(), 0, 2);
            $data['locale'] = $locale;
            $data = array_merge($data, $this->onCPOFinder($data));

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
            $data['portfolio'] = $this->onPortfolioRecommendation($args);
            $data['lang'] = $this->getShortLocale();
            return $data;
        }
        if ($args['element']['component']['name'] != "Boxalino Slider Recommendations") {
            return $data;
        }
        $emotionRepository = Shopware()->Models()->getRepository('Shopware\Models\Emotion\Emotion');
        $emotionModel = $emotionRepository->findOneBy(array('id' => $args['element']['emotionId']));
        if(isset($data['slider_filters']) && !empty($data['slider_filters']))
        {
            $filterFields = $this->checkExtraRulesOnFiltering($data['slider_filters']);
        } else {
            $filterFields = ["category_id"=>$emotionModel->getCategories()->first()->getId()];
        }

        $query = array_merge(array(
            'controller' => 'RecommendationSlider',
            'module' => 'frontend',
            'action' => 'productStreamSliderRecommendations',
            'bxChoiceId' => $data['choiceId'],
            'bxCount' => $data['article_slider_max_number']
        ), $filterFields);
        $data["ajaxFeed"] = $this->getUrl(Shopware()->Front()->Router()->assemble($query));

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

    public function onNarrativeEmotion($data) {
        try{
            return $this->searchInterceptor->onNarrativeEmotion($data);
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
            return $this->searchInterceptor->onEmotionFinder($data);
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
            if ($this->Config()->get('boxalino_search_enabled')) {
                return $this->searchInterceptor->search($arguments);
            }
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
        $sql = $db->select()->from(['e_c' => 's_emotion_categories'], [])
            ->joinInner(['e' => 's_emotion'], 'e_c.emotion_id = e.id', [])
            ->joinInner(['e_e' => 's_emotion_element'], 'e.id = e_e.emotionID', [])
            ->joinInner(['comp' => 's_library_component'], 'e_e.componentID = comp.id')
            ->where('e_c.category_id = ?', $categoryId)
            ->where('e.active')
            ->where("comp.template = 'boxalino_landingpage'")
            ->limit(1);
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
                if($this->searchInterceptor->BxData()->findStreamIdByCategoryId($id)) {
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
            return $this->frontendInterceptor->intercept($arguments);
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
        Shopware()->Container()->get('pluginlogger')->error("BxExceptionLog: Exception on \"{$context}\" [uri: {$uri} line: {$exception->getLine()}, file: {$exception->getFile()}] with message : " . $exception->getMessage() . ', stack trace: ' . $exception->getTraceAsString());
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
            Shopware()->Container()->get('pluginlogger')->error('can\'t create menu entry: ' . $e->getMessage());
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

    public function getUrl($url)
    {
        $secure = $this->getServerIsSecure();
        if($secure){
            if(strpos($url, 'https:') === false){
                return  str_replace('http:', 'https:', $url);
            }

            return $url;
        }

        if(strpos($url, 'http:') === false){
            return str_replace('https:', 'http:', $url);
        }

        return $url;
    }

    protected function getServerIsSecure()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) && $_SERVER['HTTP_X_FORWARDED_PROTOCOL'] == 'https')
        {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
        {
            return true;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' )
        {
            return true;
        }

        return false;
    }

    /**
     * Used for updating emotion elements
     *
     * @deprecated
     * @param $component
     * @param $fieldName
     * @return bool
     */
    protected function checkFieldExistsForEmotion($component, $fieldName)
    {
        $fields = $component->getFields()->getSnapshot();
        foreach($fields as $field)
        {
            if ($field->getName()==$fieldName)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Complex rules on emotion construct
     *
     * @param $filters
     * @return array
     */
    protected function checkExtraRulesOnFiltering($filters)
    {
        $filterRules = explode(';', $filters);
        $filterFields = [];
        foreach ($filterRules as $rule) {
            if(empty($rule)){continue;}
            $fieldRuleMapping = explode(':', $rule);
            $filterFields[$fieldRuleMapping[0]] = $fieldRuleMapping[1];
        }

        return $filterFields;
    }

    public function getDataExporter()
    {
        return $this->dataExporter;
    }

    public function setDataExporter($dataExporter)
    {
        $this->dataExporter  = $dataExporter;
        return $this;
    }

    public function getNarrativeRenderer()
    {
        return $this->narrativeRenderer;
    }

    public function setNarrativeRenderer($narrativeRenderer)
    {
        $this->narrativeRenderer  = $narrativeRenderer;
        return $this;
    }

}