<?php
namespace WillV\Project;


/**
 * This class should be extended within a particular project, filling in the addRoutes
 * method with appropriate routing logic.  For example:
 *
 * class ProjectRouter extends Router {
 *
 *	protected function addRoutes() {
 *
 *		$this->routeCollector->get("/", function() {
 *			$this->runController("home", func_get_args());		
 *		});
 *
 *	}
 * }
 */
class TemplateEngine {
	protected $projectRoot, $templatesDirectory = "templates";

	static public function create($projectRoot, \Mustache_Engine $mustache) {

		$mustache = new Mustache_Engine(array(
			"loader" => new Mustache_Loader_FilesystemLoader($projectRoot."/".$this->templatesDirectory),
		));

		return $mustache;
	}

}