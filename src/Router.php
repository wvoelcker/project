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
	protected $projectRoot, $activeEnvironment, $routeCollector, $controllersDirectory = "controllers";

	static public function create($projectRoot, Environment $activeEnvironment) {
		$className = get_called_class();
		$router = new $className;
		$router->projectRoot = $projectRoot;
		$router->activeEnvironment = $activeEnvironment;
		$router->routeCollector = new RouteCollector();
		$router->addRoutes();

		return $router;
	}

	public function go($method, $urlPath) {
		$dispatcher = $this->getDispatcher($this->getRoutingData());

		try {
			return $dispatcher->dispatch($method, parse_url($urlPath, PHP_URL_PATH));

		} catch (HttpRouteNotFoundException $e) {
			$this->runController("404", array(), $e);

		} catch (Exception $e) {
			$this->runController("500", array(), $e);
		}
	}

	/**
	 * Abstracted out into its own function, to allow overriding this
	 * for dependency injection purposes (e.g. when unit testing)
	 * 
	 */
	protected function getDispatcher($routingData) {
		return new Dispatcher($routingData);
	}

	protected function runController($controllerPath, $urlParams = array(), $lastException = null) {
		try {
			$controller = Controller::create($this->projectRoot, $this->activeEnvironment);
			$controller->setRelativeFilePath($controllerPath);
			$controller->setUrlParams($urlParams);
			$controller->setLastException($lastException);
			$controller->run();

		} catch (\Exception $e) {
			$this->runController("500", array(), $e);
		}
	}

	protected function getRoutingData() {

		// Comment from the examples in the Phroute docs:
		// NB. You can cache the return value from $router->getData() so you don't have to create the routes each request - massive speed gains
		return $this->routeCollector->getData();
	}

	abstract protected function addRoutes();

}