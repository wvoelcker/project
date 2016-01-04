<?php
namespace WillV\Project;

class View {
	static protected $defaultProjectRoot, $viewConfigurator, $templateCache = array();
	protected $projectRoot, $viewName, $templatesDirectory = "templates", $templateData, $templateEngine, $templateFileExtension, $filters = array();

	static public function create($viewName, $projectRoot = null) {
		$view = new View;

		if (empty($projectRoot)) {
			$view->projectRoot = self::$defaultProjectRoot;
		} else {
			$view->projectRoot = $projectRoot;
		}

		$view->viewName = $viewName;

		$view->templateEngine = new \Mustache_Engine;
		$view->templateFileExtension = "mustache";

		if (!empty(self::$viewConfigurator)) {
			self::$viewConfigurator->configure($viewName, $view);
		}

		return $view;
	}

	static public function setDefaultProjectRoot($defaultProjectRoot) {
		self::$defaultProjectRoot = $defaultProjectRoot;
	}

	static public function setViewConfigurator($viewConfigurator) {
		self::$viewConfigurator = $viewConfigurator;
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

	public function addFilter($key, $filterFunction) {
		if (!($filterFunction instanceOf \Closure)) {
			throw new \Exception("Expected a Closure");
		}

		if (!isset($this->filters[$key])) {
			$this->filters[$key] = array();
		}

		$this->filters[$key][] = $filterFunction;

		return $this;
	}

	protected function isSequentialArray($variable) {
		return (is_array($variable) and ($variable == array_values($variable)));
	}

	public function render() {

		// Apply filters
		foreach ($this->filters as $key => $filterFunctions) {
			if (isset($this->templateData[$key])) {
				foreach ($filterFunctions as $filterFunction) {
					$this->templateData[$key] = $filterFunction($this->templateData[$key]);
				}
			}
		}

		// Render template
		return $this->templateEngine->render(
			$this->getTemplateContents(),
			$this->templateData
		);
	}

	private function getTemplateContents() {
		$templateFile = ($this->projectRoot."/".$this->templatesDirectory."/".$this->viewName.(empty($this->templateFileExtension)?"":(".".$this->templateFileExtension)));
		if (!isset(self::$templateCache[$templateFile])) {
			self::$templateCache[$templateFile] = file_get_contents($templateFile);
		}

		return self::$templateCache[$templateFile];
	}
}

