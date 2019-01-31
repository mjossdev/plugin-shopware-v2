<?php
/**
 * Class Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
 */
class Shopware_Plugins_Frontend_Boxalino_Helper_BxData {

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxData
     */
    protected static $instance = null;

    /**
     * @var \Shopware\Components\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    /**
     * @var
     */
    protected $db;

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_BxData constructor.
     */
    public function __construct()
    {
        $this->container = Shopware()->Container();
        $this->config = Shopware()->Config();
        $this->db = Shopware()->Db();
    }

    /**
     * @return Shopware_Plugins_Frontend_Boxalino_Helper_BxData
     */
    public static function instance() {
        if (self::$instance == null)
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_Helper_BxData();
        return self::$instance;
    }

    /**
     * @return Shopware_Components_Config
     */
    public function Config()
    {
        return $this->config;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->container->get($name);
    }

    /**
     * @param $facets
     * @param null $request
     * @param string $immediateDeliveryField
     * @return array
     */
    public function getFacetConfig($facets, $request, $immediateDeliveryField = 'products_immediate_delivery') {
        $snippetManager = Shopware()->Snippets()->getNamespace('frontend/listing/facet_labels');
        $options = [];
        $mapper = $this->get('query_alias_mapper');
        $params = $request->getParams();
        foreach ($facets as $fieldName => $facet) {
            switch ($fieldName) {
                case 'price':
                    $min = isset($params[$mapper->getShortAlias('priceMin')]) ? $params[$mapper->getShortAlias('priceMin')] : "*";
                    $max = isset($params[$mapper->getShortAlias('priceMax')]) ? $params[$mapper->getShortAlias('priceMax')] : "*";

                    $value = ["{$min}-{$max}"];
                    $options['discountedPrice'] = [
                        'value' => $value,
                        'type' => 'ranged',
                        'bounds' => true,
                        'label' => $snippetManager->get('price', 'Preis')
                    ];
                    break;
                case 'category':
                    $options['category'] = [
                        'label' => $snippetManager->get('category', 'Kategorie')
                    ];
                    $id = null;
                    if(version_compare(Shopware::VERSION, '5.3.0', '<')) {
                        if(isset($params[$mapper->getShortAlias('sCategory')])){
                            $id = $params[$mapper->getShortAlias('sCategory')];
                        } else if (isset($params['sCategory'])){
                            $id = $params['sCategory'];
                        } else {
                            $id = Shopware()->Shop()->getCategory()->getId();
                        }
                    } else {
                        if (isset($params[$mapper->getShortAlias('categoryFilter')])){
                            $id = $params[$mapper->getShortAlias('categoryFilter')];
                        } else if (isset($params['categoryFilter'])){
                            $id = $params['categoryFilter'];
                        } else {
                            $id = Shopware()->Shop()->getCategory()->getId();
                        }
                    }
                    if(!is_null($id)) {
                        $ids = explode('|', $id);
                        foreach ($ids as $i) {
                            $options['category']['value'][] = $i;
                        }
                    }
                    break;
                case 'property':
                    $options = array_merge($options, $this->getAllFilterableOptions());
                    $id = null;
                    if( isset($params[$mapper->getShortAlias('sFilterProperties')])) {
                        $id = $params[$mapper->getShortAlias('sFilterProperties')];
                    } else if($params['sFilterProperties']) {
                        $id = $params['sFilterProperties'];
                    }
                    if($id) {
                        $ids = explode('|', $id);
                        foreach ($ids as $i) {
                            $option = $this->getOptionFromValueId($i);
                            $name = trim($option['value']);
                            $select = "{$name}_bx_{$i}";
                            $bxFieldName = 'products_optionID_mapped_' . $option['id'];
                            $options[$bxFieldName]['value'][] = $select;
                        }

                    }
                    break;
                case 'manufacturer':
                    $id = isset($params[$mapper->getShortAlias('sSupplier')]) ? $params[$mapper->getShortAlias('sSupplier')] : null;
                    $options['products_brand']['label'] = $snippetManager->get('manufacturer', 'Hersteller');
                    if($id) {
                        $ids = explode('|', $id);
                        foreach ($ids as $i) {
                            $name = trim($this->getSupplierName($i));
                            $options['products_brand']['value'][] = $name;
                        }
                    }

                    break;
                case 'shipping_free':
                    $freeShipping = isset($params[$mapper->getShortAlias('shippingFree')]) ? $params[$mapper->getShortAlias('shippingFree')] : null;
                    $options['products_shippingfree']['label'] = $snippetManager->get('shipping_free', 'Versandkostenfrei');
                    if($freeShipping) {
                        $options['products_shippingfree']['value'] = [1];
                    }
                    break;
                case 'immediate_delivery':
                    $immediate_delivery = isset($params[$mapper->getShortAlias('immediateDelivery')]) ? $params[$mapper->getShortAlias('immediateDelivery')] : null;
                    $options[$immediateDeliveryField]['label'] = $snippetManager->get('immediate_delivery', 'Sofort lieferbar');
                    if($immediate_delivery) {
                        $options[$immediateDeliveryField]['value'] = [1];
                    }
                    break;
                case 'vote_average':
                    $top = (version_compare(Shopware::VERSION, '5.3.0', '<')) ? 5 : 4;
                    $vote = isset($params['rating']) ? range($params['rating'], $top) : null;
                    $options['di_rating']['label'] = $snippetManager->get('vote_average', 'Bewertung');
                    if($vote) {
                        $options['di_rating']['value'] = $vote;
                    }
                    break;
                default:
                    break;
            }
        }
        return $options;
    }

