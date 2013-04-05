<?php
namespace spec\watoki\steps;

use watoki\steps\Migrater;

/**
 * @property StepsTest_Given given
 * @property StepsTest_When when
 * @property StepsTest_Then then
 */
class StepsTest extends \PHPUnit_Framework_TestCase {

    public $undos = array();

    public function testNoSteps() {
        $this->given->theStepsContainingFolder('stepstestnone');

        $this->when->iRunTheScript();

        $this->then->theOutputFileShouldContain('');
        $this->then->theStateFileShouldNotExist();
    }

    public function testFirstStep() {
        $this->given->theStepsContainingFolder('stepstest');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step1', 'step1:up ', 'step1:down ');

        $this->when->iRunTheScript();

        $this->then->theOutputFileShouldContain('step1:up ');
        $this->then->theStateFileShouldContain('1');
    }

    protected function setUp() {
        parent::setUp();

        $this->given = new StepsTest_Given();
        $this->given->test = $this;

        $this->when = new StepsTest_When();
        $this->when->test = $this;

        $this->then = new StepsTest_Then();
        $this->then->test = $this;
    }

    protected function tearDown() {
        foreach ($this->undos as $undo) {
            $undo();
        }
        parent::tearDown();
    }

}

/**
 * @property StepsTest test
 */
class StepsTest_Given {

    public $stepFolder;

    public $stepFolderName;

    public $outFile;

    public $namespace;

    public function theStepsContainingFolder($name) {
        $folder = __DIR__ . '/' . $name;

        $this->stepFolderName = $name;
        $this->stepFolder = $folder;

        $this->cleanUp($folder);
        mkdir($folder);

        $that = $this;
        $this->test->undos[] = function () use ($that, $folder) {
            $that->cleanUp($folder);
        };

        $this->outFile = __DIR__ . '/' . $name . '/out';
        $this->theFile($this->outFile);
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

    public function theFile($file) {
        $fh = fopen($file, 'w');
        fclose($fh);
    }

    public function theStep_WithTheUpOutput_AndTheDownOutput($stepName, $up, $down) {
        $refl = new \ReflectionClass($this);
        $this->namespace = $refl->getNamespaceName() . '\\' . $this->stepFolderName;

        file_put_contents($this->stepFolder . '/' . $stepName . '.php', "<?php namespace {$this->namespace};
        class {$stepName} implements \\watoki\\steps\\Step {

            public function up() {
                \$fh = fopen(__DIR__ . '/out', 'a'); fwrite(\$fh, \"$up\"); fclose(\$fh);
            }

            public function down() {
                \$fh = fopen(__DIR__ . '/out', 'a'); fwrite(\$fh, \"$down\"); fclose(\$fh);
            }

        }");
    }
}

/**
 * @property StepsTest test
 */
class StepsTest_When {

    public $stateFile;

    public function iRunTheScript() {
        $this->stateFile = __DIR__ . '/' . $this->test->given->stepFolderName . '/' . 'current';
        $migrater = new Migrater($this->test->given->namespace, $this->stateFile);

        $migrater->migrate();
    }
}

/**
 * @property StepsTest test
 */
class StepsTest_Then {

    public function theOutputFileShouldContain($content) {
        $this->test->assertFileExists($this->test->given->outFile);
        $this->test->assertEquals($content, file_get_contents($this->test->given->outFile));
    }

    public function theStateFileShouldContain($content) {
        $this->test->assertFileExists($this->test->when->stateFile);
        $this->test->assertEquals($content, file_get_contents($this->test->when->stateFile));
    }

    public function theStateFileShouldNotExist() {
        $this->test->assertFileNotExists($this->test->when->stateFile);
    }
}