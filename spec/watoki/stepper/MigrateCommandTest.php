<?php
namespace spec\watoki\stepper;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use watoki\factory\Factory;
use watoki\stepper\Migrater;
use watoki\stepper\cli\MigrateCommand;

/**
 * @property MigrateCommandTest_Given given
 * @property MigrateCommandTest_When when
 * @property MigrateCommandTest_Then then
 */
class MigrateCommandTest extends Test {

    public function testInputArguments() {
        $this->given->theFolder('tmp');
        $this->given->theBootstrapFile('tmp/bootstrap');

        $this->when->iExecuteTheCommandWithBootstrap_Namespace_State_AndTarget(
            'tmp/bootstrap',
            'some\namespace',
            'tmp/current',
            '42'
        );

        $this->then->theMigraterContructorArgument_ShouldBe('namespace', 'some\namespace');
        $this->then->theMigraterContructorArgument_ShouldBe('stateFile', 'tmp/current');
        $this->then->theTargetShouldBe(42);
        $this->then->theBootstrapFileShouldBeRequired();
    }

    protected function setUp() {
        parent::setUp();

        $this->given = new MigrateCommandTest_Given();
        $this->given->test = $this;

        $this->when = new MigrateCommandTest_When();
        $this->when->test = $this;

        $this->then = new MigrateCommandTest_Then();
        $this->then->test = $this;
    }

}

/**
 * @property MigrateCommandTest test
 */
class MigrateCommandTest_Given extends Test_Given {

    public function theBootstrapFile($name) {
        $this->theFile_WithContent($name, '<?php global $bootstrapIncluded; $bootstrapIncluded = true;');
    }
}

/**
 * @property MigrateCommandTest test
 */
class MigrateCommandTest_When {

    /**
     * @var Factory|Mock
     */
    public $factory;

    /**
     * @var Mock
     */
    public $migrater;

    function __construct() {
        $mf = new MockFactory();

        $this->factory = $mf->createMock(Factory::$CLASS);

        $this->migrater = $mf->createMock(Migrater::$CLASS);
        $this->factory->__mock()->method('getInstance')->willReturn($this->migrater);
    }

    function iExecuteTheCommandWithBootstrap_Namespace_State_AndTarget($bootstrapFile, $namespace, $stateFile, $target) {
        $migrate = new MigrateCommand($this->factory, __DIR__);
        $migrate->run(new ArrayInput(array(
            '--bootstrap' => __DIR__ . '/' . $bootstrapFile,
            '--namespace' => $namespace,
            '--state' => $stateFile,
            'target' => $target
        )), new NullOutput());
    }
}

/**
 * @property MigrateCommandTest test
 */
class MigrateCommandTest_Then {

    public function theMigraterContructorArgument_ShouldBe($key, $value) {
        $args = $this->test->when->factory->__mock()->method('getInstance')->getCalledArgumentAt(0, 1);
        $this->test->assertEquals($value, $args[$key]);
    }

    public function theTargetShouldBe($int) {
        $this->test->assertEquals($int, $this->test->when->migrater->__mock()->method('migrate')->getCalledArgumentAt(0, 0));
    }

    public function theBootstrapFileShouldBeRequired() {
        global $bootstrapIncluded;
        $this->test->assertTrue($bootstrapIncluded);
    }
}