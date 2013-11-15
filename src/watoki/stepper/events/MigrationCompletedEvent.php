<?php
namespace watoki\stepper\events;
 
use watoki\stepper\Step;

/**
 * Fired after a successful migration
 */
class MigrationCompletedEvent implements MigrationEvent {

    public static $CLASS = __CLASS__;

    /** @var \watoki\stepper\Step */
    private $current;

    public function __construct(Step $current) {
        $this->current = $current;
    }

    /**
     * @return string The class name of the last applied Step
     */
    public function getNewState() {
        return get_class($this->current);
    }

}
 