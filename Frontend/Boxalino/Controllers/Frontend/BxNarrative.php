<?php
class Shopware_Controllers_Frontend_BxNarrative extends Enlight_Controller_Action
{
    /**
     * called in emotion_components/widgets/emotion/components/boxalino_narrative.tpl
     */
    public function indexAction()
    {
        $this->getEmotionNarrativeAction();
    }

    /**
     * loading template and rendering emotions via ajax
     */
    public function getEmotionNarrativeAction()
    {
        try {
            $choiceId = $this->Request()->getQuery('choice_id');
            $additional = $this->Request()->getQuery('additional');
            $narrativeLogic = new Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative($choiceId, Shopware()->Front()->Request(), true, $additional);

            $narratives = $narrativeLogic->getNarratives();
            $dependencies = $narrativeLogic->getDependencies();
            $renderer = $narrativeLogic->getRenderer();

            $templateDir = $narrativeLogic->getAjaxEmotionTemplateDirectory();
            $mainPath = $narrativeLogic->getAjaxEmotionMainTemplate();
            $this->View()->addTemplateDir($templateDir);
            $this->View()->loadTemplate($mainPath);

            $this->View()->assign('dependencies', $dependencies);
            $this->View()->assign('narrative', $narratives);
            $this->View()->assign('bxRender', $renderer);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }
}
