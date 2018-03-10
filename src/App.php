<?php
namespace WillV\Project;
use WillV\Project\View;
use WillV\Project\AutoloaderSet;
use ProjectExampleApp\Config\EnvironmentList;

// TODO:WV:20180309:Test this
class App {
	public $projectRoot, $activeEnvironment;

	static public function bootstrap($projectRoot, $rootNamespace) {

		// Set up timezone
		date_default_timezone_set('UTC');

		// Set up autoloaders
		$autoLoaderSet = AutoloaderSet::create($projectRoot, $rootNamespace);
		$autoLoaderSet->register();

		// Find active environment
		$environmentList = EnvironmentList::create();
		$activeEnvironment = EnvironmentList::create()->getActiveEnvironment();
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