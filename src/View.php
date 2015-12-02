<?php
namespace WillV\Project;

class View {
	protected $projectRoot, $relativeFilePath, $templatesDirectory = "templates", $templateData, $templateEngine, $templateFileExtension;

	public function create($projectRoot, $relativeFilePath) {
		$view = new View;
		$view->projectRoot = $projectRoot;
		$view->relativeFilePath = $relativeFilePath;

		$view->templateEngine = new \Mustache_Engine;
		$view->templateFileExtension = "mustache";

		return $view;
	}

	public function set($key, $value) {
		$this->templateData[$key] = $value;

		return $this;
	}

	public function add($key, $value) {
		if (isset($this->templateData[$key]) and !$this->isSequentialArray($this->templateData[$key])) {
			throw new Exception("Can't add to this template variable - is already initiated to something other than a sequentially indexed array");
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

