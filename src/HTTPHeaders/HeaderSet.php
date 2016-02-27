<?php
namespace WillV\Project\HTTPHeaders;

abstract class HeaderSet {
	protected $headers = array();

	private function __construct() {
	}

	static public function create() {
		$className = get_called_class();
		$headerset = new $className;
		$headerset->addHeaders();
		return $headerset;
	}

	abstract protected function addHeaders();

	public function send() {
		foreach ($this->headers as $name => $value) {
			header($name.": ".$value);
		}
	}
}
