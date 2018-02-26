<?php
use PHPUnit\Framework\TestCase;
use WillV\Project\Controller;
use WillV\Project\Environment;

class DummyEnvironment extends Environment {
	protected function setUp() {
		$this->requiredFields = array("dummyKey");
	}
}

class TestController extends TestCase {

	private function makeDummyEnvironment() {
		return DummyEnvironment::create(
			array(
				"dummyKey" => true
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

	private function makeTestController($fileContents = "") {
		$testProjRoot = "/dev/shm/project-tests-".uniqid();
		$testControllerDir = $testProjRoot."/controllers";
		if (!file_exists($testControllerDir)) {
			mkdir($testControllerDir, 0600, true);
		}
		$thisControllerPathNoExtension = tempnam($testControllerDir, "project");
		$thisControllerPath = $thisControllerPathNoExtension.".php";
		rename($thisControllerPathNoExtension, $thisControllerPath);

		// Make a file accessible only to the current user
		// TODO:WV:20180226:What is the most secure way of doing this?  Any way to limit the file to the current *process* (not the current user?)
		chmod($thisControllerPath, 0600);

		$thisControllerName = basename($thisControllerPath, ".php");

		file_put_contents($thisControllerPath, $fileContents);

		return array(
			"testProjRoot" => $testProjRoot,
			"controllerName" => $thisControllerName,
			"fullPath" => $thisControllerPath,
		);
	}

	private function tidyUpTestController($controllerDetails) {
		unlink($controllerDetails["fullPath"]);
		rmdir(dirname($controllerDetails["fullPath"]));
	}

	private function createAndRunTestController($fileContents) {
		$controllerDetails = $this->makeTestController("<?php ".$fileContents);

		$controller = Controller::create($controllerDetails["testProjRoot"], $this->makeDummyEnvironment());
		$controller->setRelativeFilePath($controllerDetails["controllerName"]);

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
		$this->tidyUpTestController($controllerDetails);
	}

	public function testItShouldMakeProjectRootAvailableToRequiredControllers() {
		$controllerDetails = $this->createAndRunTestController("echo \$this->projectRoot;");

		$this->assertEquals(
			$controllerDetails["testProjRoot"],
			$controllerDetails["output"]
		);
		$this->tidyUpTestController($controllerDetails);
	}

	public function testItShouldMakeActiveEnvironmentAvailableToRequiredControllers() {

	}

	public function testItShouldMakeUrlParametersAvailableToRequiredControllers() {

	}

	public function testItShouldMakeTheLastExceptionAvailableToRequiredControllers() {

	}

	public function testItShouldReturnAReferenceToItselfAfterCallingSetRelativeFilePath() {

	}

	public function testItShouldReturnAReferenceToItselfAfterCallingSetUrlParams() {

	}

	public function testItShouldReturnAReferenceToItselfAfterCallingSetLastException() {

	}

}