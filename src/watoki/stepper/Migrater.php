<?php
namespace watoki\stepper;
 
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

    public function migrate($to = null) {
        $start = 0;
        if (file_exists($this->stateFile)) {
            $start = intval(file_get_contents($this->stateFile));
        }

        if ($start === $to) {
            return;
        }

        $this->migrateRange($start, $to);
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

            /** @var $step Step */
            $step = new $class;
            $step->up();

            $from++;
        }
        return $from;
    }

    private function migrateDown($from, $to) {
        while (true) {
            $class = $this->getStepClass($from);

            if (!class_exists($class) || $from === $to) {
                break;
            }

            /** @var $step Step */
            $step = new $class;
            $step->down();

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
