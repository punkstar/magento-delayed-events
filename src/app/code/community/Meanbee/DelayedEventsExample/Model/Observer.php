<?php
class Meanbee_DelayedEventsExample_Model_Observer
{
    public function controllerActionPostdispatchCmsIndexIndex(Varien_Event_Observer $observer)
    {
        Mage::log(
            __CLASS__ . " " . __METHOD__ . ": Hello World!",
            Zend_Log::DEBUG,
            'meanbee_delayedeventsexample.log',
            true
        );
    }
}
