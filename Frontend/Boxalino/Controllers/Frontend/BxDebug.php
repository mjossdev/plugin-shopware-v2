<?php
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxRecommendationRequest;
class Shopware_Controllers_Frontend_BxDebug extends Enlight_Controller_Action {

    public function indexAction() {
        $this->test();
    }

    public function test() {
        $account = "shopware_test_3"; // your account name
        $password = "shopware_test_3"; // your account password
        $domain = ""; // your web-site domain (e.g.: www.abc.com)
        $isDev = true;
        $host = isset($host) ? $host : "cdn.bx-cloud.com";
        $bxClient = new BxClient($account, $password, $domain, $isDev, $host);
        $start = microtime(true);
        $language = "de";
        $choiceId = "home"; 
        $hitCount = 10;
        $bxRequest = new BxRecommendationRequest($language, $choiceId, $hitCount);
        $bxFilters = new \com\boxalino\bxclient\v1\BxFilter('products_bx_type', array('product'));
        $bxRequest->addFilter($bxFilters);
        $bxRequest->setGroupBy('products_group_id');
        $bxClient->addRequest($bxRequest);

        $bxResponse = $bxClient->getResponse();
        $end = (microtime(true) - $start) * 1000;
        $logs[] = "response in : {$end}ms";
        foreach($bxResponse->getHitIds() as $i => $id) {
			$logs[] = "$i: returned id $id";
        }

        $this->View()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/emotion/');
        $this->View()->loadTemplate('frontend/plugins/boxalino/debug/debug.tpl');
        $this->View()->assign('logs', $logs);
    }

}