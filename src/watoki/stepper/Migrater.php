<?php
namespace watoki\stepper;
 
use watoki\factory\Factory;

class Migrater {

    static $CLASS = __CLASS__;

    /**
     * @var \watoki\factory\Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $stateFile;

    /**
     * @param \watoki\factory\Factory $factory
     * @param string $namespace
     * @param string $stateFile
     */
    function __construct(Factory $factory, $namespace, $stateFile) {
        $this->factory = $factory;
        $this->namespace = $namespace;
        $this->stateFile = $stateFile;
    }

    public function migrate($to = null) {
        $start = $this->getStart();

        if ($start === $to) {
            return;
        }

        $this->migrateRange($start, $to);
    }

    /**
     * @return int
     */
    public function getStart() {
        if (file_exists($this->stateFile)) {
            return intval(file_get_contents($this->stateFile));
        }
        return 0;
    }

    private function migrateRange($from, $to) {
        if ($to == null || $to > $from) {
            $num = $this->migrateUp($from, $to);
        } else {
            $num = $this->migrateDown($from, $to);
        }

        if ($num != $from) {
            file_put_contents($this->stateFile, $num);
        }
    }

    private function migrateUp($from, $to) {
        while (true) {
            $class = $this->getStepClass($from + 1);

            if (!class_exists($class) || $from === $to) {
                break;
            }

            $this->createStep($class)->up();

            $from++;
        }
        return $from;
    }

    /**
     * @param $class
     * @return Step
     */
    private function createStep($class) {
        return $this->factory->getInstance($class);
    }

    private function migrateDown($from, $to) {
        while (true) {
            $class = $this->getStepClass($from);

            if (!class_exists($class) || $from === $to) {
                break;
            }

            $this->createStep($class)->down();

            $from--;
        }
        return $from;
    }

    /**
     * @param $num
     * @return string
     */
    private function getStepClass($num) {
        return $this->namespace . '\\' . 'Step' . $num;
    }

}
