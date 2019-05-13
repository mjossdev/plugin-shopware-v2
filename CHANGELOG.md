# CHANGELOG Boxalino Shopware v2 plugin 

All further changes to the plugin will be added to the changelog.
On every plugin update - please check the file and what needs to be tested on your system.

If you have any question, just contact us at support@boxalino.com


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