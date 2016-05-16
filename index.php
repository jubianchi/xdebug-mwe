<?php

class factory
{
	private $class;
	private $instance;

	public function __construct($class, & $instance)
	{
		$this->class = $class;
		$this->instance = & $instance;
	}

	public function __invoke()
	{
		$class = $this->class;
		$args = func_get_args();

		echo __CLASS__ . ':' . __LINE__ . ' > Instanciating ' . $class . PHP_EOL;
		echo __CLASS__ . ':' . __LINE__ . ' > With arguments ' . var_export($args, true) . PHP_EOL;

		$reflection = new \reflectionClass($class);

		return $this->instance = $reflection->newInstanceArgs($args);
	}
}

class manager
{
	private $factory;
	private $test;
	private $instance;

	public function __construct(test $test, factory $factory, & $instance)
	{
		$this->test = $test;
		$this->factory = $factory;
		$this->instance = & $instance;
	}

	public function __call($method, array $args)
	{
		switch ($method) {
			case 'given':
			case 'then':
			case 'object':
				return $this->test;

			case 'newTestedInstance':
				return call_user_func_array($this->factory, $args);

			case 'testedInstance':
				echo __CLASS__ . ':' . __LINE__ . ' > Fetching tested instance' . PHP_EOL;

				return $this->instance;

			default:
				throw new Exception();
		}
	}

	public function __get($prop)
	{
		switch ($prop) {
			case 'given':
			case 'then':
			case 'object':
				return $this->test;

			case 'newTestedInstance':
				return call_user_func($this->factory);

			case 'testedInstance':
				echo __CLASS__ . ':' . __LINE__ . ' > Fetching tested instance' . PHP_EOL;

				return $this->instance;

			default:
				throw new Exception();
		}
	}
}

class test
{
	private $manager;
	private $instance;

	public function __construct($testedClassName)
	{
		$this->manager = new manager($this, new factory($testedClassName, $this->instance), $this->instance);
	}

	public function __call($method, array $args)
	{
		return call_user_func_array(array($this->manager, $method), $args);
	}

	public function __get($prop)
	{
		return $this->manager->{$prop};
	}

	public function run($method)
	{
		echo __CLASS__ . ':' . __LINE__ . ' > Enabling code coverage' . PHP_EOL;

		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

		echo __CLASS__ . ':' . __LINE__ . ' > Starting method ' . $method . PHP_EOL;

		$this->{$method}();

		echo __CLASS__ . ':' . __LINE__ . ' > Fetching code coverage' . PHP_EOL;

		$coverage = xdebug_get_code_coverage();

		echo __CLASS__ . ':' . __LINE__ . ' > Stopping code coverage' . PHP_EOL;

		xdebug_stop_code_coverage();

		//var_dump($coverage);
	}
}

class MyTest extends test
{
	public function testOne()
	{
		$this
			->given($this->newTestedInstance())
			->then()
				->object($this->testedInstance())
		;
	}

	public function testTwo()
	{
		$this
			->given($this->newTestedInstance)
			->then
				->object($this->testedInstance)
		;
	}
}

// Segault
echo __CLASS__ . ':' . __LINE__ . ' > Running testOne: should segfault ' . PHP_EOL;
$t = new MyTest('StdClass');
$t->run('testOne');

// No segfault
echo __CLASS__ . ':' . __LINE__ . ' > Running testTwo: should not segfault ' . PHP_EOL;
$t = new MyTest('StdClass');
$t->run('testTwo');
