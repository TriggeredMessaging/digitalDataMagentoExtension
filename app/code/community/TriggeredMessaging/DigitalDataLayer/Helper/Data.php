<?php

class TriggeredMessaging_DigitalDataLayer_Helper_Data extends Mage_Core_Helper_Abstract
{

    const XML_PATH_ALL_PAGES_ENABLED                           =   'triggered_messaging/triggered_messaging_digital_data_layer_all_pages';
    const XML_PATH_ENABLED_PAGES_HANDLERS                      =   'triggered_messaging/triggered_messaging_digital_data_layer_pages_selection';
    const XML_PATH_ENABLED_PAGES_HANDLERS_EXTRA                =   'triggered_messaging/triggered_messaging_digital_data_layer_pages_extra';
    const XML_PATH_PRODUCT_LIST_LIMIT                          =   'triggered_messaging/triggered_messaging_digital_data_layer_prod_list_exposure';
    const XML_PATH_RELOAD_PRODUCT_ON_CATEGORY_LISTING          =   'triggered_messaging/triggered_messaging_enable_product_reload_on_category_listing';


    public function getConfigProductListLimit(){

        $configValue = Mage::getStoreConfig(self::XML_PATH_PRODUCT_LIST_LIMIT);
        if($configValue === ''){
            //If blank we want to expose all products, so set to a number the counter won't ever get to
            $configValue = -1;
        } else {
            //Else make sure it's an integer value so counter will hit the value
            $configValue = (int) $configValue;
        }

        return $configValue;
    }

    public function shouldReloadProductOnCategoryListing(){

        return  Mage::getStoreConfig(self::XML_PATH_RELOAD_PRODUCT_ON_CATEGORY_LISTING);
    }

    public function isAllPagesEnabled(){

        return  Mage::getStoreConfig(self::XML_PATH_ALL_PAGES_ENABLED);
    }

    public function getAllowedPagesHandlers(){
        $selectedPagesConfigValue = Mage::getStoreConfig(self::XML_PATH_ENABLED_PAGES_HANDLERS);
        $extraPagesConfigValue = Mage::getStoreConfig(self::XML_PATH_ENABLED_PAGES_HANDLERS_EXTRA);

        // multiselect handles selection
        $selectedHandlesArray = explode(',', $selectedPagesConfigValue);
        $selectedHandlesArray = array_map('trim',$selectedHandlesArray);

        // extra handles list
        $extraHandlesArray = preg_split('/\r\n|[\r\n]/', $extraPagesConfigValue);
        $extraHandlesArray = array_map('trim',$extraHandlesArray);

        $selectedHandlesArray =  array_filter(array_merge($selectedHandlesArray, $extraHandlesArray));

        return $selectedHandlesArray;
    }

}

?>