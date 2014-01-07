<?php
require_once "app/Mage.php"; Mage::app();

$worker = Mage::getModel('jobqueue/worker');

while (true) {
    $worker->executeJobs();
    sleep(1);
}

