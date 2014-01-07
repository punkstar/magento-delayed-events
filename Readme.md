# Delayed Events with Magento

**Note: This is a proof of concept.**

## Background
Magento has a rich event system which allows developers to extend the core functionality of the product without needing to "hack the core".  There are a [ton of events](http://www.nicksays.co.uk/magento-events-cheat-sheet-1-8/) defined for the developer to inject their logic at run time in the right place, at the right time.

Magento events are great, but they are real-time.  Each event listener is executed, one after the other, all while blocking in the browser and [costing our clients money](http://blog.kissmetrics.com/loading-time/).  There are, of course, events that have to be executed immediately which benefit from the sequential nature of the current event system and have to be run immediately*.  There are also events, I'd bet, that don't need to happen in the request, at that exact moment in time.

So how about, when we're defining our event, we tell Magento whether this event should be executed immediately or it should be queued up to be executed at a later date?

\* - Like affecting the rest of the page, using request parameters, accessing the registry, etc.

## Installation
This extension has a dependency on [jkowens/magento-jobqueue](https://github.com/jkowens/magento-jobqueue).

Installation of the extension is handled via [modman](https://github.com/colinmollenhour/modman).

You can install the job queue extension and this one like so:

    modman
    modman clone git@github.com:jkowens/magento-jobqueue.git
    modman clone git@github.com:punkstar/magento-delayed-events.git
    modman deploy-all
    
Unfortunately, we need to override `Mage_Core_Model_App` to implement use this extension but Magento doesn't initialise this class with `Mage::getModel()`, so we need to modify all occurances of `new Mage_Core_Model_App()` in `app/Mage.php` with `new Meanbee_DelayedEvents_Model_Core_App()`.  The following diff might help:

	@@ -606,7 +605,7 @@
	     public static function app($code = '', $type = 'store', $options = array())
	     {
	         if (null === self::$_app) {
	-            self::$_app = new Mage_Core_Model_App();
	+            self::$_app = new Meanbee_DelayedEvents_Model_Core_App();
	             self::setRoot();
	             self::$_events = new Varien_Event_Collection();
	             self::_setIsInstalled($options);
	@@ -631,7 +630,7 @@
	     {
	         try {
	             self::setRoot();
	-            self::$_app     = new Mage_Core_Model_App();
	+            self::$_app     = new Meanbee_DelayedEvents_Model_Core_App();
	             self::_setIsInstalled($options);
	             self::_setConfigModel($options);
	 
	@@ -667,7 +666,7 @@
	             if (isset($options['edition'])) {
	                 self::$_currentEdition = $options['edition'];
	             }
	-            self::$_app    = new Mage_Core_Model_App();
	+            self::$_app    = new Meanbee_DelayedEvents_Model_Core_App();
	             if (isset($options['request'])) {
	                 self::$_app->setRequest($options['request']);
	             }


## Usage
To define an event as delayed, when you define your event add an `<delayed>1</delayed>` tag inside your observer.  For example:

	<config>
		...
	    <frontend>
	        <events>
	            <controller_action_postdispatch_cms_index_index>
	                <observers>
	                    <meanbee_delayedeventsexample>
	                        <type>singleton</type>
	                        <class>Meanbee_DelayedEventsExample_Model_Observer</class>
	                        <method>controllerActionPostdispatchCmsIndexIndex</method>
	                        <delayed>1</delayed>
	                    </meanbee_delayedeventsexample>
	                </observers>
	            </controller_action_postdispatch_cms_index_index>
	        </events>
	    </frontend>
	    ...
	</config>
	
Checkout the example in the `Meanbee_DelayedEventsExample` extension.
