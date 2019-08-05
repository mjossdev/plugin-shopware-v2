<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Helper_SnippetHelper
 *
 * @author Boxalino AG
 */
class Shopware_Plugins_Frontend_Boxalino_Helper_SnippetHelper
{

    protected $namespace;

    protected $shopID;

    protected $localeID;

    protected $snippets;

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_SnippetHelper constructor.
     * @param $namespace
     * @param null $shopID
     * @param null $localeID
     */
    public function __construct($namespace, $shopID = null, $localeID = null)
    {
        $this->namespace    = $namespace;
        $this->shopID       = $shopID;
        $this->localeID     = $localeID;
        $this->snippets     = $this->getAllSnippets();
    }

    /**
     * @param $name
     * @param $value
     */
    public function add($name, $value)
    {
        if (!isset($this->snippets[$name])) {
            $sql = "INSERT INTO s_core_snippets 
                (namespace, shopID, localeID, name, value)
                VALUES 
                (?, ?, ?, ?, ?)";

            Shopware()->Db()->query($sql, array($this->namespace, $this->shopID, $this->localeID, $name, $value));
        }

        return;
    }

    /**
     * @param string $namespace
     */
    public static function removeAll($namespace)
    {
        $sql = "DELETE FROM s_core_snippets 
                WHERE namespace=?";

        Shopware()->Db()->query($sql, array($namespace));
    }

    /**
     * @return array
     */
    protected function getAllSnippets()
    {
        $sql = "SELECT * FROM s_core_snippets 
                WHERE namespace=? AND shopID=? AND localeID=?";

        $result = Shopware()->Db()->query($sql, array($this->namespace, $this->shopID, $this->localeID))->fetchAll();
        $snippets = array();
        foreach ($result as $r) {
            $snippets[$r['name']] = $r;
        }
        return $snippets;
    }

}