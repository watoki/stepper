<?php
namespace watoki\stepper\events;

use watoki\stepper\Step;

abstract class MigrateEvent implements MigrationEvent {

    private $step;

    function __construct(Step $step) {
        $this->step = $step;
    }

    public function getStepName() {
        return get_class($this->step);
    }
}