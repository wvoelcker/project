<?php
namespace WillV\Project;

class View {
	static protected $defaultProjectRoot;
	protected $projectRoot, $relativeFilePath, $templatesDirectory = "templates", $templateData, $templateEngine, $templateFileExtension;

	static public function create($relativeFilePath, $projectRoot = null) {
		$view = new View;

		if (empty($projectRoot)) {
			$view->projectRoot = self::$defaultProjectRoot;
		} else {
			$view->projectRoot = $projectRoot;
		}

		$view->relativeFilePath = $relativeFilePath;

		$view->templateEngine = new \Mustache_Engine;
		$view->templateFileExtension = "mustache";

		return $view;
	}

	static public function setDefaultProjectRoot($defaultProjectRoot) {
		self::$defaultProjectRoot = $defaultProjectRoot;
	}

	public function set($key, $value = null) {

		// Allow passing in an array of key/value pairs instead of a single one
		if (is_array($key)) {
			foreach ($key as $subkey => $subvalue) {
				$this->set($subkey, $subvalue);
			}
			return $this;
		}

		$this->templateData[$key] = $value;

		return $this;
	}

	public function add($key, $value) {
		if (isset($this->templateData[$key]) and !$this->isSequentialArray($this->templateData[$key])) {
			throw new Exception("Can't add to this template variable - is already initiated to something other than a sequentially indexed array");
		}

		if (!isset($this->templateData[$key])) {
			$this->templateData[$key] = array();
		}

		$this->templateData[$key][] = $value;

		return $this;
	}

	protected function isSequentialArray($variable) {
		return (is_array($variable) and ($variable == array_values($variable)));
	}

	public function render() {
		return $this->templateEngine->render(
			file_get_contents($this->projectRoot."/".$this->templatesDirectory."/".$this->relativeFilePath.(empty($this->templateFileExtension)?"":(".".$this->templateFileExtension))),
			$this->templateData
		);
	}
}

