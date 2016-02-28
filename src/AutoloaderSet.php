<?php
namespace WillV\Project;

abstract class AutoloaderSet {
	use Trait_AbstractTemplate;
	protected $projectRoot, $autoloaders = array();

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

}