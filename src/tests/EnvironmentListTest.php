<?php
namespace WillV\Project\Tests\EnvironmentListTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\Environment;
use WillV\Project\EnvironmentList;


class ExampleEnvironment extends Environment {
	protected function setUp() {
		$this->requiredFields = array(
			"exampleKey1",
			"exampleKey2"
		);
	}
}

class ExampleEnvironmentList extends EnvironmentList {
	protected function setUp() {
		$this->addEnvironment(
			"development",
			ExampleEnvironment::create(array(
				"exampleKey1" => "development1",
				"exampleKey2" => "development2",
			),
			function() {
				return false;
			}
		));
		$this->addEnvironment(
			"staging",
			ExampleEnvironment::create(array(
				"exampleKey1" => "staging1",
				"exampleKey2" => "staging2",
			),
			function() {
				return false;
			}
		));
		$this->addEnvironment(
			"production",
			ExampleEnvironment::create(array(
				"exampleKey1" => "production1",
				"exampleKey2" => "production2",
			),
			function() {
				return false;
			}
		));
	}
}

class ExampleEnvironmentListWithActiveEnvironment extends ExampleEnvironmentList {
	protected function setUp() {
		parent::setUp();

		$this->addEnvironment(
			"test",
			ExampleEnvironment::create(array(
				"exampleKey1" => "test1",
				"exampleKey2" => "test2",
			),
			function() {
				return true;
			}
		));
	}
}

class ExampleEnvironmentListWithDuplicateEnvironment extends ExampleEnvironmentList {
	protected function setUp() {
		parent::setUp();

		$this->addEnvironment(
			"production",
			ExampleEnvironment::create(array(
				"exampleKey1" => "production-duplicate-1",
				"exampleKey2" => "production-duplicate-2",
			),
			function() {
				return true;
			}
		));
	}
}


class TestEnvironmentList extends TestCase {

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Environment 'production' already added
     */
	public function testItShouldThrowAnExceptionIfTwoEnvironmentsHaveTheSameName() {
		ExampleEnvironmentListWithDuplicateEnvironment::create();
	}

	public function testItShouldFindTheCorrectActiveEnvironment() {
		$environment = ExampleEnvironmentListWithActiveEnvironment::create()->getActiveEnvironment();
		$this->assertEquals("test1", $environment->get("exampleKey1"));
	}

	public function testItShouldReturnNullWhenFindingTheActiveEnvironmentIfThereAreNoActiveEnvironments() {
		$environment = ExampleEnvironmentList::create()->getActiveEnvironment();
		$this->assertEquals(null, $environment);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Environment 'nonExistentEnvironment' not found
     */
	public function testItShouldThrowAnExceptionWhenGettingANonExistentEnvironment() {
		ExampleEnvironmentListWithActiveEnvironment::create()->getEnvironment("nonExistentEnvironment");
	}

	public function testItShouldGetAnExistentEnvironment() {
		$function = ExampleEnvironmentListWithActiveEnvironment::create()->getEnvironment("staging");
		$this->assertEquals("staging1", $function->get("exampleKey1"));
	}

}
