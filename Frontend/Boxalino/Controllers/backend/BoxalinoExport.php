<?php
use Shopware\Components\CSRFWhitelistAware;
class Shopware_Controllers_Backend_BoxalinoExport extends Shopware_Controllers_Backend_ExtJs
    implements CSRFWhitelistAware
{

    public function getWhitelistedCSRFActions()
    {
        return [
            'full',
            'delta',
            'index',
            'flush'
        ];
    }

    /**
     * index action is called if no other action is triggered
     *
     * @return void
     */
    public function indexAction()
    {
        $this->View()->loadTemplate('backend/boxalino_export/app.js');
        $this->View()->assign('title', 'Boxalino-Export');
    }

    public function fullAction() {
        $this->exportData();
    }

    public function deltaAction() {
        $this->exportData(true);
    }

    public function flushAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        $tmpPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $exporter = new Shopware_Plugins_Frontend_Boxalino_DataExporter($tmpPath, false);
        if ($exporter->canStartExport())
        {
            echo "The export can be run.\n";
            return;
        }

        $exporter->clearExportTable();
        if ($exporter->canStartExport())
        {
            echo "The exporter table has been cleared. Run the process again.\n";
            return;
        }
    }

    private function exportData($delta = false)
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        $accounts = array_filter(explode(",", $this->Request()->getQuery('account')));
        $tmpPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $config = new Shopware_Plugins_Frontend_Boxalino_Helper_BxIndexConfig();

        if(empty($accounts))
        {
            echo "BxIndexLog: Exporting for accounts: " . implode(', ', $config->getAccounts()) . "\n";
        }
        foreach ($config->getAccounts() as $account) {
            if(!empty($accounts) && !in_array($account, $accounts))
            {
                echo "BxIndexLog: {$account} is skipped; \n";
                continue;
            }

            try{
                $exporter = Shopware()->Container()->get('boxalino_intelligence.service_exporter');
                $exporter->setAccount($account);
                $exporter->setDelta($delta);
                $output = $exporter->run();
                echo $output . "\n";
            } catch(\Throwable $e) {
                echo "BxIndexLog: {$account} export failed with exception: " . $e->getMessage() . "\n";
                continue;
            }
        }
    }

}
