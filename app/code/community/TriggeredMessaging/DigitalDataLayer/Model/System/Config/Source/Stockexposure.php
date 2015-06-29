<?php

class TriggeredMessaging_DigitalDataLayer_Model_System_Config_Source_Stockexposure
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(array('label' => 'Don\'t expose stock', 'value' => '0'),
					array('label' => 'Only Expose In or Out of stock', 'value' => '1'),
					array('label' => 'Expose actual stock level', 'value' => '2'));
    }
}

?>