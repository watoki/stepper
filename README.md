# stepper [![Build Status](https://travis-ci.org/watoki/stepper.png?branch=master)](https://travis-ci.org/watoki/stepper)

*stepper* is a minimalistic migration tool.

## Usage ##

In order to use *stepper*, you need at least one Step defining what should happen when the the Step is executed and also
whether and how it can be undone.

    class MyFirstStep implements Step {
        public function up() {
            // does something
        }

        public function down() {
            // undoes something
        }

        public function canBeUndone() {
            return true;
        }

        public function next() {}
    }

With this one Step and a file (e.g. `migration.state`) to store the current state in, you can start the Stepper:

    $app = new CliApplication(new StepperCommand(new StepOne(), 'migration.state'));
    $app->run();

That's it. You can now execute the Step with `php myScript.php`. If you add a second Step later, make sure that the first Step
returns it in it's `next` method:

    public function next() {
        return new AnotherStep();
    }

If you run `php myScript.php` now, only `AnotherStep` gets executed since the fact that the first Step already has been executed
has been saved in `migration.state`.

If you want to revert whatever `AnotherStep` did you can run `php myScript.php --to=MyFirstStep` which will execute the `down` function
of `AnotherStep`. This won't work if `AnotherStep::canBeUndone` returns `false`.

## Documentation ##

For a detailed documentation, check out the [test suite].

[test suite]: https://github.com/watoki/stepper/tree/master/spec/watoki/stepper/