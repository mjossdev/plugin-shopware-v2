<?php


class Shopware_Plugins_Frontend_Boxalino_EventReporter
{
    CONST BXL_INTELLIGENCE_STAGE_SCRIPT="//r-st.bx-cloud.com/static/ba.min.js";
    CONST BXL_INTELLIGENCE_PROD_SCRIPT="//track.bx-cloud.com/static/ba.min.js";
    CONST BXL_INTELLIGENCE_SCRIPT = "//cdn.bx-cloud.com/frontend/rc/js/ba.min.js";

    private static $configPriceFilter = array(
        1 => array("from" => 0, "to" => 5),
        2 => array("from" => 5, "to" => 10),
        3 => array("from" => 10, "to" => 20),
        4 => array("from" => 20, "to" => 50),
        5 => array("from" => 50, "to" => 100),
        6 => array("from" => 100, "to" => 300),
        7 => array("from" => 300, "to" => 600),
        8 => array("from" => 600, "to" => 1000),
        9 => array("from" => 1000, "to" => 1500),
        10 => array("from" => 1500, "to" => 2500),
        11 => array("from" => 2500, "to" => 3500),
        12 => array("from" => 3500, "to" => 5000)
    );
    private static function buildScript($pushes = null)
    {
        $account = Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper::getAccount();
        $scriptFile = self::getBaScriptServerPath();
        return <<<SCRIPT
            <script type="text/javascript">
                var _bxq = _bxq || [];
                _bxq.push(['setAccount', '$account']);
                _bxq.push(['trackPageView']);
                $pushes

                (function(){
                    var s = document.createElement('script');

                    s.async = 1;
                    s.src = '$scriptFile';
                    document.getElementsByTagName('head')[0].appendChild(s);
                 })();
            </script>
SCRIPT;
    }

    /**
     * getting the upgraded script
     * @return string
     */
    private static function getBaScriptServerPath()
    {
        $apiKey = Shopware()->Config()->get('boxalino_api_key');
        $apiSecret = Shopware()->Config()->get('boxalino_api_secret');
        if(empty($apiKey) || empty($apiSecret))
        {
            return self::BXL_INTELLIGENCE_SCRIPT;
        }
        $isDev = Shopware()->Config()->get('boxalino_dev');
        if($isDev)
        {
            return self::BXL_INTELLIGENCE_STAGE_SCRIPT;
        }

        return self::BXL_INTELLIGENCE_PROD_SCRIPT;
    }

    public static function reportPageView()
    {
        return self::buildScript();
    }

    public static function reportSearch($request)
    {
        $logTerm = addslashes(trim(stripslashes(html_entity_decode($request->sSearch))));
        $filters = json_encode(self::getFilters($request->getParams()));
        $script = <<<SCRIPT
                _bxq.push(['trackSearch', '$logTerm', $filters]);
SCRIPT;
        return self::buildScript($script);
    }

    public static function reportProductView($product)
    {
        $script = <<<SCRIPT
                _bxq.push(['trackProductView', '$product']);
SCRIPT;
        return self::buildScript($script);
    }

    public static function reportCategoryView($categoryId)
    {
        $script = <<<SCRIPT
                _bxq.push(['trackCategoryView', '$categoryId']);
SCRIPT;
        return self::buildScript($script);
    }

    public static function reportAddToBasket($product, $count, $price, $currency)
    {
        $event = new Shopware_Plugins_Frontend_Boxalino_Event(
            'addToBasket',
            array(
                'id' => $product,
                'q'  => $count,
                'p'  => $price,
                'c'  => $currency,
            )
        );
        return $event->track();
    }

    /**
     * Tracking User ID server-side
     *
     * @param $userId
     * @return bool|string
     */
    public static function reportTrackLogin($userId)
    {
        $event = new Shopware_Plugins_Frontend_Boxalino_Event(
            'login', ['id'=>$userId]
        );
        return $event->track();
    }

    /**
     * @param $products array example:
     *      <code>
     *          array(
     *              array('product' => 'PRODUCTID1', 'quantity' => 1, 'price' => 59.90),
     *              array('product' => 'PRODUCTID2', 'quantity' => 2, 'price' => 10.0)
     *          )
     *      </code>
     * @param $orderId string
     * @param $totalPrice number
     * @param $currency string
     */
    public static function reportPurchase($products, $orderId, $totalPrice, $currency)
    {
        $productsCount = count($products);
        $params = array(
            't'  => $totalPrice,
            'c'  => $currency,
            'n'  => $productsCount,
            'orderId' => $orderId,
        );
        for ($i = 0; $i < $productsCount; ++$i) {
            $params['id' . $i] = $products[$i]['product'];
            $params['q' . $i] = $products[$i]['quantity'];
            $params['p' . $i] = $products[$i]['price'];
        }

        $event = new Shopware_Plugins_Frontend_Boxalino_Event(
            'purchase', $params
        );
        return $event->track();
    }

    private static function prepareFilters($params)
    {
        $filtersTypes = array();
        foreach($params as $key => $param) {
            if(strpos($key, 'sFilter') !== false) {
                $type = explode('_', $key);
                $filtersTypes[$type[1]] = $param;
            }
        }
        return $filtersTypes;
    }

    private static function getFilters($params)
    {
        $filters = new stdClass();
        $filtersTypes = self::prepareFilters($params);
        if(isset($filtersTypes['price'])) {
            $ranges = self::returnProperPriceRange($filtersTypes['price']);
            if($ranges[0] != '') {
                $filters->filter_from_incl_price = $ranges[0];
            }

            if($ranges[1] != '') {
                $filters->filter_to_incl_price = $ranges[1];
            }
            unset($filtersTypes['price']);
        }
        if(isset($filtersTypes['category'])) {
            $filters->filter_hc_category = self::returnProperCategoryTree($filtersTypes['category']);
            //$this->getManager()->getRepository('Shopware\Models\Category\Category');
            unset($filtersTypes['category']);
        }
        if(isset($filtersTypes['supplier'])) {
            $filters->filter_supplier = self::returnSupplierValue($filtersTypes['supplier']);
            unset($filtersTypes['supplier']);
        }
        if(count($filtersTypes) > 0) {
//            foreach($filtersTypes as $key => $param) {
//                $paramName = 'filter_' . $key;
//                $filters->$paramName = self::returnfilterValue($key, $param);
//            }
        }
        return $filters;
    }

    private static function returnProperPriceRange($priceOption)
    {
        $range = self::$configPriceFilter[$priceOption];
        if($priceOption <= 1) {
            return array('', $range['to']);
        } else if ($priceOption >= 12) {
            return array($range['from'], '');
        } else {
            return array($range['from'], $range['to']);
        }
    }

    private static function returnProperCategoryTree($categoryOption)
    {
        $categories = '';
        $tree = Shopware()->Models()->getRepository('Shopware\Models\Category\Category')->getPathById($categoryOption);
        foreach($tree as $category) {
            $categories .= str_replace('/', '\/', $category) .'/';
        }
        return $categories;
    }

    private static function returnSupplierValue($supplierId)
    {
        $supplier = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->getSupplierQuery($supplierId)->getResult();
        $supplierName = $supplier[0]->getName();
        return $supplierName;
    }

    private static function returnfilterValue($paramName, $paramValue)
    {
    }
}