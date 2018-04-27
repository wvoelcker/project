<?php
namespace WillV\Project\HTTPHeaders;

class NoCache extends HeaderSet {
	protected function setUp() {
		$this->headers["Cache-Control"] = "private, no-cache, no-store, must-revalidate";
		$this->headers["Expires"] = "0";
	}
}
