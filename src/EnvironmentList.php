<?php
/*
 * TODO:WV:20151218:Document how to use this and Environment.php
 */
namespace WillV\Project;

abstract class EnvironmentList {
	use Trait_AbstractTemplate;
	protected $environments = array();

	public function addEnvironment($environmentName, Environment $environment) {
		if (isset($this->environments[$environmentName])) {
			throw new \Exception("Environment '".$environmentName."' already added");
		}
		$this->environments[$environmentName] = $environment;
	}

	public function findActiveEnvironment() {
		foreach ($this->environments as $environment) {
			if ($environment->isActive()) {
				return $environment;
			}
		}
	}

	public function getEnvironment($environmentName) {
		if (!isset($this->environments[$environmentName])) {
			throw new \Exception("Environment '".$environmentName."' not found");
		}
		return $this->environments[$environmentName];
	}

}