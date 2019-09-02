<?php

use Shopware\Bundle\SearchBundle\FacetInterface;
use Shopware\Bundle\SearchBundle\FacetResult\BooleanFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup;
use Shopware\Bundle\SearchBundle\FacetResult\MediaListItem;
use Shopware\Bundle\SearchBundle\FacetResult\RadioFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\TreeFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\TreeItem;
use Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult;
use Shopware\Bundle\SearchBundle\FacetResult\ValueListItem;
use Shopware\Components\DependencyInjection\Container;

/**
 * Class Shopware_Plugins_Frontend_Boxalino_Bundle_Facet
 */
class Shopware_Plugins_Frontend_Boxalino_Bundle_Facet
{

    /**
     * @var array
     */
    protected $facetOptions = [];

    /**
     * @var null Shopware_Plugins_Frontend_Boxalino_Bundle_Search
     */
    protected $searchBundle = null;

    /**
     * @var Shopware_Components_Config
     */
    protected $config;

    /**
     * @var Shopware\Components\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var
     */
    protected $request;

    /**
     * @var string
     */
    protected $choice = '';

    /**
     * @var null
     */
    protected $variantIndex = null;

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Helper_BxData \
     */
    protected $dataHelper;

    protected $facetHandlers = [];


    /**
     * constructor
     * @param Shopware_Plugins_Frontend_Boxalino_Bundle_Search $searchBundle
     */
    public function __construct(Shopware_Plugins_Frontend_Boxalino_Bundle_Search $searchBundle)
    {
        $this->config = Shopware()->Config();
        $this->container = Shopware()->Container();
        $this->dataHelper = Shopware_Plugins_Frontend_Boxalino_Helper_BxData::instance();
        $this->searchBundle = $searchBundle;
    }


