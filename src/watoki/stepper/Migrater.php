<?php
namespace watoki\stepper;
 
use watoki\smokey\EventDispatcher;
use watoki\stepper\events\MigrationCompletedEvent;

class Migrater {

    static $CLASS = __CLASS__;

    /** @var Step */
    private $first;

    /** @var string */
    private $state;

    /** @var null|EventDispatcher */
    private $dispatcher;

    /**
     * @param Step $first First step in the chained list of steps
     * @param string $state Class name of last applied Step
     */
    function __construct(Step $first, $state) {
        $this->first = $first;
        $this->state = $state;
    }

    /**
     * @see \watoki\stepper\events\MigrationEvent
     * @param string $event Name of event class (must implement MigrationEvent)
     * @param callable $listener
     */
    public function on($event, $listener) {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventDispatcher();
        }
        $this->dispatcher->addListener($event, $listener);
    }

    /**
     * @param string|null $to Name of the step class to migrate to (defaults to last step)
     * @throws \Exception If
     * @return void
     */
    public function migrate($to = null) {
        $current = $this->first;

        do {
            $current->up();
            $last = $current;
            $current = $current->next();
        } while ($current);

        $this->dispatcher->fire(new MigrationCompletedEvent($last));
    }

}
