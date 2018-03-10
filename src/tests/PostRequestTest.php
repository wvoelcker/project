<?php
namespace WillV\Project\Tests\PostRequestTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\PostRequest;

class TestPostRequest extends TestCase {

	public function testItShouldReturnTheInputArrayWhenGeneratingDataFromIncomingJSONForGETRequests() {
		$output = PostRequest::dataFromJSON("GET", array(), "{'a':'b'}");
		$this->assertEquals(array(), $output);
	}

	public function testItShouldReturnTheInputArrayWhenGeneratingDataFromIncomingJSONIfThereIsSomethingInThePOSTArray() {
		$output = PostRequest::dataFromJSON("POST", array("c" => "d"), "{'a':'b'}");
		$this->assertEquals(array("c" => "d"), $output);
	}

	public function testItShouldReturnTheInputArrayWhenGeneratingDataFromIncomingJSONIfTheIncomingContentBodyIsNotValidJSON() {
		$output = PostRequest::dataFromJSON("POST", array(), "invalid-json");
		$this->assertEquals(array(), $output);
	}

	public function testItShouldReturnTheInputArrayWhenGeneratingDataFromIncomingJSONIfTheIncomingContentBodyIsValidJSONButDoesNotEvaluateToAnAppropriateObjectOrArray() {
		$output = PostRequest::dataFromJSON("POST", array(), "'somestring'");
		$this->assertEquals(array(), $output);
	}

	public function testItShouldParseAValidIncomingJSONRequestAndConvertItToAnArray() {
		$output = PostRequest::dataFromJSON("POST", array(), json_encode(array("a" => "b")));
		$this->assertNotNull($output);
		$this->assertNotEmpty($output);
		$this->assertNotEmpty($output["a"]);
		$this->assertEquals("b", $output["a"]);
	}
}
