<?php
namespace WillV\Project;

class View {
	static protected $defaultProjectRoot, $templateCache = array();
	protected $projectRoot, $viewName, $templatesDirectory = "templates", $templateData, $templateEngine;
	protected $templateFileExtension, $filters = array(), $globalFilters = array(), $postFilters = array();

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
			throw new \Exception("Can't add to this template variable - is already initiated to something other than a sequentially indexed array");
		}

		if (!isset($this->templateData[$key])) {
			$this->templateData[$key] = array();
		}

		$this->templateData[$key][] = $value;

		return $this;
	}

	public function addFilter($key, $filterFunction = null) {

		// If only one argument supplied, interpret it as a global filter
		if ($filterFunction === null) {
			$filterFunction = $key;
			$filters = &$this->globalFilters;

		// If two arguments supplied, interpret them as a data-key and a filter for that data
		} else {
			if (!isset($this->filters[$key])) {
				$this->filters[$key] = array();
			}
			$filters = &$this->filters[$key];
		}

		if (!($filterFunction instanceOf \Closure)) {
			throw new \Exception("Expected a Closure");
		}

		$filters[] = $filterFunction;

		return $this;
	}

	public function addPostFilter($filterFunction) {
		$this->postFilters[] = $filterFunction;
	}

	protected function isSequentialArray($variable) {
		return (is_array($variable) and ($variable == array_values($variable)));
	}

	public function render() {
		$templateData = $this->templateData;

		// Apply filters
		foreach ($this->filters as $key => $filterFunctions) {
			if (isset($templateData[$key])) {
				foreach ($filterFunctions as $filterFunction) {
					$newTemplateData = $filterFunction($this->templateData[$key]);
					if ($newTemplateData === null) {
						throw new \Exception("Please make sure that your filter functions return data of some sort (even if only an empty string; 'null' is out of bounds)");
					}
					$templateData[$key] = $newTemplateData;
				}
			}
		}

		// Apply global filters
		foreach ($this->globalFilters as $filterFunction) {
			$newTemplateData = $filterFunction($templateData);
			if (!is_array($newTemplateData)) {
				throw new \Exception("Global filter functions should return an array");
			}
			$templateData = $newTemplateData;
		}

		// Render template
		$output = $this->templateEngine->render(
			$this->getTemplateContents(),
			$templateData
		);

		// Apply post-filters
		foreach ($this->postFilters as $filterFunction) {
			$output = $filterFunction($output, $templateData);
		}

		return $output;
	}

	public function __toString() {
		return $this->render();
	}

	private function getTemplateContents() {
		$templateFile = ($this->projectRoot."/".$this->templatesDirectory."/".$this->viewName.(empty($this->templateFileExtension)?"":(".".$this->templateFileExtension)));
		if (!isset(self::$templateCache[$templateFile])) {
			self::$templateCache[$templateFile] = file_get_contents($templateFile);
		}

		return self::$templateCache[$templateFile];
	}
}

