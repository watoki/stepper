<?php
namespace spec\watoki\stepper;

use watoki\stepper\Migrater;

/**
 * @property StepsTest_Given given
 * @property StepsTest_When when
 * @property StepsTest_Then then
 */
class MigraterTest extends Test {

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

}

/**
 * @property MigraterTest test
 */
class StepsTest_Given extends Test_Given {

    public $stepFolder;

    public $stepFolderName;

    public $outFile;

    public $namespace;

    public $stateFile;

    public function theStepsContainingFolder($name) {
        $folder = $this->theFolder($name);

        $this->stepFolderName = $name;
        $this->stepFolder = $folder;

        $this->outFile = $this->theFile($name . '/out');

        $this->stateFile = __DIR__ . '/' . $this->test->given->stepFolderName . '/' . 'current';
    }

    public function theStep_WithTheUpOutput_AndTheDownOutput($stepName, $up, $down) {
        $refl = new \ReflectionClass($this);
        $this->namespace = $refl->getNamespaceName() . '\\' . $this->stepFolderName;

        file_put_contents($this->stepFolder . '/' . $stepName . '.php', "<?php namespace {$this->namespace};
        class {$stepName} implements \\watoki\\stepper\\Step {

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
 * @property MigraterTest test
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
        $migrater = new Migrater($this->test->factory, $this->test->given->namespace, $this->test->given->stateFile);
        $migrater->migrate($num);
    }
}

/**
 * @property MigraterTest test
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