<?php
namespace WillV\Project;

class DomainObject {
	use Trait_AbstractTemplate;
	protected $data = array(), $requiredFields = array();

	protected function postSetUp() {
		$data = func_get_arg(0);
		$notSupplied = array_diff($this->requiredFields, array_keys($data));
		if (!empty($notSupplied)) {
			throw new \Exception("Missing fields: {".join(", ", $notSupplied)."}");
		}
		$this->data = $data;
	}

}