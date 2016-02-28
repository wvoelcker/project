<?php
namespace WillV\Project;

trait AbstractTemplate {
	protected $globalConfig, $configs = array();

	static public function create() {
		$className = get_called_class();
		$instance = new $className;
		call_user_func_array(array($instance, "preSetUp"), func_get_args());
		$instance->setUp();

		return $instance;
	}

	private function __construct() {}

	protected function preSetUp() {}
	
	abstract protected function setUp();
}

