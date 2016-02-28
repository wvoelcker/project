<?php
namespace WillV\Project;

abstract class AutoloaderSet {
	use Trait_AbstractTemplate;
	protected $projectRoot, $autoloaders = array();

	protected function preSetUp() {
		$this->projectRoot = func_get_arg(0);
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