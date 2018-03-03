<?php
/*
 * TODO:WV:20151218:Unit test this
 */
namespace WillV\Project;


/**
 * This class was designed as an easier alternative to long lists of Apache .htaccess redirects.
 * However, it can also be used for individual redirects, or short lists of redirects.
 * It will intelligently work through the redirect chain, and collapse it into a single redirect.
 *
 * To use it, override the class and call addRedirect repeatedly in the setUp method.
 */
abstract class Redirector {
	use Trait_AbstractTemplate;
	protected $redirects = array(), $defaultStatusCode = 302;

	public function redirect($fullRequestURL) {
		$have301 = false;

		$seen = array();
		$workingURL = $fullRequestURL;
		$numRedirects = count($this->redirects);

		for ($i = 0; $i < $numRedirects; $i++) {
			$redirect = $this->redirects[$i];

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

				// Iterate again from the start of the array, to avoid multiple-hop redirects
				$i = -1;
				continue;
			}
		}

		if ($workingURL != $fullRequestURL) {
			$this->doRedirect($workingURL, ($have301?301:302));
		}
	}

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