    public function getSupplierName($supplier) {
        $sql = $this->db->select()->from(array("ss"=>"s_articles_supplier"), array("name"))
            ->where("ss.id = ?", $supplier);

        return $this->db->fetchOne($sql);
    }

    public function getOptionFromValueId($id){
        $sql = $this->db->select()->from(array('f_v' => 's_filter_values'), array('value'))
            ->join(array('f_o' => 's_filter_options'), 'f_v.optionID = f_o.id', array('id','name'))
            ->where('f_v.id = ?', $id);
        return $this->db->fetchRow($sql);
    }

    public function useTranslation($objectType){
        $shop_id = $this->getShopId();
        $sql = $this->db->select()->from(array('c_t' => 's_core_translations'))
            ->where('c_t.objectlanguage = ?', $shop_id)
            ->where('c_t.objecttype = ?', $objectType);
        return (bool) $this->db->query($sql)->rowCount();
    }

    public function getShopId(){
        return $this->Config()->get('boxalino_overwrite_shop') != '' ? (int) $this->Config()->get('boxalino_overwrite_shop') : Shopware()->Shop()->getId();
    }

    protected function getAllFilterableOptions() {
        $options = array();
        $sql = $this->db->select()->from(array('f_o' => 's_filter_options'))
            ->where('f_o.filterable = 1');
        $shop_id = $this->getShopId();

        $useTranslation = $this->useTranslation($shop_id, 'propertyoption');
        if($useTranslation) {
            $sql
                ->joinLeft(array('t' => 's_core_translations'),
                    'f_o.id = t.objectkey AND t.objecttype = ' . $this->db->quote('propertyoption') . ' AND t.objectlanguage = ' . $shop_id,
                    array('objectdata'));
        }

        $result = $this->db->fetchAll($sql);
        foreach ($result as $row){
            if($useTranslation && isset($row['objectdata'])) {
                $translation = unserialize($row['objectdata']);
                $row['name'] = isset($translation['optionName']) && $translation['optionName'] != '' ?
                    $translation['optionName'] : $row['name'];
            }
            $options['products_optionID_mapped_' . $row['id']] = ['label' => trim($row['name'])];
        }

        return $options;
    }

