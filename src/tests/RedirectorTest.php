<?php
namespace WillV\Project\Tests\Redirector;
use PHPUnit\Framework\TestCase;
use WillV\Project\Redirector;

class ExampleRedirector extends Redirector {
	protected function setUp() {
		$this->addRedirect("/^http:\/\/tests.example.com\/step1$/", "http://tests.example.com/step2");
		$this->addRedirect("/^http:\/\/tests.example.com\/step2$/", "http://tests.example.com/step3");
		$this->addRedirect("/^http:\/\/tests.example.com\/step3$/", "http://tests.example.com/step1");
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

class TestRedirector extends TestCase {

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Circular redirect detected
     */
	public function testItShouldDetectCircularRedirects() {
		$redirector = ExampleRedirectorWithCircularRedirect::create()->redirect("http://tests.example.com/step1");
	}


	public function testItShouldUseA301RedirectIfASingleRedirectAnywhereInTheChainIsA301() {

	}

	public function testItShouldUseA302RedirectIfThereAreNo301RedirectsInTheChain() {

	}

	public function testItShouldDetectIfARedirectEarlierInTheChainMatchesTheURLAfterALaterRedirectHasChangedItAndChangeTheURLAppropriately() {

	}

	public function testItShouldPerformAnActualRedirectIfTheConfiguredRedirectsChangeTheURL() {

	}

	public function testItShouldNotAttemptToPerformARedirectIfTheConfiguredRedirectsDoNotMatchTheURL() {

	}

	public function testItShouldNotAttemptToPerformARedirectIfTheConfiguredRedirectsMatchTheURLButDoNotChangeIt() {

	}

	public function testItShouldUse302AsDefaultRedirectTypeIfNoneIsSpecified() {

	}

	public function testIfTheRedirectorClassHasADifferentDefaultRedirectTypeItShouldUseThatInsteadOf302AsTheDefaultRedirectType() {

	}

	public function testIfTheInstantiatedRedirectorObjectsHasADifferentDefaultRedirectTypeItShouldUseThatInsteadOf302AsTheDefaultRedirectType() {

	}

	public function testItShouldThrowAnExceptionIfTheStatusCodeWasNotValid() {

	}

	public function testItShouldSetAnAppropriateHTTPStatus() {

	}

	public function testItShouldSendAnAppropriateHTTPLocationHeader() {

	}

}
