<?php
namespace WillV\Project;
/**
 * This can be overridden to add config (template variables etc.) to all views in a project,
 * and to specific views whenever they are used.
 *
 *
 * E.g.
 *
 * class ProjectViewConfigurator extends ViewConfigurator {
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
 * require_once $projectRoot."/project-config/ProjectViewConfigurator.php";
 * View::setViewConfigurator(ProjectViewConfigurator::create());
 *
 * 
 */


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

