<?php
namespace spec\watoki\stepper;

use watoki\scrut\Specification;
use watoki\stepper\events\MigrationCompletedEvent;
use watoki\stepper\Migrater;

class MigraterTest extends Specification {

    public $state;

    public static $executed;

    private $firstStep;

    public function testSingleStep() {
        $this->givenTheStep('OnlyOne');

        $this->whenIStartTheMigration();

        $this->thenTheNewStateShouldBe('OnlyOne');
        $this->thenTheExecutedStepsShouldBe(array('OnlyOneUp'));
    }

    public function testTwoSteps() {
        $this->givenTheStep_WithTheNextStep('StepOneOfTwo', 'StepTwoOfTwo');
        $this->givenTheStep('StepTwoOfTwo');

        $this->whenIStartTheMigration();

        $this->thenTheNewStateShouldBe('StepTwoOfTwo');
        $this->thenTheExecutedStepsShouldBe(array('StepOneOfTwoUp', 'StepTwoOfTwoUp'));
    }

    public function testWithCurrentStep() {
        $this->markTestIncomplete();
    }

    public function testMigrateUpToTarget() {
        $this->markTestIncomplete();
    }

    public function testMigrateToCurrent() {
        $this->markTestIncomplete();
    }

    public function testMigrateDown() {
        $this->markTestIncomplete();
    }

    public function testImpossibleDownMigration() {
        $this->markTestIncomplete();
    }

    protected function setUp() {
        parent::setUp();
        self::$executed = array();
    }

    private function givenTheStep($step) {
        $this->givenTheStep_WithTheNextStep($step, null);
    }

    private function givenTheStep_WithTheNextStep($step, $next) {
        eval('  class ' . $step . ' implements \watoki\stepper\Step {
                    public function next() {
                        ' . ($next ? "return new $next;" : '') . '
                    }
                    public function up() {
                        \spec\watoki\stepper\MigraterTest::$executed[] = "' . $step . 'Up";
                    }
                    public function down() {
                        \spec\watoki\stepper\MigraterTest::$executed[] = "' . $step . 'Down";
                    }
                    public function canBeUndone() {}
                }');

        if (!$this->firstStep) {
            $this->firstStep = $step;
        }
    }

    private function whenIStartTheMigration() {
        $stepName = $this->firstStep;
        $step = new $stepName;
        $migrater = new Migrater($step, null);

        $that = $this;
        $migrater->on(MigrationCompletedEvent::$CLASS, function (MigrationCompletedEvent $e) use ($that) {
            $that->state = $e->getNewState();
        });
        $migrater->migrate();
    }

    private function thenTheNewStateShouldBe($str) {
        $this->assertEquals($str, $this->state);
    }

    private function thenTheExecutedStepsShouldBe($str) {
        $this->assertEquals($str, self::$executed);
    }

}