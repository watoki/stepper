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

    public function testReadOptionsFromConfig() {
        $this->given->theFolder('tmp2');
        $this->given->theBootstrapFile('tmp2/bootstrap.php');
        $this->given->theFile_WithContent('tmp2/config.json', '{
        "bootstrap": "' . str_replace('\\', '\\\\', __DIR__) . '/tmp2/bootstrap.php",
        "namespace": "my\\\\space",
        "state": "/tmp/state"}');

        $this->when->iExecuteTheCommandWithTheConfig('tmp2/config.json');

        $this->then->theMigraterContructorArgument_ShouldBe('namespace', 'my\space');
        $this->then->theMigraterContructorArgument_ShouldBe('stateFile', '/tmp/state');
        $this->then->theBootstrapFileShouldBeRequired();
    }

    public function testMixDefaultConfigAndArguments() {
        $this->given->theFolder('config');
        $this->given->theBootstrapFile('config/bootstrap.php');
        $this->given->theFile_WithContent('config/stepper.json', '{
        "namespace": "your\\\\space",
        "state": "/tmp/state"}');

        $this->when->iExecuteTheCommandWithBootstrap_State(
            'config/bootstrap.php',
            '/other/state');

        $this->then->theMigraterContructorArgument_ShouldBe('namespace', 'your\space');
        $this->then->theMigraterContructorArgument_ShouldBe('stateFile', '/other/state');
        $this->then->theBootstrapFileShouldBeRequired();
    }

    public function testMissingOption() {
        $this->when->iTryToExecuteTheCommand();

        $this->then->anExceptionShouldBeThrownContaining('bootstrap');
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

    /**
     * @var \Exception|null
     */
    public $caught;

    function __construct() {
        $mf = new MockFactory();

        $this->factory = $mf->createMock(Factory::$CLASS);

        $this->migrater = $mf->createMock(Migrater::$CLASS);
        $this->factory->__mock()->method('getInstance')->willReturn($this->migrater);
    }

    /**
     * @return MigrateCommand
     */
    private function getCommand() {
        return new MigrateCommand($this->factory, __DIR__);
    }

    function iExecuteTheCommandWithBootstrap_Namespace_State_AndTarget($bootstrapFile, $namespace, $stateFile, $target) {
        $this->getCommand()->run(new ArrayInput(array(
            '--bootstrap' => __DIR__ . '/' . $bootstrapFile,
            '--namespace' => $namespace,
            '--state' => $stateFile,
            'target' => $target
        )), new NullOutput());
    }

    public function iExecuteTheCommandWithBootstrap_State($bootstrapFile, $stateFile) {
        $this->getCommand()->run(new ArrayInput(array(
            '--bootstrap' => __DIR__ . '/' . $bootstrapFile,
            '--state' => $stateFile
        )), new NullOutput());
    }

    public function iExecuteTheCommand() {
        $this->getCommand()->run(new ArrayInput(array()), new NullOutput());
    }

    public function iTryToExecuteTheCommand() {
        try {
            $this->iExecuteTheCommand();
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    public function iExecuteTheCommandWithTheConfig($configFile) {
        $this->getCommand()->run(new ArrayInput(array(
            '--config' => __DIR__ . '/' . $configFile
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
        $method = $this->test->when->migrater->__mock()->method('migrate');
        $this->test->assertEquals(1, $method->getCalledCount());
        $this->test->assertEquals(array('to' => $int), $method->getCalledArgumentsAt(0));
    }

    public function theBootstrapFileShouldBeRequired() {
        global $bootstrapIncluded;
        $this->test->assertTrue($bootstrapIncluded);
    }

    public function anExceptionShouldBeThrownContaining($msg) {
        $this->test->assertNotNull($this->test->when->caught);
        $this->test->assertContains($msg, $this->test->when->caught->getMessage());
    }
}