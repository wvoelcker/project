<?php
/*
 * TODO:WV:20151218:Unit test this
 */
namespace WillV\Project;

abstract class Redirector {
	protected $redirects = array(), $defaultStatusCode = 302;

	static public function create() {
		$className = get_called_class();
		$redirector = new $className;
		$redirector->addRedirects();

		return $redirector;
	}

	private function __construct() {
	}

	public function redirect($fullRequestURL) {
		$have301 = false;

		$seen = array();
		$workingURL = $fullRequestURL;
		foreach ($this->redirects as $i => $redirect) {

			// Detect circular redirects, and throw an exception if one is found
			if (isset($seen[$i]) and in_array($workingURL, $seen[$i])) {
				throw new \Exception("Circular redirect detected");
			}
			if (!isset($seen[$i])) {
				$seen[$i] = array();
			}
			$seen[$i][] = $workingURL;

			// Adapt URL if a matching redirect was found
			if (preg_match($redirect["from"], $workingURL)) {
				$workingURL = preg_replace($redirect["from"], $redirect["to"], $workingURL);
				if ($redirect["statuscode"] == 301) {
					$have301 = true;
				}

				// Iterate again from the start of the array; to avoid multiple-hop redirects
				reset($this->redirects);
			}
		}

		if ($workingURL != $fullRequestURL) {
			$this->doRedirect($workingURL, ($have301?301:302));
		}
	}

	abstract protected function addRedirects();

	public function addRedirect($from, $to, $statuscode = null) {
		if (empty($statuscode)) {
			$statuscode = $this->defaultStatusCode;
		}
		$this->redirects[] = array("from" => $from, "to" => $to, "statuscode" => $statuscode);

		return $this;
	}

	public function setDefaultStatusCode($newStatusCode) {
		$this->defaultStatusCode = $newStatusCode;

		return $this;
	}

	public function doRedirect($newURL, $statuscode) {
		if (!in_array($statuscode, array(301, 302))) {
			throw new \Exception("Invalid status code '".$statuscode."'");
		}

		header("HTTP/1.1 ".$statuscode." ".(($statuscode == 301?"Moved Permamently":"Found")));
		header("Location: ".$newURL);
		exit;
	}

}