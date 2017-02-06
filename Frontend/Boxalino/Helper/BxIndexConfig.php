<?php

class Shopware_Plugins_Frontend_Boxalino_Helper_BxIndexConfig{

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var array
     */
    private $indexConfig = array();

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_BxIndexConfig constructor.
     */
    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->init();
    }

    /**
     * @throws Exception
     */
    private function init(){
        foreach($this->getShopIds() as $id){
            $config = $this->getConfigurationByShopId($id);
            if($config['export']){
                if($config['account'] == "") {
                    throw new \Exception(
                        "Configuration error detected: Boxalino Account Name cannot be null for any store where exporter is enabled."
                    );
                }
                $this->indexConfig[$config['account']] = $config;
            }
        }
    }

    /**
     * @param $id
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function getConfigurationByShopId($id) {

        $shop = Shopware()->Models()->find('Shopware\\Models\\Shop\\Shop', $id);
        $config = array(
            'store_id' => $id,
            'shop' => $shop,
            'db'   => $this->db,
        );
        
        $scopeConfig = new \Shopware_Components_Config($config);
        $children = $shop->getChildren();
        $languages[$shop->getId()] = substr($shop->getLocale()->toString(), 0, 2);

        foreach($children as $child){
            $languages[$child->getId()] = substr($child->getLocale()->toString(), 0, 2);
        }

        $dir = __DIR__ . '/../config.json';
        $fields = json_decode(file_get_contents($dir), true);
        $config['languages'] = $languages;
        foreach ($fields as $field){
            $name = $field['name'];
            $config[$name] = $scopeConfig->get('boxalino_' . $name);
        }
        
        return $config;
    }

    /**
     * @return array
     */
    private function getShopIds(){
        $db = $this->db;
        $sql = $db->select()
            ->from(array('s' => 's_core_shops'), array('id'))
            ->where('s.active = ?', 1)->where('s.main_id IS NULL');
        $ids = [];
        $result = $db->fetchAll($sql);

        foreach ($result as $r){
            $ids[] = $r['id'];
        }
        return $ids;
    }

    /**
     * @return array
     */
    public function getAccounts() {

        return array_keys($this->indexConfig);
    }

    /**
     * @param $account
     * @return mixed
     * @throws Exception
     */
    private function getAccountConfig($account){
        if(isset($this->indexConfig[$account])) {
            return $this->indexConfig[$account];
        }
        throw new \Exception("Account is not defined: " . $account);
    }

    /**
     * @param $account
     * @param $language
     * @return mixed
     * @throws Exception
     */
    public function getStore($account) {

        $array = $this->getAccountConfig($account);
        return $array['shop'];
    }

    /**
     * @param $account
     * @return bool
     */
    public function isCustomersExportEnabled($account) {
        $config = $this->getAccountConfig($account);
        return $config['export_customer_enable'] == 1;
    }

    /**
     * @param $account
     * @return bool
     */
    public function isTransactionsExportEnabled($account) {
        $config = $this->getAccountConfig($account);
        return $config['export_transaction_enable'] == 1;
    }

    /**
     * @param $account
     * @return mixed
     * @throws Exception
     */
    public function getAccountUsername($account) {

        $config = $this->getAccountConfig($account);
        $username = $config['username'];
        return $username != "" ? $username : $account;
    }

    /**
     * @param $account
     * @return mixed
     * @throws Exception
     */
    public function getAccountPassword($account) {

        $config = $this->getAccountConfig($account);
        $password = $config['password'];
        if($password == '') {
            throw new \Exception("Please provide a password for your boxalino account in the configuration");
        }
        return $password;
    }

    /**
     * @param $account
     * @return mixed
     * @throws Exception
     */
    public function isAccountDev($account){
        $config = $this->getAccountConfig($account);
        return $config['dev'];
    }

    /**
     * @param $account
     * @return mixed
     * @throws Exception
     */
    public function getAccountStoreId($account){
        $config = $this->getAccountConfig($account);
        return $config['store_id'];
    }

    /**
     * @param $account
     * @return mixed
     * @throws Exception
     */
    public function getAccountLanguages($account){
        $config = $this->getAccountConfig($account);
        return $config['languages'];
    }

    /**
     * @param $account
     * @return bool
     * @throws Exception
     */
    public function exportProductImages($account) {

        $config = $this->getAccountConfig($account);
        return $config['export_product_images'] == 1;
    }

    /**
     * @param $account
     * @return string
     * @throws Exception
     */
    public function getAccountExportServer($account) {

        $config = $this->getAccountConfig($account);
        $exportServer = $config['export_server'];
        return $exportServer == '' ? 'http://di1.bx-cloud.com' : $exportServer;
    }

    /**
     * @param $account
     * @return bool
     * @throws Exception
     */
    public function exportProductUrl($account) {
        
        $config = $this->getAccountConfig($account);
        return $config['export_product_url'] == 1;
    }

    /**
     * @param $account
     * @return bool
     * @throws Exception
     */
    public function publishConfigurationChanges($account) {
        $config = $this->getAccountConfig($account);
        return $config['export_publish_config'] == 1;
    }

    /**
     * @param $account
     * @param $allProperties
     * @param array $requiredProperties
     * @return array
     * @throws Exception
     */
    public function getAccountProductsProperties($account, $allProperties, $requiredProperties=array()) {

        $config = $this->getAccountConfig($account);
        $includes = explode(',', $config['export_product_include']);
        $excludes = explode(',', $config['export_product_exclude']);
        return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
    }

    /**
     * @param $account
     * @param $allProperties
     * @param array $requiredProperties
     * @return array
     * @throws Exception
     */
    public function getAccountCustomersProperties($account, $allProperties, $requiredProperties=array()) {

        $config = $this->getAccountConfig($account);
        $includes = explode(',', $config['export_customer_include']);
        $excludes = explode(',', $config['export_customer_exclude']);
        return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
    }

    /**
     * @param $account
     * @param $allProperties
     * @param array $requiredProperties
     * @return array
     * @throws Exception
     */
    public function getAccountTransactionsProperties($account, $allProperties, $requiredProperties=array()) {

        $config = $this->getAccountConfig($account);
        $includes = explode(',', $config['export_transaction_include']);
        $excludes = explode(',', $config['export_transaction_exclude']);
        return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
    }

    /**
     * @param $allProperties
     * @param $includes
     * @param $excludes
     * @param array $requiredProperties
     * @return array
     * @throws Exception
     */
    protected function getFinalProperties($allProperties, $includes, $excludes, $requiredProperties=array()) {

        foreach($includes as $k => $incl) {
            if($incl == "") {
                unset($includes[$k]);
            }
        }

        foreach($excludes as $k => $excl) {
            if($excl == "") {
                unset($excludes[$k]);
            }
        }

        if(sizeof($includes) > 0) {
            foreach($includes as $incl) {
                if(!in_array($incl, $allProperties)) {
                    throw new \Exception("requested include property $incl which is not part of all the properties provided");
                }

                if(!in_array($incl, $requiredProperties)) {
                    $requiredProperties[] = $incl;
                }
            }
            return $requiredProperties;
        }

        foreach($excludes as $excl) {
            if(!in_array($excl, $allProperties)) {
                throw new \Exception("requested exclude property $excl which is not part of all the properties provided");
            }
            if(in_array($excl, $requiredProperties)) {
                throw new \Exception("requested exclude property $excl which is part of the required properties and therefore cannot be excluded");
            }
        }

        $finalProperties = array();
        foreach($allProperties as $i => $p) {
            if(!in_array($p, $excludes)) {
                $finalProperties[$i] = $p;
            }
        }
        return $finalProperties;
    }
}