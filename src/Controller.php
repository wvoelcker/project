<?php
namespace WillV\Project;

class Controller {
	protected $controllersDirectory = "controllers", $relativeFilePath;

	// The following variables are here to be used from within included files
	protected $projectRoot, $urlParams = array(), $lastException = null;

	public function create($projectRoot) {
		$controller = new Controller;
		$controller->projectRoot = $projectRoot;
		return $controller;
	}

	public function setRelativeFilePath($relativeFilePath) {
		$this->relativeFilePath = $relativeFilePath;
	}

	public function setUrlParams($urlParams) {
		$this->urlParams = $urlParams;
	}

	public function setLastException($lastException) {
		$this->lastException = $lastException;
	}

	public function run() {
		require $this->projectRoot."/".$this->controllersDirectory."/".$this->relativeFilePath.".php";
	}
}