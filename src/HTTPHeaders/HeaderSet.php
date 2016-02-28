<?php
namespace WillV\Project\HTTPHeaders;

abstract class HeaderSet {
	use \WillV\Project\Trait_AbstractTemplate;
	protected $headers = array();

	public function send() {
		foreach ($this->headers as $name => $value) {
			header($name.": ".$value);
		}
	}
}
