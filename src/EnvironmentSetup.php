<?php
namespace WillV\Project;

class EnvironmentSetup {
	protected $projectRoot, $configRoot;
	protected $configDir = "/project-config", $timezone = "UTC";

	private function __construct() {
	}

	static public function create() {
		$className = get_called_class();
		$setup = new $className;

		return $setup;
	}

	public function doSetup($projectRoot) {
		$this->projectRoot = $projectRoot;
		$this->configRoot = $this->projectRoot.$this->configDir;

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
		require_once $this->projectRoot."/vendor/autoload.php";
		require_once $this->configRoot."/ProjectAutoloaderSet.php";
		\ProjectAutoloaderSet::create($this->projectRoot)->register();
	}

	private function setUpViews() {
		View::setDefaultProjectRoot($this->projectRoot);
		require_once $this->configRoot."/ProjectViewConfigurator.php";
		View::setViewConfigurator(\ProjectViewConfigurator::create());
	}

	private function setUpEnvironment() {
		require_once $this->configRoot."/ProjectEnvironmentList.php";
		$activeEnvironment = \ProjectEnvironmentList::create()->findActiveEnvironment();
		if (empty($activeEnvironment)) {
			throw new \Exception("No active environment found");
		}

		return $activeEnvironment;
	}

}