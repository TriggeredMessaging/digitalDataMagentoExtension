<?php

class TriggeredMessaging_DigitalDataLayer_Model_System_Config_Source_Pages
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'cms_index_index', 'label' => Mage::helper('adminhtml')->__('Home page')),
            array('value' => 'catalog_product_view', 'label' => Mage::helper('adminhtml')->__('Product page')),
            array('value' => 'catalog_product_compare_index', 'label' => Mage::helper('adminhtml')->__('Products Compare page')),
            array('value' => 'catalog_category_default', 'label' => Mage::helper('adminhtml')->__('Category page - simple')),
            array('value' => 'catalog_category_layered', 'label' => Mage::helper('adminhtml')->__('Category page - with filters')),
            array('value' => 'catalogsearch_result_index', 'label' => Mage::helper('adminhtml')->__('Search page - simple')),
            array('value' => 'catalogsearch_advanced_result', 'label' => Mage::helper('adminhtml')->__('Search page - advanced')),
            array('value' => 'checkout_cart_index', 'label' => Mage::helper('adminhtml')->__('Basket page')),
            array('value' => 'checkout_onepage_index', 'label' => Mage::helper('adminhtml')->__('Checkout page')),
            array('value' => 'checkout_onepage_success', 'label' => Mage::helper('adminhtml')->__('Checkout success page')),
            array('value' => 'cms_page', 'label' => Mage::helper('adminhtml')->__('General CMS page')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $output = array();
        foreach($this->toOptionArray() as $item){
            $output[$item['value']] = $item['label'];
        }

        return $output;
    }
}
?>