<?php

use Doctrine\DBAL\Connection;
/**
 * search interceptor for shopware 5 and following
 * uses SearchBundle
 */
class Shopware_Plugins_Frontend_Boxalino_SearchInterceptor
    extends Shopware_Plugins_Frontend_Boxalino_Interceptor {

    /**
     * @var Shopware\Components\DependencyInjection\Container
     */
    private $container;

    /**
     * @var Enlight_Event_EventManager
     */
    protected $eventManager;

    /**
     * @var FacetHandlerInterface[]
     */
    protected $facetHandlers;

    /**
     * constructor
     * @param Shopware_Plugins_Frontend_Boxalino_Bootstrap $bootstrap
     */
    public function __construct(Shopware_Plugins_Frontend_Boxalino_Bootstrap $bootstrap) {
        parent::__construct($bootstrap);
        $this->container = Shopware()->Container();
        $this->eventManager = Enlight()->Events();
    }

    /**
     * perform autocompletion suggestion
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function ajaxSearch(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled') || !$this->Config()->get('boxalino_autocomplete_enabled')) {
            return null;
        }
        $this->init($arguments);

        Enlight()->Plugins()->Controller()->Json()->setPadding();

        $term = $this->getSearchTerm();
        if (empty($term)) {
            return false;
        }

        $with_blog = $this->Config()->get('boxalino_blog_search_enabled');
        $templateProperties = $this->Helper()->autocomplete($term, $with_blog);
        $this->View()->loadTemplate('frontend/search/ajax.tpl');
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/ajax.tpl');
        $this->View()->assign($templateProperties);
        return false;
    }

    public function listingAjax(Enlight_Event_EventArgs $arguments) {

        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_navigation_enabled')) {
            return null;
        }

        $this->init($arguments);
        $viewData = $this->View()->getAssign();
        $catId = $this->Request()->getParam('sCategory');
        $streamId = $this->findStreamIdByCategoryId($catId);
        if ($streamId != null || !isset($viewData['sArticles']) || count($viewData['sArticles']) == 0) {
            return null;
        }
        $filter = array();
        if($supplier = $this->Request()->getParam('sSupplier')) {
            $supplier_name = $this->getSupplierName($supplier);
            $filter['products_brand'] = [$supplier_name];
        }
        $listingCount = $this->Request()->getActionName() == 'listingCount';
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createSearchCriteria($this->Request(), $context);
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $sort =  $this->getSortOrder($criteria, $viewData['sSort'], true);
        $facets = $this->createFacets($criteria, $context);
        $facetIdsToOptionIds = $this->getPropertyFacetOptionIds($facets);
        $queryText = $this->Request()->getParams()['q'];
        $options = $this->getFacetConfig($facets, $facetIdsToOptionIds);
        $this->Helper()->addSearch($queryText, $pageOffset, $hitCount, 'product', $sort, $options, $filter);
        $articles = $this->Helper()->getLocalArticles($this->Helper()->getEntitiesIds());
        $viewData['sArticles'] = $articles;
        if ($listingCount) {
            $this->Controller()->Response()->setBody('{"totalCount":' . $this->Helper()->getTotalHitCount() . '}');
            return false;
        }
        $this->View()->assign($viewData);
        return false;
    }

    public function listing(Enlight_Event_EventArgs $arguments) {


        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_navigation_enabled')) {
            return null;
        }

        $this->init($arguments);
        $viewData = $this->View()->getAssign();
        $catId = $this->Request()->getParam('sCategory');
        $streamId = $this->findStreamIdByCategoryId($catId);
        if ($streamId != null || !isset($viewData['sArticles']) || count($viewData['sArticles']) == 0) {
            return null;
        }

        $filter = array();
        if(isset($viewData['manufacturer']) && !empty($viewData['manufacturer'])) {
            $filter['products_brand'] = [$viewData['manufacturer']->getName()];
        }
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createSearchCriteria($this->Request(), $context);
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $this->createFacets($criteria, $context);
        $facetIdsToOptionIds = $this->getPropertyFacetOptionIds($facets);
        $options = $this->getFacetConfig($facets, $facetIdsToOptionIds);
        $sort =  $this->getSortOrder($criteria, $viewData['sSort'], true);
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $this->Helper()->addSearch('', $pageOffset, $hitCount, 'product', $sort, $options, $filter);
        $facets = $this->updateFacetsWithResult($facets, $facetIdsToOptionIds);
        $articles = $this->Helper()->getLocalArticles($this->Helper()->getEntitiesIds());
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        if ($this->Config()->get('boxalino_navigation_sorting') == true) {
            $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-sorting.tpl');
        }
        $totalHitCount = $this->Helper()->getTotalHitCount();
        $templateProperties = array(
            'criteria' => $criteria,
            'facets' => $facets,
            'sNumberArticles' => $totalHitCount,
            'sArticles' => $articles,
        );
        $templateProperties = array_merge($viewData, $templateProperties);
        $this->View()->assign($templateProperties);
        return false;
    }
    /**
     * perform search
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function search(Enlight_Event_EventArgs $arguments) {


        if (!$this->Config()->get('boxalino_active') || !$this->Config()->get('boxalino_search_enabled')) {
            return null;
        }

        $this->init($arguments);
        $term = $this->getSearchTerm();

        // Check if we have a one to one match for ordernumber, then redirect
        $location = $this->searchFuzzyCheck($term);
        if (!empty($location)) {
            return $this->Controller()->redirect($location);
        }

        /* @var ProductContextInterface $context */
        $context  = $this->get('shopware_storefront.context_service')->getProductContext();
        /* @var Shopware\Bundle\SearchBundle\Criteria $criteria */
        $criteria = $this->get('shopware_search.store_front_criteria_factory')
            ->createSearchCriteria($this->Request(), $context);

        // discard search / term conditions from criteria, such that _all_ facets are properly requested
        $criteria->removeCondition("term");
        $criteria->removeBaseCondition("search");
        $facets = $this->createFacets($criteria, $context);
        $facetIdsToOptionIds = $this->getPropertyFacetOptionIds($facets);
        $options = $this->getFacetConfig($facets, $facetIdsToOptionIds);
        $sort =  $this->getSortOrder($criteria);
        $config = $this->get('config');
        $pageCounts = array_values(explode('|', $config->get('fuzzySearchSelectPerPage')));
        $hitCount = $criteria->getLimit();
        $pageOffset = $criteria->getOffset();
        $bxHasOtherItems = false;

        $this->Helper()->addSearch($term, $pageOffset, $hitCount, 'product', $sort, $options);
        if($config->get('boxalino_blog_search_enabled')){
            $blogOffset = ($this->Request()->getParam('sBlogPage', 1) -1)*($hitCount);
            $this->Helper()->addSearch($term, $blogOffset, $hitCount, 'blog');
            $bxHasOtherItems = $this->Helper()->getTotalHitCount('blog') > 0;
        }

        $corrected = false;
        $articles = array();
        $no_result_articles = array();
        $sub_phrases = array();
        $totalHitCount = 0;
        $sub_phrase_limit = $config->get('boxalino_search_subphrase_result_limit');
        if ($this->Helper()->areThereSubPhrases() && $sub_phrase_limit > 0) {
            $sub_phrase_queries = array_slice(array_filter($this->Helper()->getSubPhrasesQueries()), 0, $sub_phrase_limit);
            foreach ($sub_phrase_queries as $query){
                $ids = array_slice($this->Helper()->getSubPhraseEntitiesIds($query), 0, $config->get('boxalino_search_subphrase_product_limit'));
                $suggestion_articles = [];
                if (count($ids) > 0) {
                    $suggestion_articles = $this->Helper()->getLocalArticles($ids);
                }
                $hitCount = $this->Helper()->getSubPhraseTotalHitCount($query);
                $sub_phrases[] = array('hitCount'=> $hitCount, 'query' => $query, 'articles' => $suggestion_articles);
            }
            $facets = array();
        } else {
            if ($totalHitCount = $this->Helper()->getTotalHitCount()) {
                if ($this->Helper()->areResultsCorrected()) {
                    $corrected = true;
                    $term = $this->Helper()->getCorrectedQuery();
                }
                $ids = $this->Helper()->getEntitiesIds();
                $articles = $this->Helper()->getLocalArticles($ids);
                $category = $this->Helper()->getFacets()->getParentCategories();
                if (!empty($category)) {
                    end($category);
                    $id = (int) key($category);
                    $this->Request()->setParam("sCategory", $id);
                    $criteria = $this->get('shopware_search.store_front_criteria_factory')
                        ->createSearchCriteria($this->Request(), $context);
                    $facets = $this->createFacets($criteria, $context);
                }
                $facets = $this->updateFacetsWithResult($facets, $facetIdsToOptionIds);
            } else {

                if ($config->get('boxalino_noresults_recommendation_enabled')) {
                    $this->Helper()->resetRequests();
                    $this->Helper()->flushResponses();
                    $min = $config->get('boxalino_noresults_recommendation_min');
                    $max = $config->get('boxalino_noresults_recommendation_max');
                    $choiceId = $config->get('boxalino_noresults_recommendation_name');
                    $this->Helper()->getRecommendation($choiceId, $max, $min, 0, [], '', false);
                    $hitIds = $this->Helper()->getRecommendation($choiceId);
                    $no_result_articles = $this->Helper()->getLocalArticles($hitIds);
                }
                $facets = array();
            }
        }
        $request = $this->Request();
        $params = $request->getParams();
        $params['sSearchOrginal'] = $term;
        $params['sSearch'] = $term;

        // Assign result to template
        $this->View()->loadTemplate('frontend/search/fuzzy.tpl');
        $this->View()->addTemplateDir($this->Bootstrap()->Path() . 'Views/emotion/');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/listing/actions/action-pagination.tpl');
        $this->View()->extendsTemplate('frontend/plugins/boxalino/search/fuzzy.tpl');
        $no_result_title = Shopware()->Snippets()->getNamespace('boxalino/intelligence')->get('search/noresult');

        $templateProperties = array_merge(array(
            'term' => $term,
            'corrected' => $corrected,
            'bxNoResult' => count($no_result_articles) > 0,
            'BxData' => [
                'article_slider_title'=> $no_result_title,
                'no_border'=> true,
                'article_slider_type' => 'selected_article',
                'values' => $no_result_articles,
                'article_slider_max_number' => count($no_result_articles),
                'article_slider_arrows' => 1
            ],
            'criteria' => $criteria,
            'facets' => $facets,
            'sPage' => $request->getParam('sPage', 1),
            'sSort' => $request->getParam('sSort', 7),
            'sTemplate' => $params['sTemplate'],
            'sPerPage' => $pageCounts,
            'sRequests' => $params,
            'shortParameters' => $this->get('query_alias_mapper')->getQueryAliases(),
            'pageSizes' => $pageCounts,
            'ajaxCountUrlParams' => ['sCategory' => $context->getShop()->getCategory()->getId()],
            'sSearchResults' => array(
                'sArticles' => $articles,
                'sArticlesCount' => $totalHitCount
            ),
            'productBoxLayout' => $config->get('searchProductBoxLayout'),
            'bxHasOtherItemTypes' => $bxHasOtherItems,
            'bxActiveTab' => $request->getParam('bxActiveTab', 'article'),
            'bxSubPhraseResults' => $sub_phrases
        ), $this->getSearchTemplateProperties($hitCount));
        $this->View()->assign($templateProperties);
        return false;
    }

    private function getSearchTemplateProperties($hitCount)
    {
        $props = array();
        $total = $this->Helper()->getTotalHitCount('blog');
        if ($total == 0) {
            return $props;
        }
        $sPage = $this->Request()->getParam('sBlogPage', 1);
        $entity_ids = $this->Helper()->getEntitiesIds('blog');

        if (!count($entity_ids)) {
            return $props;
        }
        $ids = array();
        foreach ($entity_ids as $id) {
            $ids[] = str_replace('blog_', '', $id);
        }
        $count = count($ids);
        $numberPages = ceil($count > 0 ? $total / $hitCount : 0);
        $props['bxBlogCount'] = $total;
        $props['sNumberPages'] = $numberPages;

        $repository = Shopware()->Models()->getRepository('Shopware\Models\Blog\Blog');
        $builder = $repository->getListQueryBuilder(array());
        $query = $builder
            ->andWhere($builder->expr()->in('blog.id', $ids))
            ->getQuery();
        $pages = array();

        if ($numberPages > 1) {
            $params = array_merge($this->Request()->getParams(), array('bxActiveTab' => 'blog'));
            for ($i = 1; $i <= $numberPages; $i++) {
                $pages["numbers"][$i]["markup"] = $i == $sPage;
                $pages["numbers"][$i]["value"] = $i;
                $pages["numbers"][$i]["link"] = $this->assemble(array_merge($params, array('sBlogPage' => $i)));
            }
            if ($sPage > 1) {
                $pages["previous"] = $this->assemble(array_merge($params, array('sBlogPage' => $sPage - 1)));
            } else {
                $pages["previous"] = null;
            }
            if ($sPage < $numberPages) {
                $pages["next"] = $this->assemble(array_merge($params, array('sBlogPage' => $sPage + 1)));
            } else {
                $pages["next"] = null;
            }
        }

        $props['sBlogPage'] = $sPage;
        $props['sPages'] = $pages;
        $blogArticles = $this->enhanceBlogArticles($query->getArrayResult());
        $props['sBlogArticles'] = $blogArticles;
        return $props;
    }

    private function assemble($params) {
        $p = $this->Request()->getBasePath() . $this->Request()->getPathInfo();
        if (empty($params)) return $p;

        $ignore = array("module" => 1, "controller" => 1, "action" => 1);
        $kv = [];
        array_walk($params, function($v, $k) use (&$kv, &$ignore) {
            if ($ignore[$k]) return;

            $kv[] = $k . '=' . $v;
        });
        return $p . "?" . implode('&', $kv);
    }

    // mostly copied from Frontend/Blog.php#indexAction
    private function enhanceBlogArticles($blogArticles) {
        $mediaIds = array_map(function ($blogArticle) {
            if (isset($blogArticle['media']) && $blogArticle['media'][0]['mediaId']) {
                return $blogArticle['media'][0]['mediaId'];
            }
        }, $blogArticles);
        $context = $this->Bootstrap()->get('shopware_storefront.context_service')->getShopContext();
        $medias = $this->Bootstrap()->get('shopware_storefront.media_service')->getList($mediaIds, $context);

        foreach ($blogArticles as $key => $blogArticle) {
            //adding number of comments to the blog article
            $blogArticles[$key]["numberOfComments"] = count($blogArticle["comments"]);

            //adding tags and tag filter links to the blog article
//             $tagsQuery = $this->repository->getTagsByBlogId($blogArticle["id"]);
//             $tagsData = $tagsQuery->getArrayResult();
//             $blogArticles[$key]["tags"] = $this->addLinksToFilter($tagsData, "sFilterTags", "name", false);

            //adding average vote data to the blog article
//             $avgVoteQuery = $this->repository->getAverageVoteQuery($blogArticle["id"]);
//             $blogArticles[$key]["sVoteAverage"] = $avgVoteQuery->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_SINGLE_SCALAR);

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
            $media = $this->get('legacy_struct_converter')->convertMediaStruct($media);

            if (Shopware()->Shop()->getTemplate()->getVersion() < 3) {
                $blogArticles[$key]["preview"]["thumbNails"] = array_column($media['thumbnails'], 'source');
            } else {
                $blogArticles[$key]['media'] = $media;
            }
        }
        return $blogArticles;
    }

    protected function getPropertyFacetOptionIds($facets) {
        $ids = array();
        foreach ($facets as $facet) {
            if ($facet->getFacetName() == "property") {
                $ids = array_merge($ids, $this->getValueIds($facet));
            }
        }
        $query = Shopware()->Container()->get('dbal_connection')->createQueryBuilder();
        $query->select('options.id, optionID')
            ->from('s_filter_values', 'options')
            ->where('options.id IN (:ids)')
            ->setParameter(':ids', $ids, Connection::PARAM_INT_ARRAY)
        ;
        $result = $query->execute()->fetchAll();
        $facetToOption = array();
        foreach ($result as $row) {
            $facetToOption[$row['id']] = $row['optionID'];
        }
        return $facetToOption;
    }

    protected function getValueIds($facet) {
        if ($facet instanceof Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup) {
            $ids = array();
            foreach ($facet->getfacetResults() as $facetResult) {
                $ids = array_merge($ids, $this->getValueIds($facetResult));
            }
            return $ids;
        } else {
            return array_map(function($value) { return $value->getId(); }, $facet->getValues());
        }
    }

    /**
     * Get service from resource loader
     *
     * @param string $name
     * @return mixed
     */
    public function get($name) {
        return $this->container->get($name);
    }

    /**
     * @return string
     */
    protected function getSearchTerm() {
        $term = $this->Request()->get('sSearch', '');

        $term = trim(strip_tags(htmlspecialchars_decode(stripslashes($term))));

        // we have to strip the / otherwise broken urls would be created e.g. wrong pager urls
        $term = str_replace('/', '', $term);

        return $term;
    }

    /**
     * Search product by order number
     *
     * @param string $search
     * @return string
     */
    protected function searchFuzzyCheck($search) {
        $minSearch = empty($this->Config()->sMINSEARCHLENGHT) ? 2 : (int) $this->Config()->sMINSEARCHLENGHT;
        $db = Shopware()->Db();
        if (!empty($search) && strlen($search) >= $minSearch) {
            $ordernumber = $db->quoteIdentifier('ordernumber');
            $sql = $db->select()
                ->distinct()
                ->from('s_articles_details', array('articleID'))
                ->where("$ordernumber = ?", $search)
                ->limit(2);
            $articles = $db->fetchCol($sql);

            if (empty($articles)) {
                $percent = $db->quote('%');
                $sql->orWhere("? LIKE CONCAT($ordernumber, $percent)", $search);
                $articles = $db->fetchCol($sql);
            }
        }
        if (!empty($articles) && count($articles) == 1) {
            $sql = $db->select()
                ->from(array('ac' => 's_articles_categories_ro'), array('ac.articleID'))
                ->joinInner(
                    array('c' => 's_categories'),
                    $db->quoteIdentifier('c.id') . ' = ' . $db->quoteIdentifier('ac.categoryID') . ' AND ' .
                    $db->quoteIdentifier('c.active') . ' = ' . $db->quote(1) . ' AND ' .
                    $db->quoteIdentifier('c.id') . ' = ' . $db->quote(Shopware()->Shop()->get('parentID'))
                )
                ->where($db->quoteIdentifier('ac.articleID') . ' = ?', $articles[0])
                ->limit(1);
            $articles = $db->fetchCol($sql);
        }
        if (!empty($articles) && count($articles) == 1) {
            return $this->Controller()->Front()->Router()->assemble(array('sViewport' => 'detail', 'sArticle' => $articles[0]));
        }
    }

    /**
     * @return Shopware\Bundle\SearchBundle\FacetHandlerInterface[]
     */
    protected function registerFacetHandlers() {
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
     * @param \Shopware\Bundle\SearchBundle\FacetInterface $facet
     * @return FacetHandlerInterface|null|\Shopware\Bundle\SearchBundle\FacetHandlerInterface
     */
    protected function getFacetHandler(Shopware\Bundle\SearchBundle\FacetInterface $facet) {
        if ($this->facetHandlers == null) {
            $this->facetHandlers = $this->registerFacetHandlers();
        }
        foreach ($this->facetHandlers as $handler) {
            if ($handler->supportsFacet($facet)) {
                return $handler;
            }
        }

        Shopware()->PluginLogger()->debug('Boxalino Search: Facet ' . get_class($facet) . ' not supported');
        return null;
    }

    /**
     * @param \Shopware\Bundle\SearchBundle\Criteria $criteria
     * @param \Shopware\Bundle\StoreFrontBundle\Struct\ShopContext $context
     * @return array
     */
    protected function createFacets(Shopware\Bundle\SearchBundle\Criteria $criteria, Shopware\Bundle\StoreFrontBundle\Struct\ShopContext $context) {
        $facets = array();

        foreach ($criteria->getFacets() as $facet) {
            $handler = $this->getFacetHandler($facet);
            if ($handler === null) continue;
            $result = $handler->generateFacet($facet, $criteria, $context);
            if (!$result) {
                continue;
            }

            if (!is_array($result)) {
                $result = [$result];
            }

            $facets = array_merge($facets, $result);
        }

        return $facets;
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResultInterface[] $facets
     * @return array
     */
    protected function getFacetConfig($facets, $facetIdsToOptionIds = array()) {
        $options = [];
        foreach ($facets as $facet) {
            if ($facet instanceof Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup) {
                /* @var Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup $facet */
                $options = array_merge($options, $this->getFacetConfig($facet->getFacetResults(), $facetIdsToOptionIds));
                break;
            }
            $key = 'property_values';
            switch ($facet->getFacetName()) {
                case 'price':
                    $min = $max = null;
                    $value = null;
                    if ($facet->isActive()) {
                        $min = $facet->getActiveMin();
                        $max = $facet->getActiveMax();
                        if ($max == 0){
                            $max = null;
                        }else {
                            $value = ["{$min}-{$max}"];
                        }

                    }
                    $options['discountedPrice'] = [
                        'value' => $value,
                        'type' => 'ranged',
                        'bounds' => true,
                        'label' => $facet->getLabel()
                    ];
                    break;
                case 'category':
                    if ($this->Request()->getControllerName() == 'search') {
                        $id = $label = null;
                        $params = $this->Request()->getParams();
                        if (isset($_REQUEST['c']) || isset($params['sCategory'])) {
                            $value = $this->getLowestActiveTreeItem($facet->getValues());
                            if ($value instanceof Shopware\Bundle\SearchBundle\FacetResult\TreeItem) {
                                $id = $value->getId();
                            }
                        }
                        $options['category']['value'] = $id;
                    }
                    break;
                case 'manufacturer':
                    $key = 'products_brand';
                case 'property':

                    if ($key != 'products_brand') {
                        $peek = reset($facet->getValues());
                        if ($peek && array_key_exists($peek->getId(), $facetIdsToOptionIds)) {
                            $optionId = $facetIdsToOptionIds[$peek->getId()];
                            $key = 'products_optionID_' . $optionId;//. '_' . $this->generateFacetName($facet->getLabel());
                        }
                    }
                    if (!array_key_exists($key, $options)) {
                        $options[$key] = ['label' => $facet->getLabel()];
                    }
                    $values = array();
                    foreach ($facet->getValues() as $value) {
                        /* @var Shopware\Bundle\SearchBundle\FacetResult\ValueListItem|Shopware\Bundle\SearchBundle\FacetResult\MediaListItem $value */
                        if ($value->isActive()) {
                            $values[] = $value->getLabel();//($key == 'products_brand' ? $value->getLabel() : (string) $value->getId());
                        }
                    }
                    $options[$key]['value'] = $values;
                    break;
            }
        }
        return $options;
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResult\TreeItem[] $values
     * @return null|Shopware\Bundle\SearchBundle\FacetResult\TreeItem
     */
    protected function getLowestActiveTreeItem($values) {
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
     * @param Shopware\Bundle\SearchBundle\FacetResultInterface[] $facets
     * @param \com\boxalino\p13n\api\thrift\Variant $variant
     * @return Shopware\Bundle\SearchBundle\FacetResultInterface[]
     */
    protected function updateFacetsWithResult($facets, $facetIdsToOptionIds) {
        $resultFacet = $this->Helper()->getFacets();
        foreach ($facets as $key => $facet) {
            if ($facet instanceof Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup) {
                /* @var Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup $facet */
                $facets[$key] = new Shopware\Bundle\SearchBundle\FacetResult\FacetResultGroup(
                    $this->updateFacetsWithResult($facet->getFacetResults(), $facetIdsToOptionIds),
                    $facet->getLabel(),
                    $facet->getFacetName(),
                    $facet->getAttributes(),
                    $facet->getTemplate()
                );
                continue;
            }
            switch ($facet->getFacetName()) {

                case 'price':
                    /* @var Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult $facet
                     * @var com\boxalino\p13n\api\thrift\FacetValue $FacetValue */
                    $priceRange = explode('-', $resultFacet->getPriceRanges()[0]);
                    $from = (float) $priceRange[0];
                    $to = (float) $priceRange[1];
                    $activeMin = $facet->getActiveMin();
                    if (isset($activeMin)) {
                        $activeMin = max($from, $activeMin);
                    }
                    $activeMax = $facet->getActiveMax();
                    if (isset($activeMax)) {
                        $activeMax = $activeMax == 0 ? $to : min($to, $activeMax);
                    }
                    $facets[$key] = new Shopware\Bundle\SearchBundle\FacetResult\RangeFacetResult(
                        $facet->getFacetName(),
                        $facet->isActive(),
                        $facet->getLabel(),
                        $from,
                        $to,
                        $activeMin,
                        $activeMax,
                        $facet->getMinFieldName(),
                        $facet->getMaxFieldName(),
                        $facet->getAttributes(),
                        $facet->getTemplate()
                    );
                    break;
                case 'category':
                    if ($this->Request()->getControllerName() == 'search') {
                        /* @var Shopware\Bundle\SearchBundle\FacetResult\TreeFacetResult $facet */
                        $fieldName = 'categories';
                        $updatedFacetValues = $this->updateTreeItemsWithFacetValue($facet->getValues(), $resultFacet);

                        if ($updatedFacetValues) {
                            $facets[$key] = new Shopware\Bundle\SearchBundle\FacetResult\TreeFacetResult(
                                $facet->getFacetName(),
                                $facet->getFieldName(),
                                $facet->isActive(),
                                $facet->getLabel(),
                                $updatedFacetValues,
                                $facet->getAttributes(),
                                $facet->getTemplate()
                            );
                        } else {
                            unset($facets[$key]);
                        }
                    }
                    break;
                case 'manufacturer':
                    $fieldName = 'products_brand';
                case 'property':
                    $values = $facet->getValues();
                    if(count($values)){
                        if ($facet->getFacetName() == 'property') {
                            $value = reset($values);
                            if(isset($facetIdsToOptionIds[$value->getId()])){
                                $optionId = $facetIdsToOptionIds[$value->getId()];
                            }else{
                                unset($facets[$key]);
                                break;
                            }
                            $fieldName = 'products_optionID_' . $optionId;// . '_' . $this->generateFacetName($facet->getLabel());
                        }
                    }else{
                        unset($facets[$key]);
                        break;
                    }

                    $responseValues = $this->useValuesAsKeys($resultFacet->getFacetValues($fieldName));
                    $valueList = [];
                    $nbValues = 0;


                    foreach ($values as $valueKey => $value){
                        $label = trim($value->getLabel());
                        if(isset($responseValues[$label])){
                            $nbValues++;
                            $hitCount = $resultFacet->getFacetValueCount($fieldName, $responseValues[$label]);
                            $active = $resultFacet->isFacetValueSelected($fieldName, trim($value->getLabel()));
                            if(!$active && strpos($fieldName, "optionID") !== false) {
                                try {
                                    $active = $resultFacet->isFacetValueSelected($fieldName, $value->getId());
                                } catch(\Exception $e) {

                                }
                            }
                            if($hitCount > 0){
                                $args = [];
                                $args[] = $value->getId();
                                $args[] = $value->getLabel() . ' (' . $hitCount . ')';
                                $args[] = $active;
                                if ($value instanceof Shopware\Bundle\SearchBundle\FacetResult\MediaListItem) {
                                    $args[] = $value->getMedia();
                                }
                                $args[] = $value->getAttributes();
                                if (!array_key_exists($valueKey, $valueList)) {
                                    $r = new ReflectionClass(get_class($value));
                                    $valueList[$valueKey] = $r->newInstanceArgs($args);
                                }
                            }
                        }
                    }
                    if ($nbValues > 0) {
                        usort($valueList, function($a, $b) {
                            $res = $b->isActive() - $a->isActive();
                            if ($res !== 0) return $res;

                            return strcmp($a->getLabel(), $b->getLabel());
                        });
                        $facetResultClass = get_class($facet);
                        $facets[$key] = new $facetResultClass(
                            $facet->getFacetName(),
                            $facet->isActive(),
                            $facet->getLabel(),
                            $valueList,
                            $facet->getFieldName(),
                            $facet->getAttributes(),
                            $facet->getTemplate()
                        );
                    } else {
                        unset($facets[$key]);
                    }
                    break;
                default:
                    Shopware()->PluginLogger()->debug("unrecognized facet name for facet: " . json_encode($facet));
                    break;
            }
        }
        return $facets;
    }

    /**
     * @param $array
     * @return array
     */
    public function useValuesAsKeys($array){

        return array_combine(array_keys(array_flip($array)),$array);
    }

    /**
     * @param Shopware\Bundle\SearchBundle\FacetResult\TreeItem[] $values
     * @param com\boxalino\p13n\api\thrift\FacetValue[] $FacetValues
     * @return Shopware\Bundle\SearchBundle\FacetResult\TreeItem[]
     */
    protected function updateTreeItemsWithFacetValue($values, $resultFacet) {
        /* @var Shopware\Bundle\SearchBundle\FacetResult\TreeItem $value */
        $finalVals = array();
        foreach ($values as $key => $value) {
            $id = (string) $value->getId();
            $label = $value->getLabel();
            $innerValues = $value->getValues();

            if (count($innerValues)) {
                $innerValues = $this->updateTreeItemsWithFacetValue($innerValues, $resultFacet);
            }

            $category = $resultFacet->getCategoryById($id);
            if ($category) {
                $label .= ' (' . $resultFacet->getCategoryValueCount($category) . ')';
            } else {
                if (sizeof($innerValues)==0) {
                    continue;
                }
            }

            $finalVals[$key] = new Shopware\Bundle\SearchBundle\FacetResult\TreeItem(
                $value->getId(),
                $label,
                $value->isActive(),
                $innerValues,
                $value->getAttributes()
            );
        }
        return $finalVals;
    }

    /**
     * @param Shopware\Bundle\SearchBundle\Criteria $criteria
     * @return array
     */
    public function getSortOrder(Shopware\Bundle\SearchBundle\Criteria $criteria, $default_sort = null, $listing = false) {
        /* @var Shopware\Bundle\SearchBundle\Sorting\Sorting $sort */
        $sort = current($criteria->getSortings());
        $dir = null;
        switch ($sort->getName()) {
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
                $field = 'products_releasedate';
                break;
            default:
                if ($listing == true) {
                    $default_sort = is_null($default_sort) ? $this->getDefaultSort() : $default_sort;
                    switch ($default_sort) {
                        case 1:
                            $field = 'products_releasedate';
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
                                $field = 'products_releasedate';
                                break 2;
                            }
                            break;
                    }
                }
                return array();
        }

        return array(
            'field' => $field,
            'reverse' => (is_null($dir) ? $sort->getDirection() == Shopware\Bundle\SearchBundle\SortingInterface::SORT_DESC : $dir)
        );
    }

    protected function getDefaultSort(){
        $db = Shopware()->Db();
        $sql = $db->select()
            ->from(array('c_e' => 's_core_config_elements', array('c_v.value')))
            ->join(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id')
            ->where("name = ?", "defaultListingSorting");
        $result = $db->fetchRow($sql);
        return isset($result) ? unserialize($result['value']) : null;

    }

    /**
     * @param $supplier
     * @return null
     */
    protected function getSupplierName($supplier) {
        $supplier = $this->get('dbal_connection')->fetchColumn(
            'SELECT name FROM s_articles_supplier WHERE id = :id',
            ['id' => $supplier]
        );

        if ($supplier) {
            return $supplier;
        }

        return null;
    }

    /**
     * @param int $categoryId
     * @return int|null
     */
    private function findStreamIdByCategoryId($categoryId)
    {
        $streamId = $this->get('dbal_connection')->fetchColumn(
            'SELECT stream_id FROM s_categories WHERE id = :id',
            ['id' => $categoryId]
        );

        if ($streamId) {
            return (int)$streamId;
        }

        return null;
    }

}