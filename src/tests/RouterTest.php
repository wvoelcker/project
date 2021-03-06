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

		// Test supplying an array of path patterns
		$this->get(array("/url/14", "/url/15", "/url/16"), "controller-14");

		// Test parameters
		$this->get("/url/with/parameters/{parameter1}/{parameter2}", "basic-parameter-controller");
		$this->get("/url/with/regex/parameters/{parameter1:user|group}/{parameter2:[0-9]+}", "regex-parameter-controller");
		$this->get("/url/with/regex/parameters/with/shortcut/numbers/{parameter1:user|group}/{parameter2:i}", "regex-parameter-shortcut-numbers-controller");
		$this->get("/url/with/regex/parameters/with/shortcut/alphanumeric/{parameter1:user|group}/{parameter2:a}", "regex-parameter-shortcut-alphanumeric-controller");
		$this->get("/url/with/regex/parameters/with/shortcut/alphanumericplus/{parameter1:user|group}/{parameter2:c}", "regex-parameter-shortcut-alphanumericplus-controller");
		$this->get("/url/with/regex/parameters/with/shortcut/hex/{parameter1:user|group}/{parameter2:h}", "regex-parameter-shortcut-hex-controller");
	}
}

class ExampleRouterThatDoesntCatchExceptions extends ExampleRouter {
	protected function setUp() {
		parent::setUp();
		$this->catchExceptions = false;
	}
}

class ExampleRouterWithSpecifiedDefaultMimeType extends ExampleRouter {
	protected function setUp() {
		$this->defaultResponseMimeType = "image/gif";
		parent::setUp();
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
		$this->confirm404("/no/such/url");
	}

	private function confirm404($url) {
		$controllerDetails = TemporaryController::make("echo '404 Not Found';", "404");
		$router = $this->makeRouter($controllerDetails["testProjRoot"]);

		ob_start();
		$router->go(
			"GET",
			"/no/such/url"
		);
		$output = ob_get_contents();
		TemporaryController::tidyUp($controllerDetails);
		ob_end_clean();

		$this->assertEquals("404 Not Found", $output);
	}

	private function makeRouter($projectRoot = null) {
		if (empty($projectRoot)) {
			$projectRoot = TemporaryController::getTestProjRoot();
		}
		$router = ExampleRouter::create(
			$projectRoot,
			$this->makeEnvironment()
		);

		return $router;
	}

