<?php
namespace watoki\stepper\cli;

use watoki\cli\commands\MultiCommand;
use watoki\factory\Factory;
use watoki\stepper\events\MigrationCompletedEvent;
use watoki\stepper\Migrater;
use watoki\stepper\Step;

class StepperCommand extends MultiCommand {

    /** @var Migrater */
    private $migrater;

    public function __construct(Step $first, $stateFile, Factory $factory = null) {
        parent::__construct($factory);

        $this->migrater = new Migrater($first, $this->readState($stateFile));
        $this->migrater->on(MigrationCompletedEvent::$CLASS, function (MigrationCompletedEvent $e) use ($stateFile) {
            file_put_contents($stateFile, $e->getNewState());
        });
    }

    private function readState($stateFile) {
        if (!file_exists($stateFile)) {
            return null;
        }
        return file_get_contents($stateFile);
    }

    /**
     * Migrates up from current Step to last available Step
     */
    public function doMigrate() {
        $this->migrater->migrate();
    }

    /**
     * Migrates up or down from current Step to given Step
     *
     * @param string $stepName Name of the Step class that should become the current Step
     * @throws \InvalidArgumentException If the class given by stepName does not exist
     */
    public function doMigrateTo($stepName) {
        if (!class_exists($stepName)) {
            throw new \InvalidArgumentException("Step class with name [$stepName] does not exist");
        }
        $this->migrater->migrate($stepName);
    }

}