    /**
     * @param Shopware\Bundle\SearchBundle\Criteria $criteria
     * @return array
     */
    public function getSortOrder(Shopware\Bundle\SearchBundle\Criteria $criteria, $default_sort = null, $listing = false)
    {
        /* @var Shopware\Bundle\SearchBundle\Sorting\Sorting $sort */
        $sort = current($criteria->getSortings());
        if(empty($sort))
        {
            $sort = new \Shopware\Bundle\SearchBundle\Sorting\SearchRankingSorting(Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC);
        }

        $dir = null;
        $additionalSort = null;
        if($listing && is_null($default_sort) && $this->Config()->get('boxalino_navigation_sorting')){
            return array();
        }

        $name = "default";
        if($sort instanceof Shopware\Bundle\SearchBundle\Sorting\Sorting)
        {
            $name = $name = $sort->getName();
        }

        switch ($name) {
            case 'popularity':
                $field = 'products_sales';
                break;
            case 'prices':
                $field = 'products_bx_grouped_price';
                break;
            case 'product_name':
                $field = 'title';
                break;
            case 'release_date':
                $field = 'products_datum';
                $additionalSort = true;
                break;
            default:
                if ($listing == true) {
                    $default_sort = is_null($default_sort) ? $this->getDefaultSort() : $default_sort;
                    switch ($default_sort) {
                        case 1:
                            $field = 'products_datum';
                            $additionalSort = true;
                            break 2;
                        case 2:
                            $field = 'products_sales';
                            break 2;
                        case 3:
                        case 4:
                            if ($default_sort == 3) {
                                $dir = false;
                            }
                            $field = 'products_bx_grouped_price';
                            break 2;
                        case 5:
                        case 6:
                            if ($default_sort == 5) {
                                $dir = false;
                            }
                            $field = 'title';
                            break 2;
                        default:
                            if ($this->Config()->get('boxalino_navigation_sorting') == false) {
                                $field = 'products_datum';
                                $additionalSort = true;
                                break 2;
                            }
                            break;
                    }
                }
                return array();
        }
        $sortReturn[] = array(
            'field' => $field,
            'reverse' => (is_null($dir) ? $sort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
        );
        if($additionalSort) {
            $sortReturn[] = array(
                'field' => 'products_changetime',
                'reverse' => (is_null($dir) ? $sort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
            );
        }
        return $sortReturn;
    }

    protected function getDefaultSort(){
        $sql = $this->db->select()
            ->from(array('c_e' => 's_core_config_elements', array('c_v.value')))
            ->join(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id')
            ->where("name = ?", "defaultListingSorting");
        $result = $this->db->fetchRow($sql);
        return isset($result) ? unserialize($result['value']) : null;
    }

    public function getLocalArticles($ids, $highlightedProducts=array()) {
        if (empty($ids)) {
            return array();
        }
        if(empty($highlightedProducts))
        {
            $unsortedArticles = $this->getUnsortedArticlesByIds($ids);
        } else {
            $unsortedArticles = $this->getUnsortedArticlesForFinder($ids, $highlightedProducts);
        }

        $articles = array();
        foreach ($ids as $id) {
            if(isset($unsortedArticles[$id])){
                $articles[$unsortedArticles[$id]['ordernumber']] = $unsortedArticles[$id];
            }
        }
        return $articles;
    }

    protected function getUnsortedArticlesByIds($ids)
    {
        $context = $this->container->get('shopware_storefront.context_service')->getProductContext();
        $listProductService = $this->container->get('shopware_storefront.list_product_service');
        $resultedProducts = $listProductService->getList($ids, $context);
        $legacyStructConverter = $this->container->get('legacy_struct_converter');

        return $legacyStructConverter->convertListProductStructList($resultedProducts);
    }

    /**
     * adds product configuration to the list
     * upate the displayed price based on the default option
     *
     * @param $ids
     * @return mixed
     */
    protected function getUnsortedArticlesForFinder($ids, $highlightedProducts)
    {
        $scoredProducts = array_keys(array_filter(array_combine($ids, $highlightedProducts)));
        $context = $this->container->get('shopware_storefront.context_service')->getProductContext();
        $productService = $this->container->get('shopware_storefront.product_service');
        $resultedProducts = $productService->getList($ids, $context);
        $productNumberService = $this->container->get('shopware_storefront.product_number_service');
        $configuratorService = $this->container->get('shopware_storefront.configurator_service');
        $legacyStructConverter = $this->container->get('legacy_struct_converter');
        $configurators = array();
        foreach ($resultedProducts as $number => &$product) {
            if ($product->hasConfigurator() && in_array($number, $scoredProducts)) {
                $mainNumber = $productNumberService->getMainProductNumberById($product->getId());
                if (!$mainNumber) {
                    $productNumber=$product->getId();
                }
                $product = $productService->get($mainNumber, $context);
                $selection = array();
                $selection = $product->getSelectedOptions();
                $configurator = $configuratorService->getProductConfigurator(
                    $product,
                    $context,
                    $selection
                );
                $convertedConfigurator = $legacyStructConverter->convertConfiguratorStruct($product, $configurator);
                $configurators[$number] = $convertedConfigurator;

                $product->setListingPrice($product->getPrices()[0]);
                $product->setAllowBuyInListing(true);
            }
        }

        $convertedProductsToArray = $legacyStructConverter->convertListProductStructList($resultedProducts);
        foreach ($convertedProductsToArray as $number => &$data)
        {
            if(isset($configurators[$number]))
            {
                $data['priceStartingFrom'] = null;
                $data = array_merge($data, $configurators[$number]);
            }
        }

        return $convertedProductsToArray;
    }

    public function getRelatedBlogs($articleId) {
        if($articleId != '') {
            $sql = $this->db->select()->from(array('a_b' => 's_blog_assigned_articles'), array(new Zend_Db_Expr("CONCAT('blog_',a_b.blog_id)")))
                ->where('a_b.article_id = ?', (int) $articleId);

            return $this->db->fetchCol($sql);
        }

        return array();
    }

    /**
     * @param $categoryId
     * @return int | null
     */
    public function findStreamIdByCategoryId($categoryId)
    {
        $sql = $this->db->select()->from(array("sc"=>"s_categories"), array("stream_id"))
            ->where("sc.id = ?", $categoryId);

        return $this->db->fetchOne($sql);
    }

    public function transformBlog($blogs) {
        $blogArticles = array();
        foreach ($blogs as $blog) {
            $article = array();
            foreach ($blog as $fieldName => $value) {
                $value = reset($value);
                $field = substr($fieldName, 14);
                if($field == 'id') {
                    $value = substr($value, 5);
                }
                if($field == 'short_description') {
                    $field = 'shortDescription';
                }
                $article[$field] = $value;
            }
            $blogArticles[$article['id']] = $article;
        }
        $blogArticles = $this->loadBlogMedia($blogArticles);
        return $blogArticles;
    }

    public function loadBlogMedia($blogArticles) {
        if(empty($blogArticles)) {
            return $blogArticles;
        }
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media_id']) && $blogArticle['media_id'] != '') {
                return $blogArticle['media_id'];
            }
        }, $blogArticles);
        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->container->get('shopware_storefront.media_service')->getList($mediaIds, $context);

        foreach ($blogArticles as $key => $blogArticle) {

            $mediaId = null;
            if (isset($blogArticle['media_id']) && $blogArticle['media_id'] != '') {
                $mediaId = $blogArticle['media_id'];
            }
            if (!isset($medias[$mediaId])) {
                continue;
            }

            $media = $medias[$mediaId];
            $media = $this->container->get('legacy_struct_converter')->convertMediaStruct($media);
            $blogArticles[$key]['media'] = $media;
        }

        return $blogArticles;
    }

