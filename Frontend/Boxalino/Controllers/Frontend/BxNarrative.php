<?php
/**
 * Class Shopware_Controllers_Frontend_BxNarrative
 *
 * Narratives are constructs designed to provide dynamic content from the boxalino servers
 * The content is provided by the engines and it is rendered using the templates that they are asking for
 * A narrative content can be anything: product finder, profiler, notifications, category listing, custom elements based on context/etc
 *
 *
 * Notifications have been designed with the purpose of establishing a context-based communication with the customer
 * For non-registered users, the notifications appear as global; For registered users - they appear as badges
 */
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
            $narrativeLogic = new Shopware_Plugins_Frontend_Boxalino_Models_Narrative_Narrative($choiceId, Shopware()->Front()->Request(), true, $additional, true);

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
