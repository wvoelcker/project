<?php
namespace WillV\Project;

abstract class EnvironmentSetup {
	protected $projectRoot, $configRoot, $timezone = "UTC";
	protected $autoLoaderSet = array(), $viewConfigurator = array(), $environmentList = array();

	private function __construct() {
	}

	static public function create($projectRoot, $configDir = "config") {
		$className = get_called_class();
		$setup = new $className;

		$setup->projectRoot = $projectRoot;
		$setup->configRoot = $projectRoot."/".$configDir;

		$setup->registerAutoLoaders();
		$setup->addHelpers();

		return $setup;
	}

	public function setAutoLoaders(AutoLoaderSet $autoLoaderSet) {
		$this->autoLoaderSet = $autoLoaderSet;
	}

	public function setViewConfigurator(ViewConfigurator $viewConfigurator) {
		$this->viewConfigurator = $viewConfigurator;
	}

	public function setEnvironmentList(EnvironmentList $environmentList) {
		$this->environmentList = $environmentList;
	}

	public function doSetup() {
		$this->setUpTimeZone();
		$this->setUpAutoloaders();
		$this->setUpViews();
		$activeEnvironment = $this->setUpEnvironment();
		return $activeEnvironment;
	}

	abstract protected function registerAutoLoaders();

	abstract protected function addHelpers();

	private function setUpTimeZone() {
		date_default_timezone_set($this->timezone);
	}

	private function setUpAutoloaders() {
		if (!empty($this->autoLoaderSet)) {
			$this->autoLoaderSet->register();
		}
	}

	private function setUpViews() {
		View::setDefaultProjectRoot($this->projectRoot);
		if (!empty($this->viewConfigurator)) {
			View::setViewConfigurator($this->viewConfigurator);
		}
	}

	private function setUpEnvironment() {
		if (empty($this->environmentList)) {
			throw new \Exception("Please supply an environment list first");
		}

		$activeEnvironment = $this->environmentList->findActiveEnvironment();
		if (empty($activeEnvironment)) {
			throw new \Exception("No active environment found");
		}

		return $activeEnvironment;
	}

}