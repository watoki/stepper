<?php
namespace spec\watoki\stepper\fixtures;

use watoki\scrut\Fixture;
use watoki\stepper\Step;

class StepFixture extends Fixture {

    /** @var Step */
    public $firstStep;

    public function givenTheStep($step) {
        $this->givenTheStep_WithTheNextStep($step, null);
    }

    public function givenTheStep_WithTheNextStep_WhichCannotBeUndone($step, $next) {
        $this->givenTheStep_WithTheNextStep($step, $next, false);
    }

    public function givenTheStep_WithTheNextStep($step, $next, $canBeUndone = true) {
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
                    public function canBeUndone() {
                        return ' . ($canBeUndone ? 'true' : 'false') . ';
                    }
                }');

        if (!$this->firstStep) {
            $this->firstStep = new $step;
        }
    }

} 