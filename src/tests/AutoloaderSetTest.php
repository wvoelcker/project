<?php
namespace WillV\Project\Tests\AutoloaderSetTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\AutoloaderSet;

class AutoloaderSetTest extends TestCase {

	private function createAutoloaderSetAndCreateTestClass($doRegister) {
		$autoLoaderSet = AutoloaderSet::create("/dev/null", "UnitTests", array());
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
