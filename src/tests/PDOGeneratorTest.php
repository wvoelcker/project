<?php
namespace WillV\Project\Tests\PDOGeneratorTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\PDOGenerator;

class TestPDOGenerator extends TestCase {

	// NOTE: These tests require the following user to be able to connect to the MySQL process
	private $hostname = "localhost", $username = "phpunit", $password = "phpunit";

	public function testItShouldReturnAPDOGeneratorWhenCreateIsCalled() {
		$generator = $this->create();
		$this->assertTrue($generator instanceof PDOGenerator);
	}

	private function create($omit = null) {
		$details = array(
			"hostname" => $this->hostname,
			"username" => $this->username,
			"password" => $this->password
		);

		if (!empty($omit)) {
			$details[$omit] = null;
		}

		return PDOGenerator::create(
			$details["hostname"],
			$details["username"],
			$details["password"]
		);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't generate a PDO without a hostname, username, and password
     */
	public function testItShouldThrowAnExceptionWhenGettingAPDOIfHostnameIsMissing() {
		$this->createAndGet("hostname");
	}

	private function createAndGet($omit = null, $logMode = null) {
		try {
			$generator = $this->create($omit);
			if (!empty($logMode)) {
				$generator->setLogMode($logMode);
			}
			$pdo = $generator->getPDO();
		} catch (\PDOException $e) {
			if ($e->getMessage() == "could not find driver") {
				$this->markTestSkipped("MySQL driver not available");
			} else {
				throw $e;
			}
		}

		return $pdo;
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't generate a PDO without a hostname, username, and password
     */
	public function testItShouldThrowAnExceptionWhenGettingAPDOIfUsernameIsMissing() {
		$this->createAndGet("username");
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't generate a PDO without a hostname, username, and password
     */
	public function testItShouldThrowAnExceptionWhenGettingAPDOIfPasswordIsMissing() {
		$this->createAndGet("password")->getPDO();
	}

	public function testItShouldGetAPDOIfConfigOK() {
		$pdo = $this->createAndGet();
		$this->assertTrue($pdo instanceof \PDO);
	}

	public function testItShouldLogToConsoleIfSpecified() {
		$pdo = $this->createAndGet(null, "console");
		$this->assertTrue($pdo instanceof \LoggedPDO\PDO);
		$properties = get_object_vars($pdo->getLogger());
		$this->assertEquals("console", $properties["_filename"]);
	}

	public function testItShouldLogToFileIfSpecified() {
		$pdo = $this->createAndGet(null, "file");
		$this->assertTrue($pdo instanceof \LoggedPDO\PDO);
		$properties = get_object_vars($pdo->getLogger());
		$this->assertEquals("file", $properties["_filename"]);
	}
}
