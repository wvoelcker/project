<?php
namespace WillV\Project;

abstract class DataMapper {
	use Trait_AbstractTemplate;
	protected $db, $primaryDomainObject;

	protected function preSetUp() {
		$db = func_get_arg(0);
		$this->db = $db;
	}

}
