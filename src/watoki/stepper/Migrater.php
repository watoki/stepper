<?php
namespace watoki\stepper;
 
use watoki\smokey\EventDispatcher;
use watoki\stepper\events\MigrateDownEvent;
use watoki\stepper\events\MigrateUpEvent;
use watoki\stepper\events\MigrationCompletedEvent;

class Migrater {

    static $CLASS = __CLASS__;

    /** @var Step */
    private $first;

    /** @var null|string */
    private $state;

    /** @var null|EventDispatcher */
    private $dispatcher;

    /**
     * @param Step $first First step in the chained list of steps
     * @param null|string $state Class name of last applied Step
     */
    function __construct(Step $first, $state = null) {
        $this->first = $first;
        $this->state = $state;

        $this->dispatcher = new EventDispatcher();
    }

    /**
     * @see \watoki\stepper\events\MigrationEvent
     * @param string $event Name of event class (must implement MigrationEvent)
     * @param callable $listener
     */
    public function on($event, $listener) {
        $this->dispatcher->addListener($event, $listener);
    }

    /**
     * @param string|null $to Name of the step class to migrate to (defaults to last step)
     * @throws \Exception If
     * @return void
     */
    public function migrate($to = null) {
        $steps = $this->collectSteps();

        $fromIndex = $this->findFromIndex($steps);
        $toIndex = $this->findToIndex($to, $steps);

        if ($toIndex > $fromIndex) {
            $this->stepUp($fromIndex, $toIndex, $steps);
        } else if ($toIndex < $fromIndex) {
            $this->stepDown($fromIndex, $toIndex, $steps);
        } else {
            return;
        }

        $this->dispatcher->fire(new MigrationCompletedEvent($steps[$toIndex]));
    }

    public function migrateDown() {
        $steps = $this->collectSteps();

        $fromIndex = $this->findFromIndex($steps);
        $toIndex = count($steps) - 2;

        $this->stepDown($fromIndex, $toIndex, $steps);

        $this->dispatcher->fire(new MigrationCompletedEvent($steps[$toIndex]));
    }

	public function migrateAllDown() {
		$steps = $this->collectSteps();
		$steps = array_reverse( $steps );

		/** @var Step[] $steps */
		foreach ( $steps as $step ) {
			if (!$step->canBeUndone()) {
				throw new \Exception('Cannot migrate down. Step [' . get_class($step) . '] cannot be undone.');
			}
			$this->dispatcher->fire(new MigrateDownEvent($step));
			$step->down();
		}
	}

	private function collectSteps() {
        $step = $this->first;
        $steps = array();
        while ($step) {
            $steps[] = $step;
            $next = $step->next();

            if (in_array($next, $steps)) {
                throw new \Exception('Circular step detected: [' . get_class($step) . ']');
            }
            $step = $next;
        }
        return $steps;
    }

    private function findFromIndex($steps) {
        foreach ($steps as $index => $step) {
            if (get_class($step) == $this->state) {
                return $index;
            }
        }

        if ($this->state) {
            throw new \Exception('Cannot migrate. Invalid state: [' . $this->state . ']');
        }

        return -1;
    }

    private function findToIndex($to, $steps) {
        foreach ($steps as $index => $step) {
            if (get_class($step) == $to) {
                return $index;
            }
        }

        if ($to) {
            throw new \Exception('Cannot migrate. Invalid target: [' . $to . ']');
        }

        return count($steps) - 1;
    }

    /**
     * @param $fromIndex
     * @param $toIndex
     * @param Step[] $steps
     */
    private function stepUp($fromIndex, $toIndex, $steps) {
        for ($i = $fromIndex + 1; $i <= $toIndex; $i++) {
            $this->dispatcher->fire(new MigrateUpEvent($steps[$i]));
            $steps[$i]->up();
        }
    }

    /**
     * @param $fromIndex
     * @param $toIndex
     * @param Step[] $steps
     * @throws \Exception
     */
    private function stepDown($fromIndex, $toIndex, $steps) {
        for ($i = $fromIndex; $i > $toIndex; $i--) {
            if (!$steps[$i]->canBeUndone()) {
                throw new \Exception('Cannot migrate down. Step [' . get_class($steps[$i]) . '] cannot be undone.');
            }
        }
        for ($i = $fromIndex; $i > $toIndex; $i--) {
            $this->dispatcher->fire(new MigrateDownEvent($steps[$i]));
            $steps[$i]->down();
        }
    }

}
