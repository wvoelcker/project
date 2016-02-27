<?php
namespace WillV\Project;

class Controller {
	protected $controllersDirectory = "controllers", $relativeFilePath;

	// The following variables are here to be used from within included files
	protected $projectRoot, $activeEnvironment, $urlParams = array(), $lastException = null;

	static public function create($projectRoot, $activeEnvironment) {
		$controller = new Controller($projectRoot, $activeEnvironment);
		return $controller;
	}

	/**
	 * Private constructor, to enforce use of factory methods
	 *
	 * @param string      $projectRoot       filesystem path to the root directory of this project
	 * @param Environment $activeEnvironment an environment object containing various data about the currently active environment
	 *  
	 **/
	private function __construct($projectRoot, $activeEnvironment) {
		$this->projectRoot = $projectRoot;
		$this->activeEnvironment = $activeEnvironment;
	}

	public function setRelativeFilePath($relativeFilePath) {
		$this->relativeFilePath = $relativeFilePath;
		return $this;
	}

	public function setUrlParams($urlParams) {
		$this->urlParams = $urlParams;
		return $this;
	}

	public function setLastException($lastException) {
		$this->lastException = $lastException;
		return $this;
	}

	public function run() {
		require $this->projectRoot."/".$this->controllersDirectory."/".$this->relativeFilePath.".php";
	}

	protected function run404ControllerAndExit() {
		Controller::create($this->projectRoot, $this->activeEnvironment)->setRelativeFilePath("404")->run();
		exit;
	}
}