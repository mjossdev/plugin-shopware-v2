<?php

class Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager{

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @var array
     */
    protected $resource = array();

    /**
     * @var array
     */
    protected $types = array('collection', 'product', 'blog');

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager constructor.
     */
    private function __construct() {
        $this->initResource();
    }

    public static function instance() {
        if (self::$instance == null)
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_Helper_BxResourceManager();
        return self::$instance;
    }

    protected function initResource() {
        foreach ($this->types as $type) {
            $this->resource[$type] = array();
        }
    }

    public function getResource($id, $type) {
        $resource = null;
        if(isset($this->resource[$type]) && isset($this->resource[$type][$id])) {
            $resource = $this->resource[$type][$id];
        }
        return $resource;
    }

    public function setResource($resource, $id, $type) {
        if(!isset($this->resource[$type])) {
            $this->resource[$type] = array();
        }
        $this->resource[$type][$id] = $resource;
    }
}