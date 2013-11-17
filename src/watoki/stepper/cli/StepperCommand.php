<?php
namespace watoki\stepper\cli;

use watoki\cli\CliApplication;
use watoki\cli\commands\MultiCommand;
use watoki\factory\Factory;
use watoki\stepper\events\MigrateDownEvent;
use watoki\stepper\events\MigrateEvent;
use watoki\stepper\events\MigrateUpEvent;
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

    public function execute(CliApplication $app, array $arguments) {
        $this->migrater->on(MigrateUpEvent::$CLASS, function (MigrateEvent $e) use ($app) {
            $app->getStdWriter()->writeLine('Migrating up [' . $e->getStepName() . ']');
        });
        $this->migrater->on(MigrateDownEvent::$CLASS, function (MigrateEvent $e) use ($app) {
            $app->getStdWriter()->writeLine('Migrating down [' . $e->getStepName() . ']');
        });

        parent::execute($app, $arguments);
    }

    /**
     * Migrates up from current Step to last available Step
     */
    public function doMigrate() {
        $this->app->getStdWriter()->writeLine('Starting migration');
        $this->migrater->migrate();
    }

    /**
     * Migrates up or down from current Step to given Step
     *
     * @param string $stepName Name of the Step class that should become the current Step
     */
    public function doMigrateTo($stepName) {
        $this->app->getStdWriter()->writeLine('Starting migration to [' . $stepName . ']');
        $this->migrater->migrate($stepName);
    }

}