    /**
     * @param $facets
     * @param $context
     * @param null $request
     * @param string $choice
     * @return array
     */
    public function updateFacetsWithResult()
    {
        $request = $this->getRequest();
        $this->getVariantIndex();
        $start = microtime(true);
        $lang = substr(Shopware()->Shop()->getLocale()->getLocale(), 0, 2);
        $this->facetOptions['mode'] = $this->getConfig()->get('listingMode');
        $bxFacets = $this->getBxFacets('product');
        $facets = $this->getStoreFacets();
        $context = $this->getContext();
        $propertyFacets = [];
        $filters = array();
        $mapper = $this->get('query_alias_mapper');
        if(!$propertyFieldName = $mapper->getShortAlias('sFilterProperties')) {
            $propertyFieldName = 'sFilterProperties';
        }
        $useTranslation = $this->dataHelper->useTranslation('propertyvalue');
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);

        }
        $leftFacets = $bxFacets->getLeftFacets();
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $t1) * 1000 ;
            $this->getSearchBundle()->addNotification("Search getLeftFacets took: " . $t1 . "ms.");
        }
        foreach ($leftFacets as $fieldName) {
            $key = '';
            if ($bxFacets->isFacetHidden($fieldName)) {
                continue;
            }

            switch ($fieldName) {
                case 'discountedPrice':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    if(isset($facets['price'])){
                        $facet = $facets['price'];
                        $selectedRange = $bxFacets->getSelectedPriceRange();
                        $label = trim($bxFacets->getFacetLabel($fieldName,$lang));
                        $this->facetOptions[$label] = [
                            'fieldName' => $fieldName,
                            'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                        ];
                        $priceRange = explode('-', $bxFacets->getPriceRanges()[0]);
                        $from = (float) $priceRange[0];
                        $to = (float) $priceRange[1];
                        if($selectedRange == '0-0'){
                            $activeMin = $from;
                            $activeMax = $to;
                        } else {
                            $selectedRange = explode('-', $selectedRange);
                            $activeMin = $selectedRange[0];
                            $activeMax = $selectedRange[1];
                        }

                        $result = new Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult(
                            $facet->getName(),
                            $selectedRange == '0-0' ? false : $bxFacets->isSelected($fieldName),
                            $label,
                            $from,
                            $to,
                            $activeMin,
                            $activeMax,
                            $mapper->getShortAlias('priceMin'),
                            $mapper->getShortAlias('priceMax')
                        );
                        $result->setTemplate('frontend/listing/filter/facet-currency-range.tpl');
                        $filters[] = $result;
                    }
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->getSearchBundle()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'categories':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    $facet = isset($facets['category']) ? $facets['category'] : $this->getCategoryFacet();
                    $selectedCategoryId = $bxFacets->getSelectedCategoryIds();
                    $shopCategory = Shopware()->Shop()->getCategory()->getName();
                    $shopCategoryId = Shopware()->Shop()->getCategory()->getId();

                    $ids = array();

                    foreach (range(0, $facet->getDepth()) as $i) {
                        $levelCategories = $bxFacets->getCategoryFromLevel($i);
                        foreach ($levelCategories as $lc) {
                            if(strpos($lc, $shopCategory) !== false) {
                                $id = reset(explode("/", $lc));
                                if($id != $shopCategoryId) {
                                    $ids[$id] = $id;
                                }
                            }
                        }
                    }
                    if (!$categoryFieldName = $mapper->getShortAlias('categoryFilter')) {
                        $categoryFieldName = 'categoryFilter';
                    }
                    if(reset($selectedCategoryId) != $shopCategoryId) {
                        foreach ($bxFacets->getParentCategories() as $category_id => $parent){
                            if(($category_id != $shopCategoryId) && !isset($ids[$category_id])) {
                                $ids[] = $category_id;
                            }
                        }
                    }
                    $label = $bxFacets->getFacetLabel($fieldName,$lang);
                    $categories = $this->get('shopware_storefront.category_service')->getList($ids, $context);
                    $treeResult = $this->generateTreeResult($facet, $selectedCategoryId, $categories, $label, $bxFacets, $categoryFieldName);

                    if(empty($treeResult)){
                        unset($facets['categories']);
                    } else {
                        $filters[] = $treeResult;
                    }

                    $this->facetOptions[$label] = [
                        'fieldName' => $fieldName,
                        'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                    ];
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->getSearchBundle()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'products_shippingfree':
                    $key = 'shipping_free';
                case 'products_immediate_delivery':
                    if($key == '') {
                        $key = 'immediate_delivery';
                    }
                    $facet = $facets[$key];
                    $facetFieldName = $key == 'shipping_free' ? $mapper->getShortAlias('shippingFree') : $mapper->getShortAlias('immediateDelivery');

                    $facetValues = $bxFacets->getFacetValues($fieldName);
                    if($facetValues && sizeof($facetValues) == 1 && reset($facetValues) == 0) {
                        break;
                    }
                    $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\BooleanFacetResult(
                        $facet->getName(),
                        $facetFieldName,
                        $bxFacets->isSelected($fieldName),
                        $bxFacets->getFacetLabel($fieldName,$lang),
                        []
                    );
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $start) * 1000 ;
                        $this->getSearchBundle()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'products_brand':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    $params = $request->getParams();
                    $id = isset($params[$mapper->getShortAlias('sSupplier')]) ? $params[$mapper->getShortAlias('sSupplier')] : null;
                    $values = $bxFacets->getFacetSelectedValues($fieldName);
                    if(sizeof($values) > 0 && is_null($id)) {
                        break;
                    }
                    $facet = $facets['manufacturer'];
                    $returnFacet = $this->generateManufacturerListItem($bxFacets, $facet, $lang);
                    if($returnFacet) {
                        $this->facetOptions[$bxFacets->getFacetLabel($fieldName,$lang)] = [
                            'fieldName' => $fieldName,
                            'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                        ];
                        $filters[] = $returnFacet;
                    }
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->getSearchBundle()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                case 'di_rating':
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    $facet = $facets['vote_average'];
                    $values = $bxFacets->getFacetValues($fieldName);
                    $data = array();
                    $selectedValue = null;
                    $selected = $bxFacets->isSelected($fieldName);
                    $selectedValues = $bxFacets->getSelectedValues($fieldName);
                    $setMin = !empty($selectedValues) ? min($selectedValues) : null;

                    $values = array_reverse($values);
                    foreach ($values as $value) {
                        if($value == 0) continue;
                        $count = $bxFacets->getFacetValueCount($fieldName, $value);
                        $data[] = new ValueListItem($value, (string) $count, $setMin == $value);
                    }

                    if (!$facetFieldName = $mapper->getShortAlias('rating')) {
                        $facetFieldName = 'rating';
                    }
                    $filters[] =  new RadioFacetResult(
                        $facet->getName(),
                        $selected,
                        $bxFacets->getFacetLabel($fieldName,$lang),
                        $data,
                        $facetFieldName,
                        [],
                        'frontend/listing/filter/facet-rating.tpl'
                    );
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->getSearchBundle()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
                default:
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = microtime(true);
                    }
                    if ((strpos($fieldName, 'products_optionID_mapped') !== false)) {
                        $facet = $facets['property'];
                        $returnFacet = $this->generateListItem($fieldName, $bxFacets, $facet, $lang, $useTranslation, $propertyFieldName);
                        if($returnFacet) {
                            $this->facetOptions[$bxFacets->getFacetLabel($fieldName, $lang)] = [
                                'fieldName' => $fieldName,
                                'expanded' => $bxFacets->isFacetExpanded($fieldName, false)
                            ];
                            if( $this->facetOptions['mode'] == 'filter_ajax_reload'){
                                $propertyFacets[] = $returnFacet;
                            } else {
                                $filters[] = $returnFacet;
                            }
                        }
                    }
                    if($_REQUEST['dev_bx_debug'] == 'true'){
                        $t1 = (microtime(true) - $t1) * 1000 ;
                        $this->getSearchBundle()->addNotification("Search updateFacets for $fieldName: " . $t1 . "ms.");
                    }
                    break;
            }
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $start) * 1000 ;
            $this->getSearchBundle()->addNotification("Search updateFacets after for loop: " . $t1 . "ms.");
        }

        if( $this->facetOptions['mode'] == 'filter_ajax_reload') {
            $filters[] = new Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup($propertyFacets, null, 'property');
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $start) * 1000 ;
            $this->getSearchBundle()->addNotification("Search updateFacets after took: " . $t1 . "ms.");
        }

        return $filters;
    }

    /**
     * @param $bxFacets
     * @param $facet
     * @param $lang
     * @return \Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult|void
     */
    protected function generateManufacturerListItem($bxFacets, $facet, $lang)
    {
        $db = Shopware()->Db();
        $fieldName = 'products_brand';
        $where_statement = '';
        $values = $bxFacets->getFacetValues($fieldName);
        if(sizeof($values) == 0){
            return;
        }
        foreach ($values as $index => $value) {
            if($index > 0) {
                $where_statement .= ' OR ';
            }
            $where_statement .= 'a_s.name LIKE \'%'. addslashes($value) .'%\'';
        }

        $sql = $db->select()
            ->from(array('a_s' => 's_articles_supplier', array('a_s.id', 'a_s.name')))
            ->where($where_statement);
        $result = $db->fetchAll($sql);
        $showCount = $bxFacets->showFacetValueCounters($fieldName);
        $values = $this->useValuesAsKeys($values);
        foreach ($result as $r) {
            $label = trim($r['name']);
            if(!isset($values[$label])) {
                continue;
            }
            $selected = $bxFacets->isFacetValueSelected($fieldName, $label);
            $values[$label] = new Shopware\Bundle\SearchBundle\FacetResult\MediaListItem(
                (int)$r['id'],
                $showCount ? $label . ' (' . $bxFacets->getFacetValueCount($fieldName, $label) . ')' : $label,
                $selected
            );
        }
        $finalValues = array();
        foreach ($values as $key => $innerValue) {
            if(!is_string($innerValue)) {
                $finalValues[] = $innerValue;
            }
        }
        $mapper = $this->get('query_alias_mapper');
        return new Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult(
            'manufacturer',
            $bxFacets->isSelected($fieldName),
            $bxFacets->getFacetLabel($fieldName, $lang),
            $finalValues,
            $mapper->getShortAlias('sSupplier')
        );
    }

    protected function generateTreeResult($facet, $selectedCategoryId, $categories, $label, $bxFacets, $categoryFieldName)
    {
        $parent = null;
        $values = [];
        $showCount = $bxFacets->showFacetValueCounters('categories');
        if(sizeof($selectedCategoryId) == 1 && (reset($selectedCategoryId) == Shopware()->Shop()->getCategory()->getId())) {
            $selectedCategoryId = [];
        }
        $parent = Shopware()->Shop()->getCategory()->getId();
        $items = $this->getCategoriesOfParent($categories, $parent);
        foreach ($items as $item) {
            $values[] = $this->createTreeItem($categories, $item, $selectedCategoryId, $showCount, $bxFacets);
        }

        if(empty($values))
        {
            return array();
        }

        return new TreeFacetResult(
            $facet->getName(),
            $categoryFieldName,
            !empty($selectedCategoryId),
            $label,
            $values,
            [],
            'frontend/listing/filter/facet-value-tree.tpl'
        );
    }

    /**
     * @param $fieldName
     * @param $bxFacets
     * @param $facet
     * @param $lang
     * @param $useTranslation
     * @param $propertyFieldName
     */
    protected function generateListItem($fieldName, $bxFacets, $facet, $lang, $useTranslation, $propertyFieldName)
    {
        if(is_null($facet)) {
            return;
        }
        $option_id = end(explode('_', $fieldName));
        $values = $bxFacets->getFacetValues($fieldName);

        if(sizeof($values) == 0) {
            return;
        }

        $result = $this->dataHelper->getFacetValuesResult($option_id, $values, $useTranslation);
        $media_class = false;
        $showCount = $bxFacets->showFacetValueCounters($fieldName);
        $values = $this->useValuesAsKeys($values);
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);
        }

        foreach ($result as $r) {
            if($useTranslation == true && isset($r['objectdata'])) {
                $translation = unserialize($r['objectdata']);
                $r['value'] = isset($translation['optionValue']) && $translation['optionValue'] != '' ?
                    $translation['optionValue'] : $r['value'];
            }
            $label = trim($r['value']);
            $key = $label . "_bx_{$r['id']}";
            if(!isset($values[$key])) {
                continue;
            }

            $selected = $bxFacets->isFacetValueSelected($fieldName, $key);
            if ($showCount) {
                $label .= ' (' . $bxFacets->getFacetValueCount($fieldName, $key) . ')';
            }
            $media = $r['media_id'];
            if (!is_null($media)) {
                $media = $this->getMediaById($media);
                $media_class = true;
            }
            $values[$key] = new Shopware\Bundle\SearchBundle\FacetResult\MediaListItem(
                (int)$r['id'],
                $label,
                (boolean)$selected,
                $media
            );
        }

        $systemOrder = $bxFacets->getFacetExtraInfo($fieldName, 'valueorderEnums') == 2 ? true : false;
        if($systemOrder)
        {
            $positionSort = array_column($result, "position");
            array_multisort($positionSort, SORT_ASC, $values);
        }

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $t1) * 1000 ;
            $this->getSearchBundle()->addNotification("Search generateListItem for $fieldName: " . $t1 . "ms.");
        }
        $finalValues = array();
        foreach ($values as $key => $innerValue) {
            if(!is_string($innerValue)) {
                $finalValues[] = $innerValue;
            }
        }
        $class = $media_class === true ? 'Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult' :
            'Shopware\Bundle\SearchBundle\FacetResult\ValueListFacetResult';

        return new $class(
            $facet->getName(),
            $bxFacets->isSelected($fieldName),
            $bxFacets->getFacetLabel($fieldName,$lang),
            $finalValues,
            $propertyFieldName
        );
    }

    /**
     * @param $categories
     * @param $category
     * @param $active
     * @param $showCount
     * @param $bxFacets
     * @return TreeItem
     */
    protected function createTreeItem($categories, $category, $active, $showCount, $bxFacets)
    {
        $children = $this->getCategoriesOfParent(
            $categories,
            $category->getId()
        );

        $values = [];
        foreach ($children as $child) {
            $values[] = $this->createTreeItem($categories, $child, $active, $showCount, $bxFacets);
        }
        $name = $category->getName();
        if($showCount) {
            $cat = $bxFacets->getCategoryById($category->getId());
            $name .= " (" . $bxFacets->getCategoryValueCount($cat) . ")";
        }
        return new TreeItem(
            $category->getId(),
            $name,
            in_array($category->getId(), $active),
            $values,
            $category->getAttributes()
        );
    }

    /**
     * @param $categories
     * @param $parentId
     * @return array
     */
    public function getCategoriesOfParent($categories, $parentId)
    {
        $result = [];
        foreach ($categories as $category) {
            if (!$category->getPath() && $parentId !== null) {
                continue;
            }

            if ($category->getPath() == $parentId) {
                $result[] = $category;
                continue;
            }

            $parents = $category->getPath();
            $lastParent = $parents[count($parents) - 1];

            if ($lastParent == $parentId) {
                $result[] = $category;
            }
        }
        return $result;
    }

    /**
     * @return \Shopware\Bundle\SearchBundle\Facet\CategoryFacet
     */
    public function getCategoryFacet()
    {
        $snippetManager = Shopware()->Snippets()->getNamespace('frontend/listing/facet_labels');
        $label = $snippetManager->get('category', 'Kategorie');
        $depth = $this->getConfig()->get('levels');

        return new \Shopware\Bundle\SearchBundle\Facet\CategoryFacet($label, $depth);
    }

    /**
     * @deprecated
     * @param $values
     * @return mixed|null
     */
    public function getLowestActiveTreeItem($values) {
        foreach ($values as $value) {
            $innerValues = $value->getValues();
            if (count($innerValues)) {
                $innerValue = $this->getLowestActiveTreeItem($innerValues);
                if ($innerValue instanceof Shopware\Bundle\SearchBundle\FacetResult\TreeItem) {
                    return $innerValue;
                }
            }
            if ($value->isActive()) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @deprecated
     * @return array
     */
    public function registerFacetHandlers()
    {
        // did not find a way to use the service tag "facet_handler_dba"
        // it seems the dependency injection CompilerPass is not available to plugins?
        $facetHandlerIds = [
            'vote_average',
            'shipping_free',
            'product_attribute',
            'immediate_delivery',
            'manufacturer',
            'property',
            'category',
            'price',
        ];
        $facetHandlers = [];
        foreach ($facetHandlerIds as $id) {
            $facetHandlers[] = $this->container->get("shopware_searchdbal.${id}_facet_handler_dbal");
        }

        return $facetHandlers;
    }

    /**
     * @deprecated
     * @param \Shopware\Bundle\SearchBundle\FacetInterface $facet
     * @return FacetHandlerInterface|null|\Shopware\Bundle\SearchBundle\FacetHandlerInterface
     */
    public function getFacetHandler(Shopware\Bundle\SearchBundle\FacetInterface $facet) {
        if ($this->facetHandlers == null) {
            $this->facetHandlers = $this->registerFacetHandlers();
        }
        foreach ($this->facetHandlers as $handler) {
            if ($handler->supportsFacet($facet)) {
                return $handler;
            }
        }
        return null;
    }


    /**
     * @param $array
     * @return array
     */
    public function useValuesAsKeys($array)
    {
        return array_combine(array_keys(array_flip($array)),$array);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getMediaById($id)
    {
        return $this->get('shopware_storefront.media_service')
            ->get($id, $this->get('shopware_storefront.context_service')->getProductContext());
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
     * @return Shopware_Components_Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getRequest()
    {
        return $this->getSearchBundle()->getRequest();
    }

    public function getSearchBundle()
    {
        return $this->searchBundle;
    }

    public function getStoreFacets()
    {
        return $this->getSearchBundle()->getShopwareFacets();
    }

    public function getBxFacets($type)
    {
        return $this->getSearchBundle()->getFacets($type, $this->choice, $this->variantIndex);
    }

    public function getContext()
    {
        return $this->getSearchBundle()->getContext();
    }

    public function getVariantIndex()
    {
        $this->variantIndex = 0;
        if(empty($this->choice))
        {
            $this->variantIndex = null;
        }

        return $this;
    }

    /**
     * @param $bundle
     * @return $this
     */
    public function setSearchBundle(Shopware_Plugins_Frontend_Boxalino_Bundle_Search $bundle)
    {
        $this->searchBundle = $bundle;
        return $this;
    }


    public function setChoice($choice)
    {
        $this->choice = $choice;
        return $this;
    }

    public function getFacetOptions()
    {
        return $this->facetOptions;
    }

}