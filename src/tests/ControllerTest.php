<?php
namespace WillV\Project\Tests\ControllerTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\Controller;
use WillV\Project\Environment;

use WillV\Project\Tests\TemporaryController;
require_once(__DIR__."/TemporaryController.php");

class DummyEnvironment extends Environment {
	protected function setUp() {
		$this->requiredFields = array("dummyKey");
	}
}

class TestController extends TestCase {

	private function makeDummyEnvironment() {
		return DummyEnvironment::create(
			array(
				"dummyKey" => "dummyValue"
			),
			function() {
				return true;
			}
		);
	}

	public function testCreateShouldMakeANewController() {
		$controller = Controller::create("/dev/null", $this->makeDummyEnvironment());
		$this->assertTrue($controller instanceof Controller);
	}

	private function createAndRunTestController($fileContents, $urlParams = array(), $lastException = null) {
		$controllerDetails = TemporaryController::make("<?php ".$fileContents);

		$controller = Controller::create($controllerDetails["testProjRoot"], $this->makeDummyEnvironment());
		$controller->setRelativeFilePath($controllerDetails["controllerName"]);

		if (!empty($urlParams)) {
			$controller->setUrlParams($urlParams);
		}

		if (!empty($lastException)) {
			$controller->setLastException($lastException);
		}

		ob_start();
		$controller->run();
		$output = ob_get_contents();
		ob_end_clean();

		return $controllerDetails + array("output" => $output);
	}

	public function testItShouldUseAllThreePartsOfThePathCorrectlyWhenWorkingOutThePathOfAControllerAndRequireTheControllerWhenRunIsCalled() {
		$controllerDetails = $this->createAndRunTestController("echo __FILE__;");

		$this->assertEquals(
			$controllerDetails["testProjRoot"]."/controllers/".$controllerDetails["controllerName"].".php",
			$controllerDetails["output"]
		);
		TemporaryController::tidyUp($controllerDetails);
	}

	public function testItShouldMakeProjectRootAvailableToRequiredControllers() {
		$controllerDetails = $this->createAndRunTestController("echo \$this->projectRoot;");

		$this->assertEquals(
			$controllerDetails["testProjRoot"],
			$controllerDetails["output"]
		);
		TemporaryController::tidyUp($controllerDetails);
	}

	public function testItShouldMakeActiveEnvironmentAvailableToRequiredControllers() {
		$controllerDetails = $this->createAndRunTestController("echo \$this->activeEnvironment->get('dummyKey');");

		$this->assertEquals(
			"dummyValue",
			$controllerDetails["output"]
		);
		TemporaryController::tidyUp($controllerDetails);
	}

	public function testItShouldMakeUrlParametersAvailableToRequiredControllers() {
		$controllerDetails = $this->createAndRunTestController("echo \$this->urlParams['dummyUrlParam'];", array("dummyUrlParam" => "dummyUrlParamValue"));

		$this->assertEquals(
			"dummyUrlParamValue",
			$controllerDetails["output"]
		);
		TemporaryController::tidyUp($controllerDetails);
	}

	public function testItShouldMakeTheLastExceptionAvailableToRequiredControllers() {
		$controllerDetails = $this->createAndRunTestController("echo \$this->lastException->getMessage();", array(), new \Exception("Test Exception"));

		$this->assertEquals(
			"Test Exception",
			$controllerDetails["output"]
		);
		TemporaryController::tidyUp($controllerDetails);
	}

	public function testItShouldReturnAReferenceToItselfAfterCallingSetRelativeFilePath() {
		$controller = Controller::create("/dev/null", $this->makeDummyEnvironment());
		$output = $controller->setRelativeFilePath("test");
		$this->assertEquals($output, $controller);
	}

	public function testItShouldReturnAReferenceToItselfAfterCallingSetUrlParams() {
		$controller = Controller::create("/dev/null", $this->makeDummyEnvironment());
		$output = $controller->setUrlParams(array("testKey" => "testValue"));
		$this->assertEquals($output, $controller);
	}

	public function testItShouldReturnAReferenceToItselfAfterCallingSetLastException() {
		$controller = Controller::create("/dev/null", $this->makeDummyEnvironment());
		$output = $controller->setLastException(new \Exception("Test Exception"));
		$this->assertEquals($output, $controller);
	}

}