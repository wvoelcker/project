<?php
namespace WillV\Project\Tests;

class TemporaryController {
	static public function getTestProjRoot() {
		return $testProjRoot = "/dev/shm/project-tests-".uniqid();
	}
	static public function make($fileContents = "", $fileName = null, $testProjRoot = null) {
		if (empty($testProjRoot)) {
			$testProjRoot = self::getTestProjRoot();
		}
		$testControllerDir = $testProjRoot."/controllers";
		if (!file_exists($testControllerDir)) {
			mkdir($testControllerDir, 0700, true);
		}
		if (empty($fileName)) {
			$thisControllerPathNoExtension = tempnam($testControllerDir, "project");
			$thisControllerPath = $thisControllerPathNoExtension.".php";
			rename($thisControllerPathNoExtension, $thisControllerPath);			
		} else {
			$thisControllerPathNoExtension = $testControllerDir."/".$fileName;
			$thisControllerPath = $thisControllerPathNoExtension.".php";
			touch($thisControllerPath);
		}

		// Make a file accessible only to the current user
		// TODO:WV:20180226:What is the most secure way of doing this?  Any way to limit the file to the current *process* (not the current user?)
		chmod($thisControllerPath, 0600);

		$thisControllerName = basename($thisControllerPath, ".php");

		if (!empty($fileContents)) {
			$fileContents = "<?php ".$fileContents;
		}
		file_put_contents($thisControllerPath, $fileContents);

		return array(
			"testProjRoot" => $testProjRoot,
			"controllerName" => $thisControllerName,
			"fullPath" => $thisControllerPath,
		);
	}

	static public function tidyUp($controllerDetails) {
		unlink($controllerDetails["fullPath"]);
		$dir = dirname($controllerDetails["fullPath"]);
		self::rmDirIfEmpty($dir);
		self::rmDirIfEmpty(dirname($dir));
	}

	static private function rmDirIfEmpty($dir) {
		$directoryIsEmpty = (count(scandir($dir)) == 2);
		if ($directoryIsEmpty) {
			rmdir($dir);
		}
	}

}
