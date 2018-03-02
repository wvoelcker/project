<?php
namespace WillV\Project;

abstract class EnvironmentSetup {
	use Trait_AbstractTemplate;
	protected $projectRoot, $configRoot, $timezone = "UTC";
	protected $autoLoaderSet = array(), $environmentList = array();

	protected function preSetUp() {
		$args = func_get_args();
		$this->projectRoot = $args[0];
		$this->configRoot = $args[0]."/".(empty($args[1])?"config":$args[1]);
		$this->registerAutoLoaders();
	}

	abstract protected function registerAutoLoaders();

	public function setAutoLoaders(AutoLoaderSet $autoLoaderSet) {
		$this->autoLoaderSet = $autoLoaderSet;
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
	}

	private function setUpEnvironment() {
		if (empty($this->environmentList)) {
			throw new \Exception("Please supply an environment list first");
		}

		$activeEnvironment = $this->environmentList->getActiveEnvironment();
		if (empty($activeEnvironment)) {
			throw new \Exception("No active environment found");
		}

		return $activeEnvironment;
	}

}