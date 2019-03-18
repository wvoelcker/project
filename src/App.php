<?php
namespace WillV\Project;
use WillV\Project\View;
use WillV\Project\AutoloaderSet;

// TODO:WV:20180309:Unit test this
class App {
	public $projectRoot, $activeEnvironment;

	static public function bootstrap($projectRoot, $rootNamespace) {

		// Set up timezone
		date_default_timezone_set('UTC');

		// Set up autoloaders
		$autoLoaderSet = AutoloaderSet::create($projectRoot, $rootNamespace);
		$autoLoaderSet->register();

		// Find active environment
		$environmentListClass = $rootNamespace."\Config\EnvironmentList";
		$environmentListClass::create();
		$activeEnvironment = $environmentList->getActiveEnvironment();
		if (empty($activeEnvironment)) {
			throw new \Exception("No active environment found");
		}

		// Set up views
		View::setDefaultProjectRoot($projectRoot);

		// Store settings in 'app' object
		$app = new App;
		$app->projectRoot = $projectRoot;
		$app->activeEnvironment = $activeEnvironment;

		return $app;
	}

	private function __construct() {
	}
}