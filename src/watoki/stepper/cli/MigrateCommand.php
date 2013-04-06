<?php
namespace watoki\stepper\cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use watoki\factory\Factory;
use watoki\stepper\Migrater;

class MigrateCommand extends Command {

    /**
     * @var \watoki\factory\Factory
     */
    private $factory;

    private $cwd;

    public function __construct(Factory $factory, $cwd) {
        parent::__construct('migrate');
        $this->factory = $factory;
        $this->cwd = $cwd;
    }

    protected function configure() {
        $this->setDescription('Migrate from the current to another step.');
        $this->setHelp("Performs all steps between the current and the target step.\n"
            . "Options marked with [c] are read from the config file.");

        $this->addArgument('target', InputArgument::OPTIONAL,
            'Number of the target step (last step if omitted)', null);
        $this->addOption('config', 'c', InputArgument::OPTIONAL,
            'Path to config file', 'config/stepper.json');
        $this->addConfigurableOption('bootstrap', 'Path to bootstrap file');
        $this->addConfigurableOption('namespace', 'Namespace of StepX classes');
        $this->addConfigurableOption('state', 'Path to file containing current step');
    }

    private function addConfigurableOption($name, $description) {
        $this->addOption($name, null, InputArgument::OPTIONAL, '[c] ' . $description);
    }

    private function getConfigurableOption($name, InputInterface $input) {
        return $input->getOption($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        require_once $this->getConfigurableOption('bootstrap', $input);

        /** @var $migrater Migrater */
        $migrater = $this->factory->getInstance(Migrater::$CLASS, array(
            'namespace' => $this->getConfigurableOption('namespace', $input),
            'stateFile' => $this->getConfigurableOption('state', $input)
        ));

        $migrater->migrate($input->getArgument('target'));
    }

}