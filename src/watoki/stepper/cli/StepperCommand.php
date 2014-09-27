<?php
namespace watoki\stepper\cli;

use watoki\cli\commands\DefaultCommand;
use watoki\cli\Console;
use watoki\factory\Factory;
use watoki\stepper\events\MigrateDownEvent;
use watoki\stepper\events\MigrateEvent;
use watoki\stepper\events\MigrateUpEvent;
use watoki\stepper\events\MigrationCompletedEvent;
use watoki\stepper\Migrater;
use watoki\stepper\Step;

class StepperCommand extends DefaultCommand {

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
     * Migrates up from current Step to given Step (defaults to last Step)
     *
     * @param string $to Name of the Step class that should become the current Step
     * @param Console $console [] <-
     */
    public function doExecute($to = null, Console $console) {
        $console->out->writeLine('Starting migration' . ($to ? ' to [' . $to . ']' : ''));

        $this->migrater->on(MigrateUpEvent::$CLASS, function (MigrateEvent $e) use ($console) {
            $console->out->writeLine('Migrating up [' . $e->getStepName() . ']');
        });
        $this->migrater->on(MigrateDownEvent::$CLASS, function (MigrateEvent $e) use ($console) {
            $console->out->writeLine('Migrating down [' . $e->getStepName() . ']');
        });

        $this->migrater->migrate($to);
    }

}