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

    public function testWithState() {
        $this->givenTheCurrentStateIs('WithStateTwo');

        $this->givenTheStep_WithTheNextStep('WithStateOne', 'WithStateTwo');
        $this->givenTheStep_WithTheNextStep('WithStateTwo', 'WithStateThree');
        $this->givenTheStep_WithTheNextStep('WithStateThree', 'WithStateFour');
        $this->givenTheStep('WithStateFour');

        $this->whenIStartTheMigration();

        $this->thenTheNewStateShouldBe('WithStateFour');
        $this->thenTheExecutedStepsShouldBe(array('WithStateThreeUp', 'WithStateFourUp'));
    }

    public function testMigrateUpToTarget() {
        $this->givenTheStep_WithTheNextStep('TargetOne', 'TargetTwo');
        $this->givenTheStep_WithTheNextStep('TargetTwo', 'TargetThree');
        $this->givenTheStep('TargetThree');

        $this->whenIStartTheMigrationTo('TargetTwo');

        $this->thenTheNewStateShouldBe('TargetTwo');
        $this->thenTheExecutedStepsShouldBe(array('TargetOneUp', 'TargetTwoUp'));
    }

    public function testTargetIsCurrentState() {
        $this->givenTheCurrentStateIs('SameStateTwo');

        $this->givenTheStep_WithTheNextStep('SameStateOne', 'SameStateTwo');
        $this->givenTheStep_WithTheNextStep('SameStateTwo', 'SameStateThree');
        $this->givenTheStep('SameStateThree');

        $this->whenIStartTheMigrationTo('SameStateTwo');

        $this->thenTheNewStateShouldBe('SameStateTwo');
        $this->thenTheExecutedStepsShouldBe(array());
    }

    public function testMigrateDown() {
        $this->givenTheCurrentStateIs('DownThree');

        $this->givenTheStep_WithTheNextStep('DownOne', 'DownTwo');
        $this->givenTheStep_WithTheNextStep('DownTwo', 'DownThree');
        $this->givenTheStep('DownThree');

        $this->whenIStartTheMigrationTo('DownOne');

        $this->thenTheNewStateShouldBe('DownOne');
        $this->thenTheExecutedStepsShouldBe(array('DownThreeDown', 'DownTwoDown'));
    }

    public function testImpossibleDownMigration() {
        $this->markTestIncomplete();
    }

    public function testExceptionWhileMigrating() {
        $this->markTestIncomplete();
    }

    public function testInvalidState() {
        $this->markTestIncomplete();
    }

    public function testCircularSteps() {
        $this->markTestIncomplete();
    }

    protected function setUp() {
        parent::setUp();
        self::$executed = array();
    }

    private function givenTheStep($step) {
        $this->givenTheStep_WithTheNextStep($step, null);
    }

    private function givenTheCurrentStateIs($step) {
        $this->state = $step;
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
        $this->whenIStartTheMigrationTo(null);
    }

    private function whenIStartTheMigrationTo($target) {
        $stepName = $this->firstStep;
        $step = new $stepName;
        $migrater = new Migrater($step, $this->state);

        $that = $this;
        $migrater->on(MigrationCompletedEvent::$CLASS, function (MigrationCompletedEvent $e) use ($that) {
            $that->state = $e->getNewState();
        });
        $migrater->migrate($target);
    }

    private function thenTheNewStateShouldBe($str) {
        $this->assertEquals($str, $this->state);
    }

    private function thenTheExecutedStepsShouldBe($array) {
        $this->assertEquals($array, self::$executed);
    }

}