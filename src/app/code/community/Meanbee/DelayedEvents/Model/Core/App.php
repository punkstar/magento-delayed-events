<?php
/**
 * Class Meanbee_DelayedEvents_Model_Core_App
 */
class Meanbee_DelayedEvents_Model_Core_App extends Mage_Core_Model_App {

    /**
     * @param $eventName
     * @param $args
     *
     * @return $this
     */
    public function dispatchEvent($eventName, $args) {
        foreach ($this->_events as $area => $events) {
            if (!isset($events[$eventName])) {
                $eventConfig = $this->getConfig()->getEventConfig($area, $eventName);
                if (!$eventConfig) {
                    $this->_events[$area][$eventName] = false;
                    continue;
                }

                $observers = array();

                foreach ($eventConfig->observers->children() as $obsName => $obsConfig) {
                    $observers[$obsName] = array(
                        'type'   => (string)$obsConfig->type,
                        'model'  => $obsConfig->class ? (string)$obsConfig->class : $obsConfig->getClassName(),
                        'method' => (string)$obsConfig->method,
                        'args'   => (array)$obsConfig->args,

                        /*
                         * Look for the additional <delayed></delayed> tag we've added.
                         */
                        'delayed'  => (int)$obsConfig->delayed
                    );
                }

                $events[$eventName]['observers'] = $observers;
                $this->_events[$area][$eventName]['observers'] = $observers;
            }

            if (false === $events[$eventName]) {
                continue;
            } else {
                $event = new Varien_Event($args);
                $event->setName($eventName);
                $observer = new Varien_Event_Observer();
            }

            foreach ($events[$eventName]['observers'] as $obsName => $obs) {
                $observer->setData(array('event' => $event));
                Varien_Profiler::start('OBSERVER: ' . $obsName);

                switch ($obs['type']) {
                    case 'disabled':
                        break;
                    case 'object':
                    case 'model':
                        $method = $obs['method'];
                        $observer->addData($args);
                        $object = Mage::getModel($obs['model']);
                        $this->_callObserverMethod($object, $method, $observer, $obs['delayed']);
                        break;
                    default:
                        $method = $obs['method'];
                        $observer->addData($args);
                        $object = Mage::getSingleton($obs['model']);
                        $this->_callObserverMethod($object, $method, $observer, $obs['delayed']);
                        break;
                }

                Varien_Profiler::stop('OBSERVER: ' . $obsName);
            }
        }

        return $this;
    }

    /**
     * Performs non-existent observer method calls protection.
     *
     * Extended to support queuing of the job if $delayed is defined as 1.
     *
     * @param object $object
     * @param string $method
     * @param Varien_Event_Observer $observer
     * @param int    $delayed Should this be queued or executed immediately?
     * @return Mage_Core_Model_App
     * @throws Mage_Core_Exception
     */
    protected function _callObserverMethod($object, $method, $observer, $delayed = 0)
    {
        if (method_exists($object, $method)) {
            if ($delayed) {
                $job = Mage::getModel('meanbee_delayedevents/job')
                    ->setObject($object)
                    ->setMethod($method)
                    ->setObserver($observer);
                $job->enqueue();
            } else {
                $object->$method($observer);
            }
        } elseif (Mage::getIsDeveloperMode()) {
            Mage::throwException('Method "'.$method.'" is not defined in "'.get_class($object).'"');
        }
        return $this;
    }
}
