<?php
namespace WillV\Project;

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;


/**
 * This class should be extended within a particular project, filling in the addRoutes
 * method with appropriate routing logic.
 */
abstract class Router {
	use Trait_AbstractTemplate;
	protected $projectRoot, $activeEnvironment, $routeCollector, $controllersDirectory = "controllers", $defaultResponseMimeType = "text/html", $catchExceptions = true;

	protected function preSetUp() {
		$args = func_get_args();
		$this->projectRoot = $args[0];
		$this->activeEnvironment = $args[1];
		$this->routeCollector = new RouteCollector();
	}

	public function go($method, $urlPath) {
		$dispatcher = $this->getDispatcher($this->getRoutingData());

		try {
			return $dispatcher->dispatch($method, parse_url($urlPath, PHP_URL_PATH));

		} catch (HttpRouteNotFoundException $e) {
			$this->runController("404", array(), $e);

		} catch (Exception $e) {
			if (!$this->catchExceptions) {
				throw $e;
			}
			$this->runController("500", array(), $e);
		}
	}

	/**
	 * Abstracted out into its own function, to allow overriding this
	 * for dependency injection purposes (e.g. when unit testing)
	 *
	 */
	protected final function getDispatcher($routingData) {
		return new Dispatcher($routingData);
	}

	protected final function runController($controllerPath, $urlParams = array(), $lastException = null) {
		try {
			$controller = Controller::create($this->projectRoot, $this->activeEnvironment);
			$controller->setRelativeFilePath($controllerPath);
			$controller->setUrlParams($urlParams);
			$controller->setLastException($lastException);
			$controller->run();

		} catch (\Exception $e) {
			if (!$this->catchExceptions) {
				throw $e;
			}
			$this->runController("500", array(), $e);
		}
	}

	protected final function getRoutingData() {

		// Comment from the examples in the Phroute docs:
		// NB. You can cache the return value from $router->getData() so you don't have to create the routes each request - massive speed gains
		return $this->routeCollector->getData();
	}

	protected final function get($pathPattern, $controller, $responseMimeType = null) {
		$this->addRoute("get", $pathPattern, $controller, $responseMimeType);
	}

	protected final function options($pathPattern, $controller, $responseMimeType = null) {
		$this->addRoute("options", $pathPattern, $controller, $responseMimeType);
	}

	protected final function post($pathPattern, $controller, $responseMimeType = null) {
		$this->addRoute("post", $pathPattern, $controller, $responseMimeType);
	}

	protected final function put($pathPattern, $controller, $responseMimeType = null) {
		$this->addRoute("put", $pathPattern, $controller, $responseMimeType);
	}

	protected final function delete($pathPattern, $controller, $responseMimeType = null) {
		$this->addRoute("delete", $pathPattern, $controller, $responseMimeType);
	}

	protected final function getOrPost($pathPattern, $controller, $responseMimeType = null) {
		$this->addRoute(array("get", "post"), $pathPattern, $controller, $responseMimeType);
	}

	public function hasRoute($httpMethod, $pathPattern) {
		return $this->routeCollector->hasRoute($this->getRouteName($httpMethod, $pathPattern));
	}

	private function getRouteName($httpMethod, $pathPattern) {
		return strtolower($httpMethod).":".$pathPattern;
	}

	private function addRoute($httpMethod, $pathPattern, $controller, $responseMimeType = null) {

		// Allow supplying an array of http methods
		if (is_array($httpMethod)) {
			foreach ($httpMethod as $individualHttpMethod) {
				$this->addRoute($individualHttpMethod, $pathPattern, $controller, $responseMimeType);
			}
			return;
		}

		// Allow supplying an array of patterns
		if (is_array($pathPattern)) {
			foreach ($pathPattern as $individualPattern) {
				$this->addRoute($httpMethod, $individualPattern, $controller, $responseMimeType);
			}
			return;
		}

		// NB this validation is important as the $httpMethod is used to generate a
		// PHP method name to call on $this->routeCollector (see below)
		$validMethods = array("get", "post", "put", "delete", "options");
		if (!in_array($httpMethod, $validMethods)) {
			throw new Exception("Invalid HTTP method; expected one of the following: {".join(", ", $validMethods)."}");
		}

		if ($responseMimeType === null) {
			$responseMimeType = $this->defaultResponseMimeType;
		}

		// NB this assuming that the routeCollector has a suitable method with the same name as the $httpMethod, which
		// could potentially be a security or flakiness concern; therefore the validation above is important.
		$routeCollectorMethod = $httpMethod;

		$this->routeCollector->$routeCollectorMethod(array($pathPattern, $this->getRouteName($httpMethod, $pathPattern)), function() use ($responseMimeType, $controller) {
			header("Content-Type: ".$responseMimeType."; charset=utf-8");
			$this->runController($controller, array_map("rawurldecode", func_get_args()));
		});
	}

}