<?php
namespace WillV\Project;

abstract class ViewConfigurator {
	protected $globalConfig, $configs = array();

	static public function create() {
		$className = get_called_class();
		$configurator = new $className;
		$configurator->addConfigs();

		return $configurator;
	}
	
	public function configure($viewName, $view) {
		if (!empty($this->globalConfig)) {
			call_user_func_array($this->globalConfig, array($view));
		}
		if (isset($this->configs[$viewName])) {
			call_user_func_array($this->configs[$viewName], array($view));
		}
	}

	abstract protected function addConfigs();
}

