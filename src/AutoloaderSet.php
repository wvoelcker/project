<?php
namespace WillV\Project;

class AutoloaderSet {
	use Trait_AbstractTemplate;
	protected $projectRoot, $rootNamespace, $namespaceDirectories;
	protected $autoloaders = array();

	protected function preSetUp() {
		$this->projectRoot = func_get_arg(0);
		$this->rootNamespace = func_get_arg(1);
		$this->namespaceDirectories = func_get_arg(2);		
	}

	protected function setUp() {
		$this->addAutoloader(function($className) {
			$classPath = $this->getClassPath($className);
			if (!empty($classPath) and file_exists($classPath)) {
				require_once $classPath;
			}
		});
	}

	private function getClassPath($className) {

		$classDetails = explode("\\", $className);
		$numClassDetails = count($classDetails);

		if (($numClassDetails != 3) or ($classDetails[0] != $this->rootNamespace)) {
			return null;
		}

		if (!in_array($classDetails[1], $this->namespaceDirectories)) {
			throw new \Exception("Unknown subnamespace '".$classDetails[1]."'");
		}

		return $this->projectRoot."/".strtolower($classDetails[1])."/".$classDetails[2].".php";
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