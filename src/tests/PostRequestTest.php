<?php
namespace WillV\Project\Tests\PostRequestTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\PostRequest;

class TestPostRequest extends TestCase {

	public function testItShouldReturnNullWhenGeneratingDataFromIncomingJSONForGETRequests() {
		$output = PostRequest::dataFromJSON("GET", array(), "application/json", "{'a':'b'}");
		$this->assertNull($output);
	}

	public function testItShouldReturnNullWhenGeneratingDataFromIncomingJSONIfThereIsSomethingInThePOSTArray() {
		$output = PostRequest::dataFromJSON("POST", array("c" => "d"), "application/json", "{'a':'b'}");
		$this->assertNull($output);
	}

	public function testItShouldReturnNullWhenGeneratingDataFromIncomingJSONIfTheIncomingContentTypeIsNotApplicationJSON() {
		$output = PostRequest::dataFromJSON("POST", array(), "text/plain", "{'a':'b'}");
		$this->assertNull($output);
	}

	public function testItShouldReturnNullWhenGeneratingDataFromIncomingJSONIfTheIncomingContentBodyIsNotValidJSON() {
		$output = PostRequest::dataFromJSON("POST", array(), "application/json", "invalid-json");
		$this->assertNull($output);	
	}

	public function testItShouldReturnNullWhenGeneratingDataFromIncomingJSONIfTheIncomingContentBodyIsValidJSONButDoesNotEvaluateToAnAppropriateObjectOrArray() {
		$output = PostRequest::dataFromJSON("POST", array(), "application/json", "'somestring'");
		$this->assertNull($output);		
	}

	public function testItShouldParseAValidIncomingJSONRequestAndConvertItToAnArray() {
		$output = PostRequest::dataFromJSON("POST", array(), "application/json", json_encode(array("a" => "b")));
		$this->assertNotNull($output);
		$this->assertNotEmpty($output);
		$this->assertNotEmpty($output["a"]);
		$this->assertEquals("b", $output["a"]);
	}
}
