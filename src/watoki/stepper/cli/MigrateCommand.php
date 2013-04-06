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
        $this->factory = $factory;
        $this->cwd = $cwd;

        parent::__construct('migrate');
    }

    protected function configure() {
        $this->setDescription('Migrate from the current to another step.');
        $this->setHelp("Performs all steps between the current and the target step.\n"
            . "Options marked with [c] are read from the config file.");

        $this->addArgument('target', InputArgument::OPTIONAL,
            'Number of the target step (last step if omitted)', null);
        $this->addOption('config', 'c', InputArgument::OPTIONAL,
            'Path to config file', $this->cwd . '/config/stepper.json');
        $this->addConfigurableOption('bootstrap', 'Path to bootstrap file');
        $this->addConfigurableOption('namespace', 'Namespace of StepX classes');
        $this->addConfigurableOption('state', 'Path to file containing current step');
    }

    private function addConfigurableOption($name, $description) {
        $this->addOption($name, null, InputArgument::OPTIONAL, '[c] ' . $description);
    }

    private function getConfigurableOption($name, InputInterface $input) {
        $option = $input->getOption($name);

        if ($option !== null) {
            return $option;
        }

        $configFile = $input->getOption('config');
        if ($configFile && file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $config = json_decode($content, true);

            if (!$config) {
                throw new \Exception("Could not parse [$configFile]: " . $content);
            }

            if (array_key_exists($name, $config)) {
                return $config[$name];
            }
        }

        throw new \Exception("Option [$name] is missing.");
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