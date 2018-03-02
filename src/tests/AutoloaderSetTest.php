<?php
namespace WillV\Project\Tests\AutoloaderSetTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\AutoloaderSet;

class TestAutoLoaderSet extends AutoloaderSet {
	private $autoloaded = array();

	protected function setUp() {
		$that = $this;
		$this->addAutoloader(function($className) use ($that) {
			$that->autoloaded[] = $className;
		});
	}

	public function isAutoloaded($className) {
		return in_array($className, $this->autoloaded);
	}
}

class AutoloaderSetTest extends TestCase {

	private function createAutoloaderSetAndCreateTestClass($doRegister) {
		$autoLoaderSet = TestAutoLoaderSet::create("/dev/null");
		if ($doRegister) {
			$autoLoaderSet->register();			
		}
		$someObject = new SomeClass();
		$isAutoloaded = $autoLoaderSet->isAutoloaded("SomeClass");
		return $isAutoloaded;
	}

    /**
     * @expectedException Error
     * @expectedExceptionMessage SomeClass' not found
     */
	public function testItShouldNotAutoloadClassesBeforeBeingRegistered() {
		$isAutoloaded = $this->createAutoloaderSetAndCreateTestClass(false);
		$this->assertFalse($isAutoloaded);
	}

    /**
     * @expectedException Error
     * @expectedExceptionMessage SomeClass' not found
     */
	public function testItShouldAutoloadClassesAfterBeingRegistered() {
		$isAutoloaded = $this->createAutoloaderSetAndCreateTestClass(true);
		$this->assertTrue($isAutoloaded);
	}
}
