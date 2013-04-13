<?php
namespace watoki\stepper\cli;

use Symfony\Component\Console\Application;
use watoki\factory\Factory;
use watoki\stepper\Migrater;

class Stepper extends Application {

    private $cwd;

    public function __construct($cwd) {
        parent::__construct('Stepper', '0.3');
        $this->cwd = $cwd;

        $factory = new Factory();

        $this->addCommands(array(
            new MigrateCommand($cwd)
        ));
    }

}