<?php
namespace WillV\Project\Tests\PDOGeneratorTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\PDOGenerator;

class TestPDOGenerator extends TestCase {
	public function testItShouldReturnAPDOGeneratorWhenCreateIsCalled() {
		$generator = PDOGenerator::create("testhostname", "testdatabasename", "testusername", "testpassword");
		$this->assertTrue($generator instanceof PDOGenerator);
	}
}
