<?php
namespace WillV\Project\Tests\AutoloaderSetTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\AutoloaderSet;
use UnitTests\DummyClassesForAutoloaders\DummyClassOne;

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
     * @runInSeparateProcess
     */
	public function testItShouldNotAutoloadClassesBeforeBeingRegistered() {
		$isAutoloaded = $this->createAutoloaderSetAndCreateTestClass(false);
		$this->assertFalse($isAutoloaded);
	}

    /**
     * @expectedException Error
     * @expectedExceptionMessage SomeClass' not found
     * @runInSeparateProcess
     */
	public function testItShouldAutoloadClassesAfterBeingRegistered() {
		$isAutoloaded = $this->createAutoloaderSetAndCreateTestClass(true);
		$this->assertTrue($isAutoloaded);
	}

    /**
     * @runInSeparateProcess
     */
	public function testItShouldSetUpADefaultAutoloaderMappingClassNamesToDirectories() {
		$autoLoaderSet = AutoloaderSet::create(__DIR__, "UnitTests", array("DummyClassesForAutoloaders"));
		$autoLoaderSet->register();
		$someObject = new DummyClassOne;
		$this->assertEquals(5, $someObject->getNumber5());
	}
}
