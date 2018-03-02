<?php
namespace WillV\Project\Tests\ArrayFiltererTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\ArrayFilterer;

class ArrayFiltererTest extends TestCase {
	public function testCreateShouldMakeANewArrayFilterer() {
		$filterer = ArrayFilterer::create();
		$this->assertTrue($filterer instanceof ArrayFilterer);
	}
	public function testFilterByKeyShouldFilterAnArrayByKey() {
		$filterer = ArrayFilterer::create();
		$inputArray = array(
			"a" => 1,
			"b" => 2,
			"c" => 3,
			"d" => 4,
			"e" => 5,
			"f" => 6
		);
		$outputArray = $filterer->filterByKey($inputArray, array("a", "c", "e"));
		$this->assertEquals($outputArray, array("a" => 1, "c" => 3, "e" => 5));
	}
}
