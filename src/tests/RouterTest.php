<?php
namespace WillV\Project\Tests\Router;
use PHPUnit\Framework\TestCase;
use WillV\Project\Router;
use WillV\Project\Environment;

use WillV\Project\Tests\TemporaryController;
require_once(__DIR__."/TemporaryController.php");

class ExampleRouter extends Router {
	protected function setUp() {
		$this->get("/url/1", "controller-1");
		$this->get("/url/1/json", "controller-1", "application/json");
		$this->get("/url/2", "controller-2");

		$this->post("/url/3", "controller-3");
		$this->post("/url/3/json", "controller-3", "application/json");
		$this->post("/url/4", "controller-4");

		$this->put("/url/5", "controller-5");
		$this->put("/url/5/json", "controller-5", "application/json");
		$this->put("/url/6", "controller-6");

		$this->delete("/url/7", "controller-7");
		$this->delete("/url/7/json", "controller-7", "application/json");
		$this->delete("/url/8", "controller-8");

		$this->getOrPost("/url/9", "controller-9");
		$this->getOrPost("/url/9/json", "controller-9", "application/json");
		$this->getOrPost("/url/10", "controller-10");

		$this->options("/url/11", "controller-11");
		$this->options("/url/11/json", "controller-11", "application/json");
		$this->options("/url/12", "controller-12");

		// Test same URL, different controller for different HTTP methods
		$this->post("/url/1", "controller-1a");
		$this->put("/url/1", "controller-1b");
		$this->delete("/url/1", "controller-1c");
		$this->options("/url/1", "controller-1d");
	}
}

class ExampleEnvironment extends Environment {
	protected function setUp() {
		$this->requiredFields = array(
			"exampleKey1",
			"exampleKey2"
		);
	}
}

class TestRouter extends TestCase {
	public function testItShouldRunThe404ControllerIfTheRouteWasNotFound() {
		$controllerDetails = TemporaryController::make("echo '404 Not Found';", "404");
		$router = ExampleRouter::create(
			$controllerDetails["testProjRoot"],
			ExampleEnvironment::create(
				array(
					"exampleKey1" => "exampleValue1",
					"exampleKey2" => "exampleValue2"
				),
				function() {
					return true;
				}
			)
		);

		ob_start();
		$router->go(
			"GET",
			"/no/such/path"
		);
		$output = ob_get_contents();
		TemporaryController::tidyUp($controllerDetails);
		ob_end_clean();

		$this->assertEquals("404 Not Found", $output);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldRunThe500ControllerIfThereWasAnException() {
		$testProjRoot = TemporaryController::getTestProjRoot();
		$errorControllerDetails = TemporaryController::make("echo '500 Internal Server Error';", "500", $testProjRoot);
		$routeControllerDetails = TemporaryController::make("throw new \Exception('Example Exception');", "controller-1", $testProjRoot);
		$router = ExampleRouter::create(
			$routeControllerDetails["testProjRoot"],
			ExampleEnvironment::create(
				array(
					"exampleKey1" => "exampleValue1",
					"exampleKey2" => "exampleValue2"
				),
				function() {
					return true;
				}
			)
		);

		ob_start();
		$router->go(
			"GET",
			"/url/1"
		);
		$output = ob_get_contents();
		ob_end_clean();
		TemporaryController::tidyUp($errorControllerDetails);
		TemporaryController::tidyUp($routeControllerDetails);

		$this->assertEquals("500 Internal Server Error", $output);
	}

	public function testItShouldNotRunThe500ControllerIfThereWasAnExceptionButCatchExceptionsIsSetToFalse() {
	}

	public function testItShouldDetectGetRequests() {
	}

	public function testItShouldDetectPostRequests() {
	}

	public function testItShouldDetectPutRequests() {
	}

	public function testItShouldDetectDeleteRequests() {
	}

	public function testItShouldDetectOptionsRequests() {
	}

	public function testItShouldHandleGetOrPostRoutes() {
	}

	public function testItShouldSupportSupplyingAnArrayOfPathPatterns() {
	}

	public function testItShouldUseADefaultResponseMimeTypeIfNoneWasProvided() {
	}

	public function testItShouldRunTheAppropriateController() {
	}

	public function testItShouldSupportNamedParameters() {
	}

	public function testItShouldSupportNamedParametersWithRegexMatches() {
	}

	public function testItShouldSupportRegexShortcutForNumbersOnly() {
	}

	public function testItShouldSupportRegexShortcutForAlphanumeric() {
	}

	public function testItShouldSupportRegexShortcutForAlphanumericAndPlusAndUnderscoreAndHyphenAndDot() {
	}

	public function testItShouldSupportRegexShortcutForHexadecimal() {
	}
}