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
        $steps = $this->collectSteps();

        $toIndex = count($steps) - 1;
        $fromIndex = -1;
        foreach ($steps as $i => $step) {
            if (get_class($step) == $to) {
                $toIndex = $i;
            }
            if (get_class($step) == $this->state) {
                $fromIndex = $i;
            }
        }

        if ($this->state && $fromIndex < 0) {
            throw new \Exception('Cannot migrate. Invalid state: [' . $this->state . ']');
        }

        if ($toIndex == $fromIndex) {
            return;
        }

        if ($toIndex > $fromIndex) {
            for ($i = $fromIndex + 1; $i <= $toIndex; $i++) {
                $steps[$i]->up();
            }
        } else if ($toIndex < $fromIndex) {
            for ($i = $fromIndex; $i > $toIndex; $i--) {
                if (!$steps[$i]->canBeUndone()) {
                    throw new \Exception('Cannot migrate down. Step [' . get_class($steps[$i]) . '] cannot be undone.');
                }
            }
            for ($i = $fromIndex; $i > $toIndex; $i--) {
                $steps[$i]->down();
            }
        }

        $this->dispatcher->fire(new MigrationCompletedEvent($steps[$toIndex]));
    }

    /**
     * @return array|Step[]
     */
    private function collectSteps() {
        $step = $this->first;
        $steps = array();
        while ($step) {
            $steps[] = $step;
            $step = $step->next();
        }
        return $steps;
    }

}
