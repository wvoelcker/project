<?php
namespace WillV\Project;

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;


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
abstract class Router {
	protected $projectRoot, $routeCollector, $controllersDirectory = "controllers";

	// These variables are here to be available to controllers via $this
	protected $urlParams = array(), $mustache, $lastException = null;

	protected function runController($controllerPath, $urlParams = array()) {
		$this->urlParams = $urlParams;
		require $this->projectRoot."/".$this->controllersDirectory."/".$controllerPath.".php";
	}

	static public function create($projectRoot, Mustache_Engine $mustache) {
		$router = new ProjectRouter;
		$router->projectRoot = $projectRoot;
		$router->routeCollector = new RouteCollector();
		$router->mustache = $mustache;
		$router->addRoutes();

		return $router;
	}

	public function go($method, $urlPath) {
		$dispatcher = new Dispatcher($this->getRoutingData());

		try {
			return $dispatcher->dispatch($method, parse_url($urlPath, PHP_URL_PATH));

		} catch (HttpRouteNotFoundException $e) {
			$this->lastException = $e;
			$this->runController("404");

		} catch (Exception $e) {
			$this->lastException = $e;
			$this->runController("500");
		}
	}

	protected function getRoutingData() {

		// Comment from the examples in the Phroute docs:
		// NB. You can cache the return value from $router->getData() so you don't have to create the routes each request - massive speed gains
		return $this->routeCollector->getData();
	}

	abstract protected function addRoutes();

}