	private function makeEnvironment() {
		$environment = ExampleEnvironment::create(
			array(
				"exampleKey1" => "exampleValue1",
				"exampleKey2" => "exampleValue2"
			),
			function() {
				return true;
			}
		);

		return $environment;
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldRunThe500ControllerIfThereWasAnException() {
		$testProjRoot = TemporaryController::getTestProjRoot();
		$errorControllerDetails = TemporaryController::make("echo '500 Internal Server Error';", "500", $testProjRoot);
		$routeControllerDetails = TemporaryController::make("throw new \Exception('Example Exception');", "controller-1", $testProjRoot);
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);

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

    /**
    * @runInSeparateProcess
    * @expectedException Exception
    * @expectedExceptionMessage Example Exception
    */
	public function testItShouldNotRunThe500ControllerIfThereWasAnExceptionButCatchExceptionsIsSetToFalse() {
		$testProjRoot = TemporaryController::getTestProjRoot();
		$errorControllerDetails = TemporaryController::make("echo '500 Internal Server Error';", "500", $testProjRoot);
		$routeControllerDetails = TemporaryController::make("throw new \Exception('Example Exception');", "controller-1", $testProjRoot);

		$router = ExampleRouterThatDoesntCatchExceptions::create(
			$routeControllerDetails["testProjRoot"],
			$this->makeEnvironment()
		);

		ob_start();
		$e = null;
		try {
			$router->go(
				"GET",
				"/url/1"
			);
		} catch (\Exception $e) {
			// Do nothing yet (need to close output buffers and tidy up temporary controllers before throwing $e)
		}
		ob_end_clean();

		TemporaryController::tidyUp($errorControllerDetails);
		TemporaryController::tidyUp($routeControllerDetails);

		if (!empty($e)) {
			throw $e;
		}
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldDetectGetRequests() {
		$this->confirmHttpMethod("GET", "1");
	}

	private function confirmHttpMethod($method, $controllerNumber, $urlEnd = null) {
		if ($urlEnd === null) {
			$urlEnd = $controllerNumber;
		}
		$routeControllerDetails = $this->makeTestController($controllerNumber);
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);

		ob_start();
		$router->go(
			$method,
			"/url/".$urlEnd
		);
		$output = ob_get_contents();
		ob_end_clean();
		TemporaryController::tidyUp($routeControllerDetails);

		$this->assertEquals($routeControllerDetails["expectedOutput"], $output);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldDetectPostRequests() {
		$this->confirmHttpMethod("POST", "3");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldDetectPutRequests() {
		$this->confirmHttpMethod("PUT", "5");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldDetectDeleteRequests() {
		$this->confirmHttpMethod("DELETE", "7");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldDetectOptionsRequests() {
		$this->confirmHttpMethod("OPTIONS", "11");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldHandleGetOrPostRoutesWhenGetIsTheMethod() {
		$this->confirmHttpMethod("GET", "9");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldHandleGetOrPostRoutesWhenPostIsTheMethod() {
		$this->confirmHttpMethod("POST", "9");
	}

    /**
    * @runInSeparateProcess
    * @expectedException Exception
    * @expectedExceptionMessage Allow: PUT
    */
	public function testItShouldThrowAnExceptionIfYouTryToUseAnHttpMethodThatIsNotAllowedForAParticularURL() {
		$routeControllerDetails = $this->makeTestController(5);
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);
		$router->go(
			"POST",
			"/url/5"
		);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSupportSupplyingAnArrayOfPathPatterns() {
		$this->confirmHttpMethod("GET", "14", "14");
		$this->confirmHttpMethod("GET", "14", "15");
		$this->confirmHttpMethod("GET", "14", "16");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldUseADefaultResponseMimeTypeIfNoneWasProvided() {
		$routeControllerDetails = $this->makeTestController();
		$router = ExampleRouterWithSpecifiedDefaultMimeType::create(
			$routeControllerDetails["testProjRoot"],
			$this->makeEnvironment()
		);

		$this->confirmMimeType($router, $routeControllerDetails, 1, "image/gif");
	}

	private function makeTestController($controllerName = "controller-1") {
		if (ctype_digit((string)$controllerName)) {
			$controllerName = "controller-".$controllerName;
		}
		$routeControllerDetails = TemporaryController::make("echo basename(__FILE__, '.php').'-contents';", $controllerName);
		$routeControllerDetails["expectedOutput"] = $controllerName."-contents";
		return $routeControllerDetails;
	}

	private function confirmMimeType($router, $routeControllerDetails, $urlEnd = 1, $mimeType = "text/html", $charset = "utf-8") {
		if (!function_exists("xdebug_get_headers")) {
			$this->markTestSkipped("XDebug is not available, so not able to verify headers");
		}

		ob_start();
		$router->go(
			"GET",
			"/url/".$urlEnd
		);
		ob_end_clean();
		TemporaryController::tidyUp($routeControllerDetails);
		$headersSent = xdebug_get_headers();

		$this->assertTrue(is_array($headersSent));
		$this->assertTrue(in_array("Content-Type: ".$mimeType."; charset=".$charset, $headersSent));
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldUseTextHtmlAsTheDefaultResponseMimeTypeIfNoDefaultWasProvided() {
		$routeControllerDetails = $this->makeTestController();
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);
		$this->confirmMimeType($router, $routeControllerDetails, 1, "text/html");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSpecifyUTF8AsTheCharset() {
		$routeControllerDetails = $this->makeTestController();
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);
		$this->confirmMimeType($router, $routeControllerDetails, 1, "text/html", "utf-8");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldUseAnyProvidedResponseMimeType() {
		$routeControllerDetails = $this->makeTestController();
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);
		$this->confirmMimeType($router, $routeControllerDetails, "1/json", "application/json");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldRunTheAppropriateController() {
		$routeControllerDetails = $this->makeTestController(2);
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);

		ob_start();
		$router->go(
			"GET",
			"/url/2"
		);
		$output = ob_get_contents();
		ob_end_clean();
		TemporaryController::tidyUp($routeControllerDetails);

		$this->assertEquals($routeControllerDetails["expectedOutput"], $output);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSupportParameters() {
		$routeControllerDetails = TemporaryController::make("echo \$this->urlParams[0].':'.\$this->urlParams[1];", "basic-parameter-controller");
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);

		ob_start();
		$router->go(
			"GET",
			"/url/with/parameters/p1value/p2value"
		);
		$output = ob_get_contents();
		ob_end_clean();
		TemporaryController::tidyUp($routeControllerDetails);

		$this->assertEquals("p1value:p2value", $output);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldNotMatchURLsContainingParametersThatDoNotMatchASuppliedRegex() {
		$this->confirm404("/url/with/regex/parameters/section/1");
		$this->confirm404("/url/with/regex/parameters/user/jo");
		$this->confirm404("/url/with/regex/parameters/section/articles");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldMatchURLsContainingParametersThatMatchASuppliedRegex() {
		$this->confirmNot404("/url/with/regex/parameters/user/1", "regex-parameter-controller");
	}

	private function confirmNot404($url, $controllerName) {
		$routeControllerDetails = $this->makeTestController($controllerName);
		$router = $this->makeRouter($routeControllerDetails["testProjRoot"]);

		ob_start();
		$router->go(
			"GET",
			$url
		);
		$output = ob_get_contents();
		ob_end_clean();
		TemporaryController::tidyUp($routeControllerDetails);

		$this->assertEquals($routeControllerDetails["expectedOutput"], $output);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSupportRegexShortcutForNumbersOnly() {
		$this->confirm404("/url/with/regex/parameters/with/shortcut/numbers/user/chen");
		$this->confirmNot404("/url/with/regex/parameters/with/shortcut/numbers/user/15", "regex-parameter-shortcut-numbers-controller");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSupportRegexShortcutForAlphanumeric() {
		$this->confirm404("/url/with/regex/parameters/with/shortcut/alphanumeric/user/@@#");
		$this->confirmNot404("/url/with/regex/parameters/with/shortcut/alphanumeric/user/chen15", "regex-parameter-shortcut-alphanumeric-controller");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSupportRegexShortcutForAlphanumericAndPlusAndUnderscoreAndHyphenAndDot() {
		$this->confirm404("/url/with/regex/parameters/with/shortcut/alphanumericplus/user/chen15+,-.@");
		$this->confirmNot404("/url/with/regex/parameters/with/shortcut/alphanumericplus/user/chen15+_-.", "regex-parameter-shortcut-alphanumericplus-controller");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSupportRegexShortcutForHexadecimal() {
		$this->confirm404("/url/with/regex/parameters/with/shortcut/hex/user/12ABcd34Z");
		$this->confirmNot404("/url/with/regex/parameters/with/shortcut/hex/user/12ABcd34", "regex-parameter-shortcut-hex-controller");
	}
}