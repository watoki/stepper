<?php
namespace watoki\steps;
 
class Migrater {

    private $namespace;

    private $stateFile;

    /**
     * @param string $namespace
     * @param string $stateFile
     */
    function __construct($namespace, $stateFile) {
        $this->namespace = $namespace;
        $this->stateFile = $stateFile;
    }

    public function migrate() {
        $class = $this->namespace . '\\' . 'Step1';

        if (!class_exists($class)) {
            return;
        }

        /** @var $step Step */
        $step = new $class;
        $step->up();

        file_put_contents($this->stateFile, '1');
    }

}
