<?php
use PHPUnit\Framework\TestCase;
use WillV\Project\Environment;


class ExampleEnvironment extends Environment {
	protected function setUp() {
		$this->requiredFields = array(
			"exampleKey1",
			"exampleKey2"
		);
	}
}

class TestEnvironment extends TestCase {
	public function testItShouldAllowConfiguringWithOutConfigFiles() {
		$environment = ExampleEnvironment::create(array(
			"exampleKey1" => "exampleValue1",
			"exampleKey2" => "exampleValue2",
		), function() {
			return true;
		});
		$this->assertEquals("exampleValue1", $environment->get("exampleKey1"));
		$this->assertEquals("exampleValue2", $environment->get("exampleKey2"));
		$this->assertTrue($environment instanceof ExampleEnvironment);
	}

	public function testItShouldAllowConfiguringWithOnlyConfigFiles() {
		$configFile1 = $this->makeTestConfigFile(array("exampleKey1" => "exampleValue1"));
		$configFile2 = $this->makeTestConfigFile(array("exampleKey2" => "exampleValue2"));
		$environment = ExampleEnvironment::create(array(), array($configFile1, $configFile2), function() {
			return true;
		});
		$this->assertEquals("exampleValue1", $environment->get("exampleKey1"));
		$this->assertEquals("exampleValue2", $environment->get("exampleKey2"));
		$this->assertTrue($environment instanceof ExampleEnvironment);
	}

	public function testItShouldAllowConfiguringWithSomeConfigFilesAndSomeNonConfigFileMethod() {
		$configFile2 = $this->makeTestConfigFile(array("exampleKey2" => "exampleValue2"));
		$environment = ExampleEnvironment::create(array("exampleKey1" => "exampleValue1"), array($configFile2), function() {
			return true;
		});
		$this->assertEquals("exampleValue1", $environment->get("exampleKey1"));
		$this->assertEquals("exampleValue2", $environment->get("exampleKey2"));
		$this->assertTrue($environment instanceof ExampleEnvironment);
	}

	private function makeTestConfigFile($data) {
		$filePath = tempnam("/dev/shm", "/project-test-environment-config-file");

		// Make a file accessible only to the current user
		// TODO:WV:20180226:What is the most secure way of doing this?  Any way to limit the file to the current *process* (not the current user?)
		chmod($filePath, 0600);

		file_put_contents($filePath, json_encode($data));
		return $filePath;
	}


	public function testItShouldThrowAnExceptionIfOnlyOneArgumentWasProvided() {

	}

	public function testItShouldThrowAnExceptionIfMoreThanTwoArgumentsWereProvided() {
		
	}

	public function testItShouldThrowAnExceptionIfANonExistentConfigFileWasProvided() {
		
	}

	public function testItShouldThrowAnExceptionIfRequiredFieldsIsSetToSomethingOtherThanAnArray() {
		
	}

	public function testItShouldThrowAnExceptionIfSomeRequiredDataWasNotProvided() {
		
	}

	public function testItShouldCorrectlyDetermineIfItIsActive() {

	}

	public function testItShouldThrowAnExceptionIfAttemptingToGetDataThatWasNotSet() {

	}

	public function testItShouldReturnDataThatWasSetWhenCallingGet() {

	}
}