    public function enhanceBlogArticles($blogArticles) {
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);

        $context = $this->container->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->container->get('shopware_storefront.media_service')->getList($mediaIds, $context);

        foreach ($blogArticles as $key => $blogArticle) {
            //adding number of comments to the blog article
            $blogArticles[$key]["numberOfComments"] = count($blogArticle["comments"]);

            //adding thumbnails to the blog article
            if (empty($blogArticle["media"][0]['mediaId'])) {
                continue;
            }

            $mediaId = $blogArticle["media"][0]['mediaId'];
            if (!isset($medias[$mediaId])) {
                continue;
            }

            /**@var $media \Shopware\Bundle\StoreFrontBundle\Struct\Media*/
            $media = $medias[$mediaId];
            $media = $this->container->get('legacy_struct_converter')->convertMediaStruct($media);
            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }

    public function isValidCategory($categoryId) {
        $defaultShopCategoryId = Shopware()->Shop()->getCategory()->getId();

        /**@var $repository \Shopware\Models\Category\Repository*/
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Category\Category');
        $categoryPath = $repository->getPathById($categoryId);

        if (!$categoryPath) {
            return true;
        }

        if (!array_key_exists($defaultShopCategoryId, $categoryPath)) {
            return false;
        }

        return true;
    }

    public function getStreamById($productStreamId) {
        $row = $this->db->fetchAssoc(
            'SELECT streams.*, customSorting.sortings as customSortings
             FROM s_product_streams streams
             LEFT JOIN s_search_custom_sorting customSorting
                 ON customSorting.id = streams.sorting_id
             WHERE streams.id = :productStreamId
             LIMIT 1',
            ['productStreamId' => $productStreamId]
        );

        return $row;
    }

    /**
     * @param $category_id
     * @return bool
     */
    public function categoryShowFilter($category_id) {
        if($category_id) {
            $sql = $this->db->select()->from(array('c' => 's_categories'))
                ->where('c.id = ?', $category_id);
            $result = $this->db->fetchRow($sql);
            if(empty($result))
            {
                return true;
            }
             return !$result['hidefilter'];
        }
        return true;
    }

}