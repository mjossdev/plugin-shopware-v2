<?php

use Shopware\Commands\ShopwareCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Shopware_Plugins_Frontend_Boxalino_Command_FullDataSync extends ShopwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('boxalino:exporter:run')
            ->setDescription('Run Boxalino Full Data Sync.')
            ->addArgument(
                'account',
                InputArgument::REQUIRED,
                'Boxalino Account name is required.'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $account = $input->getArgument('account');
        $output->writeln('<info>'.sprintf("Running Boxalino Full Data Sync for account: %s.", $account).'</info>');

        try {
            $exporter = Shopware()->Container()->get('boxalino_intelligence.service_exporter');
            $exporter->setAccount($account);
            $exporter->setDelta(false);
            $output = $exporter->run();
            $successMessages[] = $output;
        } catch (\Throwable $e) {
            $output->writeln('<info>'.sprintf("Exception: %s.", $e->getMessage()).'</info>');
        }

        $output->writeln('<info>'.sprintf("End of Boxalino Full Data Sync for account: %s.", $account).'</info>');
    }


}