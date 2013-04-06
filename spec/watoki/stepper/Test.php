<?php
namespace spec\watoki\stepper;

abstract class Test extends \PHPUnit_Framework_TestCase {

    public $undos = array();

    protected function tearDown() {
        foreach ($this->undos as $undo) {
            $undo();
        }
        parent::tearDown();
    }

}

/**
 * @property Test test
 */
abstract class Test_Given {

    public function theFolder($name) {
        $folder = __DIR__ . '/' . $name;

        $this->cleanUp($folder);
        mkdir($folder);

        $that = $this;
        $this->test->undos[] = function () use ($that, $folder) {
            $that->cleanUp($folder);
        };

        return $folder;
    }

    public function cleanUp($folder) {
        if (!file_exists($folder)) {
            return true;
        }

        do {
            $items = glob(rtrim($folder, '/') . '/' . '*');
            foreach ($items as $item) {
                is_dir($item) ? $this->cleanUp($item) : unlink($item);
            }
        } while ($items);

        return rmdir($folder);
    }

    public function theFile($name) {
        return $this->theFile_WithContent($name, '');
    }

    public function theFile_WithContent($name, $content) {
        $file = __DIR__ . '/' . $name;

        file_put_contents($file, utf8_encode($content));

        return $file;
    }
}