# CHANGELOG Boxalino Shopware v2 plugin 

All further changes to the plugin will be added to the changelog.
On every plugin update - please check the file and what needs to be tested on your system.

If you have any question, just contact us at support@boxalino.com


### v3.1 
* *setup version* : 3.1
* *requirements* : review your ajax search (_autocomplete_) integration (templates).

Use the "plugin/swSearch/onGetRtuxApiAcRenderer" to set a different API renderer **from your project** for the ajax search event.
(ex: https://github.com/boxalino/plugin-shopware-v2/blob/master/Frontend/Boxalino/Views/responsive/frontend/_resources/javascript/boxalinoApiAcRenderer.js#L205)

Use the "plugin/swSearch/onGetRtuxApiAcFilters" to set a different API renderer **from your project** for the ajax search event.
(ex: https://github.com/boxalino/plugin-shopware-v2/blob/master/Frontend/Boxalino/Views/responsive/frontend/_resources/javascript/boxalinoApiAcRenderer.js#L219)

As a narrative structure, the recommended one for Shopware6 can be used:
1. [Layout Blocks](https://github.com/boxalino/rtux-integration-shopware/wiki/Autocomplete-(JS)#layout-blocks-json)
2. [Narrative](https://github.com/boxalino/rtux-integration-shopware/wiki/Autocomplete-(JS)#narrative-layout-json)
3. [Intelligence Admin guidelines](https://github.com/boxalino/rtux-integration-shopware/wiki/Boxalino-Intelligence-Admin)

### v3.0
* *setup version* : 3.0
* *requirements* : the templates must be updated to include the required HTML attributes as documented [in the JS Tracker API HTML Requirements](https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/8716641/JS+Tracker+API#Narrative-HTML-markup-requirements)

Template updates must be applied on :
  * for category listing: 
     1. (if ajax load is disabled) themes/Frontend/Bare/frontend/listing/index.tpl (line 63)
        `<div class="listing--container {if $bx_request_uuid}bx-narrative" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-name="products-list" data-bx-narrative-group-by="{$bx_request_groupby}{/if}">`
     2. themes/Frontend/Bare/frontend/listing/product-box/box-basic.tpl & box-emotion.tpl
        `<div class="product--box box--{$productBoxLayout} bx-narrative-item" data-bx-item-id="{$sArticle.articleID}"`
     3. (if ajax load is enabled) themes/Frontend/Bare/frontend/listing/product-box/box-basic.tpl & box-emotion.tpl (wrap the template in this div)
        `{if $bx_request_uuid} <div class="bx-narrative" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-name="products-list" data-bx-narrative-group-by="{$bx_request_groupby}">{/if}`
        `{if $bx_request_uuid}</div>{/if}`
  * for autocomplete:
    1. themes/Frontend/Bare/frontend/search/ajax.tpl (line 13)
        ` <ul class="results--list bx-narrative" {if $bx_request_uuid} data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-name="products-list"{/if}
                         {if $bx_request_groupby} data-bx-narrative-group-by="{$bx_request_groupby}"{/if}>`
    2. themes/Frontend/Bare/frontend/search/ajax.tpl (line 19)
        `<li class="list--entry block-group result--item bx-narrative-item" data-bx-item-id="{$search_result.articleID}">`
  * for product sliders:
    1. themes/Frontend/Bare/frontend/_includes/product_slider.tpl (line 68)
        `<div class="product-slider--container {if $bx_request_uuid}bx-narrative" data-bx-variant-uuid="{$bx_request_uuid}" data-bx-narrative-name="products-list"
                     data-bx-narrative-group-by="{$bx_request_groupby}{/if}">`
  



### v1.4.2 - 2020-02-25

##### 1. Fixes for upgrade from v1.6.29
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/c9202d59bffe1d17cfaf91391158c75d0e6a7a22

##### 2. Update on customer data export 
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/4df7075aae7d4ee654b1ade9c27cbf66af2e20eb

##### 3. Fixes for autocomplete blog SEO URL
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/5194b1653f35b46152996bb10274effa5e59b411


### v1.4.1 - 2019-12-11
* *setup version* : 1.6.34
* *post-deploy tests* : test navigation sorting options, requests that require HTTPS

##### 1. Disabled all plugin features by default
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/e1d24c2213ae03c687a599a98efe06cbcea1fd81

##### 2. Extended the logic for checking if your server is secure
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/1e215a69270be36c9bbff0308a8ab1577b774f95

##### 3. Allowing duplicate products on PDP widgets
* *description* : On the PDP view, if configured, the products returned can duplicate among different type of sections (complimentary, similar, related)
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/880115252b68d53763e17200658548647148826f


### v1.4.0 - 2019-08-05
* *setup version* : 1.6.31
* *post-deploy tests* : test search, navigation, filters and narratives (product finder, etc)

##### 1. Added Narrative Bundle component
* *description* : All narrative logic exported in Bundle (prototype); a facet bundle has been created as well;
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/1dd3c4468f1321d545e8f330e8211d65fd19a50d

##### 2. Added Option to sort facet values by system definition
* *description* : If configured in the Boxalino Intelligence Admin, the facet options will follow the rules defined in the Shopware store.
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/d5d5577d2e819565d11017a1f0c72f26d4d9c582

##### 3. BxClient update to show content on page as JSON
* *description* : For developers - use &boxalino_response=true as an URL parameter to see the content returned by the SOLR index as JSON.


### v1.3.5 - 2019-07-10
* *setup version* : 1.6.30
* *post-deploy tests* : run <store>/backend/boxalino_export/check to see the contents of the boxalino_exports table

##### 1. Exporter tracker per account and type
* *description* : The tracking boxalino_exports table has been updated to follow the account, export type, run date and status of each export process.
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/34bf81d299f15c8ca4bf784a53ec1b91a2e3c80b
##### 2. Backend action to check export status
* *description* : On each export cron/manual job, the boxalino_exports logs relevant information (account, export type, export date and status - fail, success or processing).
 The view is accessible via URL: <store>/backend/boxalino_export/check
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/34bf81d299f15c8ca4bf784a53ec1b91a2e3c80b
##### 3. Setting filters on emotion sliders
* *description* : If you need custom logic for the product slider, define the fields in the "Additional Slider Filters" row in your emotion. Use "category_id-X" to set/change the category filter, and for other fields - set "bxs_" prefix for the boxalino field.
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/13bb463173fcd74ce2411ff1ae9f4522fb9a5d2b

### v1.3.4 - 2019-06-20
* *post-deploy steps* : clear cache; update the Transactions export mode to "Full" overnight;
* *post-deploy tests* : product stream pages, listing sorting, ajax listing, navigation

##### 1. Configurations for Connection Timeout 
* *description* : Added 2 new configurable properties for connection timeout. If exceeded - the fallback view is triggered.
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/3032233ccd9debc5ff66f72e27bb78085bbe247e

##### 2. Product Finder JS updates
* *description* : IE compatibility on variant switcher; history reload strategy
* *commits* :
https://github.com/boxalino/plugin-shopware-v2/commit/e238f4bbb1a236d73f2c42daee639a8472befd4e
https://github.com/boxalino/plugin-shopware-v2/commit/24e39924b9c50ea9e647ceab715fab0f41868b3c
https://github.com/boxalino/plugin-shopware-v2/commit/96ba7abeb5f18232a29198265aa1d991162462b9

##### 3. Transaction Addresses export 
* *description* : Updates on transactions export
* *commits* :
https://github.com/boxalino/plugin-shopware-v2/commit/32f8d3feeebebbee181a2a9188cb70455b92832f

##### 4. Added Search Bundle component
* *description* : Allows custom sorting definitions
* *commits* :
https://github.com/boxalino/plugin-shopware-v2/commit/7c3c94ea99e214349e321f1942da4324d8a1e4df

### v1.3.3 - 2019-05-31
* *post-deploy steps* : clear cache
* *setup version* : 1.6.29

##### 1. Data Exporter - hook event to update the service
* *description* : Added a Enlight_Bootstrap_BeforeSetResource_boxalino_intelligence.service_exporter event so that the DataExporter class can be changed/extended with custom logic.
* *commits* : 
https://github.com/boxalino/plugin-shopware-v2/commit/7a9d6e910ad1036c24b09080f9ea6bcce52f6c22

##### 2. Product Finder notification message
* *description* : Update the productfinder template with a notification bar; 
* *commits* :
https://github.com/boxalino/plugin-shopware-v2/commit/665f29319d802571d531fffbecd3b3dd3c7c598c

### v1.3.2 - 2019-05-29
* *post-deploy steps* : in the plugin configuration, set "Exporter - Transactions - Mode" to *Full* for 1 day. Switch back to *Incremental* after a full data sync.

##### 1. Data Exporter - order status updates
* *description* : Updates on transactions export so that the order status would not be overwritten by detail status on CSV data save. 
* *commits* :  
https://github.com/boxalino/plugin-shopware-v2/commit/d1705bcb0a99fe1866e2a0275aba39ea1d3546fe

##### 2. Product Finder - encoded URL facet parsing update
* *description* : Fix for facets JS to be able to parse _encoded_ URL with array values.
* *commits* :  
https://github.com/boxalino/plugin-shopware-v2/commit/423aa68c613b8891379edc4002b41d610d0f6824


### v1.3.1 - 2019-05-24
##### 1. Data Exporter - export additional tables
* *setup version* : 1.6.28
* *description* : Admin configuration fields to define entity-related tables which have to be exported to Boxalino. The table names must exist within your store database and they are exported as is. 
* *configuration*: "Exporter - Products - Additional Tables", "Exporter - Customers - Additional Tables", "Exporter - Transactions - Additional Tables"
* *commits* :  
https://github.com/boxalino/plugin-shopware-v2/commit/ad705039b05553f050174c4bb89e0217b0f89763

### v1.3.0 - 2019-05-09
* *post-deploy steps* : clear cache, re-create theme

##### 1. Product Finder JS updates 
###### 1. Reload view on JSON parse error
* *description* : Depending on the cache configuration, the JSON parsing via JS seems to be affected.
For the purpose of avoiding the fallback strategy to the user, the view reloads once. 
* *commits* :  
https://github.com/boxalino/plugin-shopware-v2/commit/07aaa311ce11c702938b705908c8fcccd8dbc125

###### 2. Fallback strategy 
* *description* : A fallback strategy has been created for when there are JS errors with the finder 
* *extensibility* : Check the block "frontend_product_finder_script_templates_fallback" from the main template.
* *commits* :  
https://github.com/boxalino/plugin-shopware-v2/commit/0d7430c37c6c919f3f8fb2fdf8d80fd4a823eae0

##### 2. Product View - similar products recommendation updates
* *description* : The system-marked related products on the PDP widget are now as well retrieved via Boxalino response.
* *commits*: https://github.com/boxalino/plugin-shopware-v2/commit/514fb5e04e959dd2b11bc66a906a90e45410e4d6

##### 3. Exporter updates
###### 1. Single-account full-export action
* *description* : For multi-account setups, it is possible to run a full-export targeting the accounts needed. 
Sample URL: <store>backend/boxalino_export/full?account=account1,account2
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/41a37e0a08f94cacab5bc46db64fd9fcbdf2624e

###### 1. Update export table for errors as well
* *description* : Until now, a failed export would not mark the end of the process (for debugging purposes). It has been decided to update the flow
by allowing the exporter to try the export again.
* *commits* : https://github.com/boxalino/plugin-shopware-v2/commit/adbf2b5780d95ebda63ee4a1526ac27092ea1d91