<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Category
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_BoxalinoNarrative
{

    CONST BOXALINO_NARRATIVE_TEMPLATE_OVERWRITE_PARAMETER = 'narrative_block_main_template';
    CONST BOXALINO_NARRATIVE_SERVER_TEMPLATE_DIR = "Views/emotion/";
    CONST BOXALINO_NARRATIVE_SERVER_TEMPLATE_MAIN = "frontend/plugins/boxalino/narrative/main.tpl";
    CONST BOXALINO_NARRATIVE_SERVER_SCRIPTS_MAIN = "frontend/plugins/boxalino/narrative/script.tpl";
    CONST BOXALINO_NARRATIVE_TEMPLATE_MAIN_NOREPLACE = "frontend/plugins/boxalino/narrative/listing/index.tpl";

    /**
     * @param Enlight_View_Default $view
     * @return mixed
     * @throws Exception
     */
    public function render(&$view)
    {
        if(is_array($view))
        {
            $view = array_pop($view);
        }
        try {
            $data = $view->getAssign();
            if(isset($data['sCategoryContent']) || isset($data['sBreadcrumb']))
            {
                //updating content of the category view in case it was set via narrative
                $categoryTemplateData = new Shopware_Plugins_Frontend_Boxalino_Models_Listing_Template_CategoryData($this->getRenderer()->getHelper(), $data);
                $data = $categoryTemplateData->update();
            }

            $view->addTemplateDir($this->getTemplateDirectory());
            if ($this->config->get('boxalino_navigation_sorting') == true) {
                $view->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-sorting.tpl');
            }
            $view->extendsTemplate($this->getScriptTemplate());
            $view->extendsTemplate($this->getMainTemplate());

            $view->assign($data);
            $view->assign($this->getContent());

            return $view;
        } catch (Exception $exception)
        {
            $this->container->get("pluginlogger")->error("BxNarrativeCategory: can not render content " . $exception);
        }
    }

    /**
     * Get narrative template data which is available by default
     *
     * @return []
     * @throws Exception
     */
    public function getContent()
    {
        $content = parent::getContent();
        if($this->main)
        {
            return $content;
        }

        $narratives = $this->getNarratives();
        $generic = $this->getHelper()->getContextValues($narratives[0]['parameters']);
        if(!isset($generic[self::BOXALINO_NARRATIVE_TEMPLATE_OVERWRITE_PARAMETER]))
        {
            $generic[self::BOXALINO_NARRATIVE_TEMPLATE_OVERWRITE_PARAMETER] = null;
        }

        return array_merge($generic, $content);
    }

    public function getTemplateDirectory()
    {
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . self::BOXALINO_NARRATIVE_SERVER_TEMPLATE_DIR;
    }

    public function getMainTemplate()
    {
        return self::BOXALINO_NARRATIVE_SERVER_TEMPLATE_MAIN;
    }

    public function getMainTemplateNoReplace($templateFromNarrative = null)
    {
        if(is_null($templateFromNarrative))
        {
            return self::BOXALINO_NARRATIVE_TEMPLATE_MAIN_NOREPLACE;
        }

        return $templateFromNarrative;
    }

    public function getScriptTemplate()
    {
        return self::BOXALINO_NARRATIVE_SERVER_SCRIPTS_MAIN;
    }

}