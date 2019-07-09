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
            'flush',
            'check'
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
        $account = $this->Request()->getQuery('account');

        if(empty($account))
        {
            echo "Please set the account to be cleared. \n";
            return;
        }

        $exporter = Shopware()->Container()->get('boxalino_intelligence.service_exporter');
        $exporter->setAccount($account);
        $type = $this->Request()->getQuery('type');

        $exporter->clearExportTable($type);
        echo "The exporter history for account {$account} has been cleared. If the exporter will not run - it means other accounts are processing. Consult with your IT team to check the boxalino_exports table.\n";
        return;
    }

    public function checkAction()
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();
        try {
            $exporter = Shopware()->Container()->get('boxalino_intelligence.service_exporter');
            $content = $exporter->viewExportTable();
            $firstRow = true;
            foreach($content as $row)
            {
                if ($firstRow) {
                    $firstRow = false;
                    echo "\n";
                    foreach($row as $key => $field) {
                        echo  "\t" . htmlspecialchars($key) . "\t\t";
                    }
                }
                echo "\n";
                foreach($row as $key => $field) {
                    echo "\t" . htmlspecialchars($field) . "\t";
                }
                echo "\n";
            }
        } catch(\Throwable $e) {
            echo "BxIndexLog: export failed with exception: " . $e->getMessage() . "\n";
        }

        return;
    }


    private function exportData($delta = false)
    {
        $this->container->get('front')->Plugins()->ViewRenderer()->setNoRender();

        $accounts = array_filter(explode(",", $this->Request()->getQuery('account')));
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
