<?php
class Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_Emotion
    extends Shopware_Plugins_Frontend_Boxalino_Bundle_Narrative_BoxalinoNarrative
{

    CONST BOXALINO_NARRATIVE_EMOTION_TEMPLATE_DIR = "Views/emotion/";
    CONST BOXALINO_NARRATIVE_AJAX_TEMPLATE_MAIN = "frontend/plugins/boxalino/journey/main.tpl";

    /**
     * @param $view
     * @return mixed
     * @throws Exception
     */
    public function render(&$view)
    {
        $view->addTemplateDir($this->getTemplateDirectory());
        $view->loadTemplate($this->getMainTemplate());

        $view->assign($this->getContent());
        return $view;
    }

    /**
     * @return string
     */
    public function getTemplateDirectory()
    {
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . self::BOXALINO_NARRATIVE_EMOTION_TEMPLATE_DIR;
    }

    /**
     * @return string
     */
    public function getMainTemplate()
    {
        return self::BOXALINO_NARRATIVE_AJAX_TEMPLATE_MAIN;
    }

}