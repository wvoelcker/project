<?php
namespace WillV\Project;
/**
 * This can be overridden to add config (template variables etc.) to all views in a project,
 * and to specific views whenever they are used.
 *
 *
 * E.g.
 *
 * class MyConfigurator extends ViewConfigurator {
 *
 *	protected function addConfigs() {
 *		$this->configs["page"] = function($view) {
 *			$view->set("header-carousel-images", array("image1.jpg", "image2.jpg", "image3.jpg", "image4.jpg"));
 *		};
 *	}
 * }
 *
 * And then in the main controller / include file:
 *
 * // Set up views
 * View::setDefaultProjectRoot($projectRoot);
 * require_once $projectRoot."/config/MyConfigurator.php";
 * View::setViewConfigurator(MyConfigurator::create());
 *
 * 
 */


abstract class ViewConfigurator {
	use Trait_AbstractTemplate;
	protected $globalConfigs = array(), $configs = array();

	public function configure($viewName, $view) {
		if (!empty($this->globalConfigs)) {
			foreach ($this->globalConfigs as $globalConfig) {
				call_user_func_array($globalConfig, array($view));
			}
		}
		if (!empty($this->configs[$viewName])) {
			foreach ($this->configs[$viewName] as $config) {
				call_user_func_array($config, array($view));
			}
		}
	}

	protected function addConfig($configFunction, $viewNames = null) {
		if (empty($viewNames)) {
			$this->globalConfigs[] = $configFunction;
		} else {
			if (!is_array($viewNames)) {
				$viewNames = array($viewNames);
			}
			foreach ($viewNames as $viewName) {
				if (!isset($this->configs[$viewName])) {
					$this->configs[$viewName] = array();
				}
				$this->configs[$viewName][] = $configFunction;
			}
		}
	}
}

