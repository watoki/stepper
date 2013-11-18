<?php
namespace spec\watoki\stepper;
 
use spec\watoki\stepper\fixtures\StepFixture;
use watoki\cli\Console;
use watoki\cli\writers\ArrayWriter;
use watoki\scrut\Specification;
use watoki\stepper\cli\StepperCommand;

/**
 * @property StepFixture step <-
 */
class StepperCommandTest extends Specification {

    function testNonExistingStateFile() {
        $this->step->givenTheStep('NonExistingStateOne');
        $this->whenIExecuteTheCommandWithTheStateFile('NonExisting.state');
        $this->thenTheFile_ShouldContain('NonExisting.state', 'NonExistingStateOne');
    }

    function testLoadAndSaveState() {
        $this->givenTheFile_WithTheContent('my.state', 'MyStepOne');

        $this->step->givenTheStep_WithTheNextStep('MyStepOne', 'MyStepTwo');
        $this->step->givenTheStep_WithTheNextStep('MyStepTwo', 'MyStepThree');
        $this->step->givenTheStep('MyStepThree');

        $this->whenIExecuteTheCommandWithTheStateFile('my.state');

        $this->thenTheLogShouldBe(array("Migrating up [MyStepTwo]", "Migrating up [MyStepThree]"));
        $this->thenTheFile_ShouldContain('my.state', 'MyStepThree');
    }

    /** @var ArrayWriter */
    private $writer;

    private $tmpDir;

    protected function setUp() {
        parent::setUp();
        $this->tmpDir = __DIR__ . DIRECTORY_SEPARATOR;
    }

    private function givenTheFile_WithTheContent($file, $content) {
        $fullFile = $this->tmpDir . $file;
        file_put_contents($fullFile, $content);

        $this->undos[] = function () use ($fullFile) {
            @unlink($fullFile);
        };
    }

    private function whenIExecuteTheCommandWithTheStateFile($file) {
        $command = new StepperCommand($this->step->firstStep, $this->tmpDir . $file);

        $this->writer = new ArrayWriter();
        $command->execute(new Console($this->writer), array());
    }

    private function thenTheFile_ShouldContain($file, $content) {
        $fullFile = $this->tmpDir . $file;
        $this->assertFileExists($fullFile);
        $this->assertEquals($content, file_get_contents($fullFile));

        @unlink($fullFile);
    }

    private function thenTheLogShouldBe($messages) {
        $count = count($messages);
        $this->assertEquals($messages, array_slice($this->writer->getOutput(), -$count, $count));
    }

}
 