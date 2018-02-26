<?php
use PHPUnit\Framework\TestCase;
use WillV\Project\Controller;
use WillV\Project\Environment;

class DummyEnvironment extends Environment {
	protected function setUp() {}
}

class TestController extends TestCase {

	private function makeDummyEnvironment() {
		return DummyEnvironment::create(
			array(),
			function() {
				return true;
			}
		);
	}

	public function testCreateShouldMakeANewController() {
		$controller = Controller::create("/dev/null", $this->makeDummyEnvironment());
		$this->assertTrue($controller instanceof Controller);
	}

}