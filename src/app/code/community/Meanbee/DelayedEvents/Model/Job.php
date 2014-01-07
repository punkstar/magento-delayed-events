<?php
/**
 * Class Meanbee_DelayedEvents_Model_Job
 *
 * @method setObject()
 * @method getObject()
 * @method setMethod()
 * @method getMethod()
 * @method setObserver()
 * @method getObserver()
 */
class Meanbee_DelayedEvents_Model_Job extends Jowens_JobQueue_Model_Job_Abstract
{
    /**
     * Execute the original observer method.
     */
    public function perform() {
        $object = $this->getObject();
        $method = $this->getMethod();
        $observer = $this->getObserver();

        $object->$method($observer);
    }
}
