<?php
namespace spec\watoki\stepper;

use spec\watoki\stepper\fixtures\StepFixture;
use watoki\scrut\Specification;
use watoki\stepper\events\MigrationCompletedEvent;
use watoki\stepper\Migrater;

/**
 * @property StepFixture step <-
 */
class MigraterTest extends Specification {

    public function testSingleStep() {
        $this->step->givenTheStep('OnlyOne');

        $this->whenIStartTheMigration();

        $this->thenTheNewStateShouldBe('OnlyOne');
        $this->thenTheExecutedStepsShouldBe(array('OnlyOneUp'));
    }

    public function testTwoSteps() {
        $this->step->givenTheStep_WithTheNextStep('StepOneOfTwo', 'StepTwoOfTwo');
        $this->step->givenTheStep('StepTwoOfTwo');

        $this->whenIStartTheMigration();

        $this->thenTheNewStateShouldBe('StepTwoOfTwo');
        $this->thenTheExecutedStepsShouldBe(array('StepOneOfTwoUp', 'StepTwoOfTwoUp'));
    }

    public function testWithState() {
        $this->givenTheCurrentStateIs('WithStateTwo');

        $this->step->givenTheStep_WithTheNextStep('WithStateOne', 'WithStateTwo');
        $this->step->givenTheStep_WithTheNextStep('WithStateTwo', 'WithStateThree');
        $this->step->givenTheStep_WithTheNextStep('WithStateThree', 'WithStateFour');
        $this->step->givenTheStep('WithStateFour');

        $this->whenIStartTheMigration();

        $this->thenTheNewStateShouldBe('WithStateFour');
        $this->thenTheExecutedStepsShouldBe(array('WithStateThreeUp', 'WithStateFourUp'));
    }

    public function testMigrateUpToTarget() {
        $this->step->givenTheStep_WithTheNextStep('TargetOne', 'TargetTwo');
        $this->step->givenTheStep_WithTheNextStep('TargetTwo', 'TargetThree');
        $this->step->givenTheStep('TargetThree');

        $this->whenIStartTheMigrationTo('TargetTwo');

        $this->thenTheNewStateShouldBe('TargetTwo');
        $this->thenTheExecutedStepsShouldBe(array('TargetOneUp', 'TargetTwoUp'));
    }

    public function testTargetIsCurrentState() {
        $this->givenTheCurrentStateIs('SameStateTwo');

        $this->step->givenTheStep_WithTheNextStep('SameStateOne', 'SameStateTwo');
        $this->step->givenTheStep_WithTheNextStep('SameStateTwo', 'SameStateThree');
        $this->step->givenTheStep('SameStateThree');

        $this->whenIStartTheMigrationTo('SameStateTwo');

        $this->thenTheNewStateShouldBe('SameStateTwo');
        $this->thenTheExecutedStepsShouldBe(array());
    }

    public function testMigrateDown() {
        $this->givenTheCurrentStateIs('DownThree');

        $this->step->givenTheStep_WithTheNextStep('DownOne', 'DownTwo');
        $this->step->givenTheStep_WithTheNextStep('DownTwo', 'DownThree');
        $this->step->givenTheStep('DownThree');

        $this->whenIStartTheMigrationTo('DownOne');

        $this->thenTheNewStateShouldBe('DownOne');
        $this->thenTheExecutedStepsShouldBe(array('DownThreeDown', 'DownTwoDown'));
    }

    public function testImpossibleDownMigration() {
        $this->givenTheCurrentStateIs('ImpossibleThree');

        $this->step->givenTheStep_WithTheNextStep('ImpossibleOne', 'ImpossibleTwo');
        $this->step->givenTheStep_WithTheNextStep_WhichCannotBeUndone('ImpossibleTwo', 'ImpossibleThree');
        $this->step->givenTheStep('ImpossibleThree');

        $this->whenITryToMigrationTo('ImpossibleOne');

        $this->thenAnExceptionContaining_ShouldBeThrown('Cannot migrate down. Step [ImpossibleTwo] cannot be undone.');
    }

    public function testInvalidState() {
        $this->givenTheCurrentStateIs('invalid');

        $this->step->givenTheStep_WithTheNextStep('InvalidStateOne', 'InvalidStateTwo');
        $this->step->givenTheStep('InvalidStateTwo');

        $this->whenITryToMigrationTo('InvalidStateTwo');

        $this->thenAnExceptionContaining_ShouldBeThrown('Cannot migrate. Invalid state: [invalid]');
    }

    public function testInvalidTarget() {
        $this->givenTheCurrentStateIs('InvalidTargetOne');

        $this->step->givenTheStep_WithTheNextStep('InvalidTargetOne', 'InvalidTargetTwo');
        $this->step->givenTheStep('InvalidTargetTwo');

        $this->whenITryToMigrationTo('NonExisting');

        $this->thenAnExceptionContaining_ShouldBeThrown('Cannot migrate. Invalid target: [NonExisting]');
    }

    public function testCircularSteps() {
        $this->step->givenTheStep_WithTheNextStep('CircularOne', 'CircularTwo');
        $this->step->givenTheStep_WithTheNextStep('CircularTwo', 'CircularThree');
        $this->step->givenTheStep_WithTheNextStep('CircularThree', 'CircularOne');

        $this->whenITryToMigrationTo('CircularThree');

        $this->thenAnExceptionContaining_ShouldBeThrown('Circular step detected: [CircularThree]');
    }

    public $state;

    public static $executed;

    /** @var \Exception */
    private $caught;

    protected function setUp() {
        parent::setUp();
        self::$executed = array();
    }

    private function givenTheCurrentStateIs($step) {
        $this->state = $step;
    }

    private function whenIStartTheMigration() {
        $this->whenIStartTheMigrationTo(null);
    }

    private function whenITryToMigrationTo($target) {
        try {
            $this->whenIStartTheMigrationTo($target);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    private function whenIStartTheMigrationTo($target) {
        $migrater = new Migrater($this->step->firstStep, $this->state);

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

    private function thenAnExceptionContaining_ShouldBeThrown($string) {
        $this->assertNotNull($this->caught, 'Expected Exception was not thrown.');
        $this->assertContains($string, $this->caught->getMessage());
    }

}