<?php
namespace WillV\Project;

class TemplateEngine {
	static protected $templatesDirectory = "templates";

	static public function create($projectRoot) {

		$mustache = new \Mustache_Engine(array(
			"loader" => new \Mustache_Loader_FilesystemLoader($projectRoot."/".static::$templatesDirectory),
		));

		return $mustache;
	}

}