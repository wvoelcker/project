<?php
namespace WillV\Project\Tests\JSONResponseTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\JSONResponse;
use WillV\Project\HTTPHeaders\HeaderSet;

class ExampleHeaderSet extends HeaderSet {
	protected function setUp() {
		$this->headers["X-TEST-HEADER"] = "test-header-value";
	}
}

class TestJSONResponse extends TestCase {
	public function testItShouldNotThrowAnExceptionIfTheStatusCodeWas200() {
		$this->confirmNoException(200);
	}

	private function confirmNoException($statusCode) {
		$e = null;
		try {
			$response = JSONResponse::create(array("testKey" => "testValue"), $statusCode);	
		} catch (\Exception $e) {
			// Do nothing
		}

		$this->assertEquals(null, $e);
	}

	public function testItShouldNotThrowAnExceptionIfTheStatusCodeWas400() {
		$this->confirmNoException(400);
	}

	public function testItShouldNotThrowAnExceptionIfTheStatusCodeWas403() {
		$this->confirmNoException(403);
	}

	public function testItShouldNotThrowAnExceptionIfTheStatusCodeWas404() {
		$this->confirmNoException(404);
	}

	public function testItShouldNotThrowAnExceptionIfTheStatusCodeWas500() {
		$this->confirmNoException(500);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid status code
     */
	public function testItShouldThrowAnExceptionIfThesStatusCodeWasInvalid() {
		$response = JSONResponse::create(array("testKey" => "testValue"), 418);	
	}

	public function testItShouldReturnAJSONResponseObjectWhenCreateIsCalled() {
		$response = JSONResponse::create(array("testKey" => "testValue"), 200);
		$this->assertTrue($response instanceof JSONResponse);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldCreateAnObjectWithStatusValidationErrorWhenCallingCreateFromValidationErrors() {
		$response = JSONResponse::createFromValidationErrors(array("example-field" => "This field was invalid"));

		ob_start();
		$response->send();
		$output = @json_decode(ob_get_contents());
		ob_end_clean();

		$this->assertTrue(!empty($output));
		$this->assertTrue(is_object($output));
		$this->assertObjectHasAttribute("status", $output);
		$this->assertEquals("validationError", $output->status);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldCreateAnObjectContainingTheAppropriateErrorMessagesWhenCallingCreateFromValidationErrors() {
		$response = JSONResponse::createFromValidationErrors(array("example-field" => "This field was invalid"));

		ob_start();
		$response->send();
		$output = @json_decode(ob_get_contents());
		ob_end_clean();

		$this->assertTrue(!empty($output));
		$this->assertTrue(is_object($output));
		$this->assertObjectHasAttribute("errors", $output);
		$this->assertObjectHasAttribute("example-field", $output->errors);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSendTheCorrectCustomHeaders() {
		if (!function_exists("xdebug_get_headers")) {
			$this->markTestSkipped("XDebug is not available, so not able to verify headers");
		}
		$response = JSONResponse::createFromValidationErrors(array("example-field" => "This field was invalid"));
		$response->addHeaders(ExampleHeaderSet::create());

		ob_start();
		$response->send();
		ob_end_clean();
		$headersSent = xdebug_get_headers();

		$this->assertTrue(is_array($headersSent));
		$this->assertTrue(in_array("X-TEST-HEADER: test-header-value", $headersSent));
	}


    /**
    * @runInSeparateProcess
    */
	public function testItShouldSendTheCorrectStatusCodeForValidationErrors() {
		$response = JSONResponse::createFromValidationErrors(array("example-field" => "This field was invalid"));
		$this->confirmResponseCodeSent($response, 400);
	}

	private function confirmResponseCodeSent($response, $expectedCode) {
		ob_start();
		$response->send();
		ob_end_clean();

		$this->assertEquals($expectedCode, http_response_code());
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSendTheSpecifiedStatusCode() {
		$response = JSONResponse::create(array("message" => "You are not allowed to access that response"), 403);
		$this->confirmResponseCodeSent($response, 403);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSendADefaultStatusCodeOf200() {
		$response = JSONResponse::create(array("message" => "You are not allowed to access that response"));
		$this->confirmResponseCodeSent($response, 200);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldSendTheCorrectCustomData() {
		$response = JSONResponse::create(array("testKey" => "testValue"));

		ob_start();
		$response->send();
		$output = @json_decode(ob_get_contents());
		ob_end_clean();

		$this->assertTrue(!empty($output));
		$this->assertTrue(is_object($output));
		$this->assertEquals(1, count((array)$output));
		$this->assertObjectHasAttribute("testKey", $output);
		$this->assertEquals("testValue", $output->testKey);
	}
}
