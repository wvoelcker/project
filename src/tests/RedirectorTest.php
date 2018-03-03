<?php
namespace WillV\Project\Tests\Redirector;
use PHPUnit\Framework\TestCase;
use WillV\Project\Redirector;

class ExampleRedirector extends Redirector {
	protected function setUp() {
		$this->addRedirect("/^http:\/\/tests.example.com\/step1$/", "http://tests.example.com/step2");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2$/", "http://tests.example.com/step3");
		$this->addRedirect("/^http:\/\/tests.example.com\/step3$/", "http://tests.example.com/step4");
		$this->addRedirect("/^http:\/\/tests.example.com\/step4$/", "http://tests.example.com/step5");
	}
}

class ExampleRedirectorWith301 extends Redirector {
	protected function setUp() {
		$this->addRedirect("/^http:\/\/tests.example.com\/step1$/", "http://tests.example.com/step2");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2$/", "http://tests.example.com/step3");
		$this->addRedirect("/^http:\/\/tests.example.com\/step3$/", "http://tests.example.com/step4", 301);
		$this->addRedirect("/^http:\/\/tests.example.com\/step4$/", "http://tests.example.com/step5");
	}
}

class ExampleRedirectorWith301DefaultStatusCode extends Redirector {

	protected function setUp() {
		$this->setDefaultStatusCode(301);
		$this->addRedirect("/^http:\/\/tests.example.com\/step1$/", "http://tests.example.com/step2");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2$/", "http://tests.example.com/step3");
		$this->addRedirect("/^http:\/\/tests.example.com\/step3$/", "http://tests.example.com/step4");
		$this->addRedirect("/^http:\/\/tests.example.com\/step4$/", "http://tests.example.com/step5");
	}
}

class ExampleRedirectorWithCircularRedirect extends Redirector {
	protected function setUp() {
		$this->addRedirect("/^http:\/\/tests.example.com\/step1$/", "http://tests.example.com/step2");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2$/", "http://tests.example.com/step3");
		$this->addRedirect("/^http:\/\/tests.example.com\/step3$/", "http://tests.example.com/step1");
		$this->addRedirect("/^http:\/\/tests.example.com\/step4$/", "http://tests.example.com/step5");
	}
}

class ExampleRedirectorWithEarlierMatchingAfterLaterHasChanged extends Redirector {
	protected function setUp() {
		$this->addRedirect("/^http:\/\/tests.example.com\/step1$/", "http://tests.example.com/step2");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2$/", "http://tests.example.com/step3");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2\/after-changed$/", "http://tests.example.com/step3/after-changed");
		$this->addRedirect("/^http:\/\/tests.example.com\/step3$/", "http://tests.example.com/step2/after-changed");
		$this->addRedirect("/^http:\/\/tests.example.com\/step4$/", "http://tests.example.com/step5");
	}
}

class TestRedirector extends TestCase {

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Circular redirect detected
     */
	public function testItShouldDetectCircularRedirects() {
		ExampleRedirectorWithCircularRedirect::create()->redirect("http://tests.example.com/step1");
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldUseA301RedirectIfASingleRedirectAnywhereInTheChainIsA301() {
		ob_start();
		ExampleRedirectorWith301::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$this->assertEquals(301, http_response_code());
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldUseA302RedirectIfThereAreNo301RedirectsInTheChain() {
		ob_start();
		ExampleRedirector::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$this->assertEquals(302, http_response_code());
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldDetectIfARedirectEarlierInTheChainMatchesTheURLAfterALaterRedirectHasChangedItAndChangeTheURLAppropriately() {
		if (!function_exists("xdebug_get_headers")) {
			$this->markTestSkipped("XDebug is not available, so not able to verify headers");
		}

		ob_start();
		ExampleRedirectorWithEarlierMatchingAfterLaterHasChanged::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$headersSent = xdebug_get_headers();
		$this->assertTrue(in_array("Location: http://tests.example.com/step3/after-changed", $headersSent));
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldPerformAnActualRedirectIfTheConfiguredRedirectsChangeTheURL() {
		if (!function_exists("xdebug_get_headers")) {
			$this->markTestSkipped("XDebug is not available, so not able to verify headers");
		}

		ob_start();
		ExampleRedirector::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$responseCode = http_response_code();
		$this->assertEquals(302, $responseCode);

		$headersSent = xdebug_get_headers();
		$this->assertTrue(in_array("Location: http://tests.example.com/step5", $headersSent));
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldNotAttemptToPerformARedirectIfTheConfiguredRedirectsDoNotMatchTheURL() {
		ob_start();
		ExampleRedirector::create()->redirect("http://does.not.match.example.com/step1");
		ob_end_clean();

		$responseCode = http_response_code();
		$this->assertEmpty($responseCode);
	}

    /**
    * @runInSeparateProcess
    */
	public function testItShouldUse302AsDefaultRedirectTypeIfNoneIsSpecified() {
		ob_start();
		ExampleRedirector::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$responseCode = http_response_code();
		$this->assertEquals(302, $responseCode);
	}

    /**
    * @runInSeparateProcess
    */
	public function testIfTheRedirectorClassHasADifferentDefaultRedirectTypeItShouldUseThatInsteadOf302AsTheDefaultRedirectType() {
		ob_start();
		ExampleRedirectorWith301DefaultStatusCode::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$responseCode = http_response_code();
		$this->assertEquals(301, $responseCode);
	}

    /**
     * @runInSeparateProcess
     */
	public function testItShouldSetAnAppropriateHTTPStatus() {
		ob_start();
		ExampleRedirector::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$responseCode = http_response_code();
		$this->assertEquals(302, $responseCode);
	}

    /**
     * @runInSeparateProcess
     */
	public function testItShouldSendAnAppropriateHTTPLocationHeader() {
		if (!function_exists("xdebug_get_headers")) {
			$this->markTestSkipped("XDebug is not available, so not able to verify headers");
		}

		ob_start();
		ExampleRedirector::create()->redirect("http://tests.example.com/step1");
		ob_end_clean();

		$headersSent = xdebug_get_headers();
		$this->assertTrue(in_array("Location: http://tests.example.com/step5", $headersSent));
	}

}
