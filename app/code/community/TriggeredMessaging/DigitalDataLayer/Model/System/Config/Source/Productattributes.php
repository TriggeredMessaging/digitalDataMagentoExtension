<?php

class TriggeredMessaging_DigitalDataLayer_Model_System_Config_Source_Productattributes
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributes = Mage::getModel('catalog/product')->getAttributes();
        $attributeArray = array(array('label' => 'none', 'value' => '0'));

        foreach ($attributes as $a) {

            foreach ($a->getEntityType()->getAttributeCodes() as $attrCode) {
                $attribute_details = Mage::getSingleton("eav/config")->getAttribute('catalog_product', $attrCode);
                if ($attribute_details->getData('is_user_defined')) {
                    array_push($attributeArray, array('label' => $attrCode, 'value' => $attrCode));
                }
            }
            break;
        }
        array_push($attributeArray, array('label' => 'weight', 'value' => 'weight'));
        return $attributeArray;
    }
}

?>
