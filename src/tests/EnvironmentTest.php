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

class ExampleEnvironmentWithInvalidRequiredFields extends Environment {
	protected function setUp() {
		$this->requiredFields = (object)array(
			"exampleKey1" => true,
			"exampleKey2" => true
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
		$environment = ExampleEnvironment::create(
			array(),
			array($configFile1, $configFile2),
			function() {
				return true;
			}
		);
		$this->assertEquals("exampleValue1", $environment->get("exampleKey1"));
		$this->assertEquals("exampleValue2", $environment->get("exampleKey2"));
		$this->assertTrue($environment instanceof ExampleEnvironment);
		unlink($configFile1);
		unlink($configFile2);
	}

	public function testItShouldAllowConfiguringWithSomeConfigFilesAndSomeNonConfigFileMethod() {
		$configFile = $this->makeTestConfigFile(array("exampleKey2" => "exampleValue2"));
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1"),
			array($configFile),
			function() {
				return true;
			}
		);
		$this->assertEquals("exampleValue1", $environment->get("exampleKey1"));
		$this->assertEquals("exampleValue2", $environment->get("exampleKey2"));
		$this->assertTrue($environment instanceof ExampleEnvironment);
		unlink($configFile);
	}

	private function makeTestConfigFile($data) {
		$filePath = tempnam("/dev/shm", "/project-test-environment-config-file");

		// Make a file accessible only to the current user
		// TODO:WV:20180226:What is the most secure way of doing this?  Any way to limit the file to the current *process* (not the current user?)
		chmod($filePath, 0600);

		file_put_contents($filePath, json_encode($data));
		return $filePath;
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unexpected number of arguments
     */
	public function testItShouldThrowAnExceptionIfOnlyOneArgumentWasProvided() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1")
		);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unexpected number of arguments
     */
	public function testItShouldThrowAnExceptionIfMoreThanThreeArgumentsWereProvided() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1"),
			array("/dev/null"),
			function() {
				return true;
			},
			"Extra argument"
		);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Data file not found: /does/not/exist.json
     */
	public function testItShouldThrowAnExceptionIfANonExistentConfigFileWasProvided() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1"),
			array("/does/not/exist.json"),
			function() {
				return true;
			}
		);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage requiredFields is not an array
     */
	public function testItShouldThrowAnExceptionIfRequiredFieldsIsSetToSomethingOtherThanAnArray() {
		$environment = ExampleEnvironmentWithInvalidRequiredFields::create(
			array("exampleKey1" => "exampleValue1", "exampleKey2" => "exampleValue2"),
			function() {
				return true;
			}
		);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Missing fields: {exampleKey2}
     */
	public function testItShouldThrowAnExceptionIfSomeRequiredDataWasNotProvided() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1"),
			function() {
				return true;
			}
		);
	}

	public function testItShouldCorrectlyDetermineIfItIsActive() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1", "exampleKey2" => "exampleValue2"),
			function() {
				return true;
			}
		);
		$this->assertTrue($environment->isActive());
	}

	public function testItShouldCorrectlyDetermineIfItIsNotActive() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1", "exampleKey2" => "exampleValue2"),
			function() {
				return false;
			}
		);
		$this->assertFalse($environment->isActive());
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Data key 'exampleKey3' not set
     */
	public function testItShouldThrowAnExceptionIfAttemptingToGetDataThatWasNotSet() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1", "exampleKey2" => "exampleValue2"),
			function() {
				return true;
			}
		);

		$environment->get("exampleKey3");
	}

	public function testItShouldReturnDataThatWasSetWhenCallingGet() {
		$environment = ExampleEnvironment::create(
			array("exampleKey1" => "exampleValue1", "exampleKey2" => "exampleValue2"),
			function() {
				return true;
			}
		);

		$this->assertEquals(
			$environment->get("exampleKey2"),
			"exampleValue2"
		);
	}
}
