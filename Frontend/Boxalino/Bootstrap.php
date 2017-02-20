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
        return '1.3.0';
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
            $this->applyBackendViewModifications();
            $this->createDatabase();
            $this->registerCronJobs();
            $this->registerEmotions();
        } catch (Exception $e) {
            Shopware()->PluginLogger()->error('Plugin install error: '. $e->getMessage());
            return false;
        }
        return true;
    }

    public function update($version) {
        try {
            $this->registerEvents();
            $this->createConfiguration();
            $this->registerEmotions();
        } catch (Exception $e) {
            Shopware()->PluginLogger()->error('Plugin update error: '. $e->getMessage());
            return false;
        }
        return true;
    }

    public function uninstall() {
        try {
            $this->removeDatabase();
        } catch (Exception $e) {
            Shopware()->PluginLogger()->error('Plugin uninstall error: '. $e->getMessage());
            return false;
        }
        return array('success' => true, 'invalidateCache' => array('frontend'));
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
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jquery.search_enhancements.js'
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

    private function runBoxalinoExportCronJob($delta = false) {
        $tmpPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $exporter = new Shopware_Plugins_Frontend_Boxalino_DataExporter($tmpPath, $delta);
        $exporter->run();
        return true;
    }

    private function createDatabase() {
        $db = Shopware()->Db();
        $db->query(
            'CREATE TABLE IF NOT EXISTS ' . $db->quoteIdentifier('exports') .
            ' ( ' . $db->quoteIdentifier('export_date') . ' DATETIME)'
        );
    }

    private function removeDatabase() {
        $db = Shopware()->Db();
        $db->query(
            'DROP TABLE IF EXISTS ' . $db->quoteIdentifier('exports')
        );
    }

    private function registerEvents() {
        
        // search results and autocompletion results
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Listing', 'onListing');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Widgets_Listing', 'onAjaxListing');
        $this->subscribeEvent('Enlight_Controller_Action_Frontend_Search_DefaultSearch', 'onSearch');
        $this->subscribeEvent('Enlight_Controller_Action_Frontend_AjaxSearch_Index', 'onAjaxSearch');

        // all frontend views to inject appropriate tracking, product and basket recommendations
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend', 'onFrontend');

        // add to basket and purchase tracking
        $this->subscribeEvent('Shopware_Modules_Basket_AddArticle_FilterSql', 'onAddToBasket');
        $this->subscribeEvent('Shopware_Modules_Order_SaveOrder_ProcessDetails', 'onPurchase');

        // backend indexing menu and running indexer
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_BoxalinoExport', 'boxalinoBackendControllerExport');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Customer', 'onBackendCustomerPostDispatch');
        $this->subscribeEvent('Theme_Compiler_Collect_Plugin_Javascript', 'addJsFiles');
        $this->subscribeEvent('Theme_Compiler_Collect_Plugin_Less', 'addLessFiles');
    }
    
    private function registerEmotions() {
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
        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatchSecure_Widgets_Campaign',
            'extendsEmotionTemplates'
        );
        $this->subscribeEvent(
            'Shopware_Controllers_Widgets_Emotion_AddElement',
            'convertRecommendationSlider'
        );
        $this->registerController('Frontend', 'RecommendationSlider');
        $this->registerController('Frontend', 'BxDebug');
    }
    
    public function convertRecommendationSlider($args) {
        $data = $args->getReturn();
        if ($args['element']['component']['name'] != "Boxalino Slider Recommendations") {
            return $data;
        }
        $emotionRepository = Shopware()->Models()->getRepository('Shopware\Models\Emotion\Emotion');
        $categoryId = $args->getSubject()->getEmotion($emotionRepository)[0]['categories'][0]['id'];
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
        Shopware()->PluginLogger()->info("component match:: " . var_export($args['element']['component'], true));
        Shopware()->PluginLogger()->info("result data:: " . var_export($data, true));
        return $data;
    }

    public function boxalinoBackendControllerExport() {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . "/Controllers/backend/BoxalinoExport.php";
    }

    public function onSearch(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->search($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onListing(Enlight_Event_EventArgs $arguments){
        try {
            return $this->searchInterceptor->listing($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }
    
    public function onAjaxListing(Enlight_Event_EventArgs $arguments){
        try {
            return $this->searchInterceptor->listingAjax($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onAjaxSearch(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->ajaxSearch($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onFrontend(Enlight_Event_EventArgs $arguments) {
        try {
            $this->onBasket($arguments);
            return $this->frontendInterceptor->intercept($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onBasket(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->basket($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onAddToBasket(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->addToBasket($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function onPurchase(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->purchase($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function createConfiguration() {
        $scopeShop = Shopware\Models\Config\Element::SCOPE_SHOP;
        $scopeLocale = Shopware\Models\Config\Element::SCOPE_LOCALE;

        $dir = __DIR__ . '/config.json';
        $fields = json_decode(file_get_contents($dir), true);
        $form = $this->Form();
        foreach($fields as $f) {
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
            if ($present && array_key_exists('value', $present)) {
                $f['value'] = $present['value'];
            }
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
        $args->getSubject()->View()->addTemplateDir($this->Path() . 'Views/');

        //if the controller action name equals "load" we have to load all application components
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/customer/model/customer_preferences/attribute.js');
            $view->extendsTemplate('backend/customer/model/customer_preferences/list.js');
            $view->extendsTemplate('backend/customer/view/list/customer_preferences/list.js');
            $view->extendsTemplate('backend/customer/view/detail/customer_preferences/window.js');
            $view->extendsTemplate('backend/boxalino_export/view/main/window.js');

            //if the controller action name equals "index" we have to extend the backend customer application
            if ($args->getRequest()->getActionName() === 'index') {
                $view->extendsTemplate('backend/customer/customer_preferences_app.js');
                $view->extendsTemplate('backend/boxalino_export/boxalino_export_app.js');
            }
        }
    }

    /**
     * @param $exception
     */
    private function logException($exception, $context) {
        Shopware()->PluginLogger()->error("Boxalino Log [ERROR]: Exception on \"{$context}\" with message : " . $exception->getMessage());
    }

    /**
     * @throws Exception
     */
    private function applyBackendViewModifications() {
        try {
            $parent = $this->Menu()->findOneBy(array('label' => 'import/export'));
            $this->createMenuItem(array('label' => 'Boxalino Export', 'class' => 'sprite-cards-stack', 'active' => 1,
                'controller' => 'BoxalinoExport', 'action' => 'index', 'parent' => $parent));
        } catch (Exception $e) {
            Shopware()->PluginLogger()->error('can\'t create menu entry: ' . $e->getMessage());
            throw new Exception('can\'t create menu entry: ' . $e->getMessage());
        }
    }

}
