<?php
class Meanbee_AsyncEvents_Model_Core_App extends Mage_Core_Model_App {
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
                        $this->_callObserverMethod($object, $method, $observer);
                        break;
                    default:
                        $method = $obs['method'];
                        $observer->addData($args);
                        $object = Mage::getSingleton($obs['model']);
                        $this->_callObserverMethod($object, $method, $observer);
                        break;
                }
                
                Varien_Profiler::stop('OBSERVER: ' . $obsName);
            }
        }

        return $this;
    }
}
