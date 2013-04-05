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

        $this->when->iMigrateToTheLastStep();

        $this->then->theOutputFileShouldContain('');
        $this->then->theStateFileShouldNotExist();
    }

    public function testFirstStep() {
        $this->given->theStepsContainingFolder('stepstest');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step1', 'step1:up ', '');

        $this->when->iMigrateToTheLastStep();

        $this->then->theOutputFileShouldContain('step1:up ');
        $this->then->theStateFileShouldContain('1');
    }

    public function testTwoSteps() {
        $this->given->theStepsContainingFolder('twosteps');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step1', 'step1:up ', '');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step2', 'step2:up ', '');

        $this->when->iMigrateToTheLastStep();

        $this->then->theOutputFileShouldContain('step1:up step2:up ');
        $this->then->theStateFileShouldContain('2');
    }

    public function testAdvancedStart() {
        $this->given->theStepsContainingFolder('start');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step3', 'step3:up ', '');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step4', 'step4:up ', '');
        $this->given->theStateFileContains('2');

        $this->when->iMigrateToTheLastStep();

        $this->then->theOutputFileShouldContain('step3:up step4:up ');
        $this->then->theStateFileShouldContain('4');
    }

    public function testMissingStep() {
        $this->given->theStepsContainingFolder('missing');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step3', 'step3:up ', '');
        $this->given->theStateFileContains('1');

        $this->when->iTryToMigrateToTheLastStep();

        $this->then->noExceptionShouldBeThrown();
        $this->then->theStateFileShouldContain('1');
    }

    public function testStepToTarget() {
        $this->given->theStepsContainingFolder('target');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step1', '1up', '');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step2', '2up', '');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step3', '3up', '');

        $this->when->iMigrateToStep(2);

        $this->then->theOutputFileShouldContain('1up2up');
        $this->then->theStateFileShouldContain('2');
    }

    public function testStepToCurrent() {
        $this->given->theStepsContainingFolder('nullstep');
        $this->given->theStateFileContains('2');

        $this->when->iMigrateToStep(2);

        $this->then->theOutputFileShouldContain('');
        $this->then->theStateFileShouldContain('2');
    }

    public function testStepBack() {
        $this->given->theStepsContainingFolder('back');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step2', '', 'down2');
        $this->given->theStep_WithTheUpOutput_AndTheDownOutput('Step3', '', 'down3');
        $this->given->theStateFileContains(3);

        $this->when->iMigrateToStep(1);

        $this->then->theOutputFileShouldContain('down3down2');
        $this->then->theStateFileShouldContain(1);
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

    public $stateFile;

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

        $this->stateFile = __DIR__ . '/' . $this->test->given->stepFolderName . '/' . 'current';
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
                file_put_contents(__DIR__ . '/out', \"$up\", FILE_APPEND);
            }

            public function down() {
                file_put_contents(__DIR__ . '/out', \"$down\", FILE_APPEND);
            }

        }");
    }

    public function theStateFileContains($content) {
        file_put_contents($this->stateFile, $content);
    }
}

/**
 * @property StepsTest test
 */
class StepsTest_When {

    /**
     * @var \Exception|null
     */
    public $caught;

    public function iMigrateToTheLastStep() {
        $this->iMigrateToStep(null);
    }

    public function iTryToMigrateToTheLastStep() {
        try {
            $this->iMigrateToTheLastStep();
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function iMigrateToStep($num) {
        $migrater = new Migrater($this->test->given->namespace, $this->test->given->stateFile);
        $migrater->migrate($num);
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
        $this->test->assertFileExists($this->test->given->stateFile);
        $this->test->assertEquals($content, file_get_contents($this->test->given->stateFile));
    }

    public function theStateFileShouldNotExist() {
        $this->test->assertFileNotExists($this->test->given->stateFile);
    }

    public function anExceptionShouldBeThrownContaining($message) {
        $this->test->assertNotNull($this->test->when->caught);
        $this->test->assertContains($message, $this->test->when->caught->getMessage());
    }

    public function noExceptionShouldBeThrown() {
        $this->test->assertNull($this->test->when->caught);
    }
}