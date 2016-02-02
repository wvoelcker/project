<?php
namespace WillV\Project;

abstract class AutoloaderSet {
	protected $projectRoot, $autoloaders = array();

	static public function create($projectRoot) {
		$className = get_called_class();
		$set = new $className($projectRoot);
		$set->addAutoloaders();

		return $set;
	}

	protected function __construct($projectRoot) {
		$this->projectRoot = $projectRoot;
	}

	protected function addAutoloader($autoloader) {
		$this->autoloaders[] = $autoloader;
	}

	public function register() {
		foreach ($this->autoloaders as $autoloader) {
			spl_autoload_register($autoloader);
		}
	}

	abstract protected function addAutoloaders();

}