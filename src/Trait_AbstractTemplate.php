<?php
namespace WillV\Project;

trait Trait_AbstractTemplate {
	static public function create() {
		$className = get_called_class();
		$instance = new $className;
		call_user_func_array(array($instance, "preSetUp"), func_get_args());
		$instance->setUp();
		call_user_func_array(array($instance, "postSetUp"), func_get_args());

		return $instance;
	}

	/* TODO:WV:20161129:Consider adding a 'final' keyword in the line below.  Would it break anything? */
	private function __construct() {}

	protected function preSetUp() {}
	protected function postSetUp() {}
	
	abstract protected function setUp();
}

