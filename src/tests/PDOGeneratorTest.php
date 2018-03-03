<?php
namespace WillV\Project\Tests\PDOGeneratorTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\PDOGenerator;

class TestPDOGenerator extends TestCase {
	public function testItShouldReturnAPDOGeneratorWhenCreateIsCalled() {
		$generator = PDOGenerator::create("testhostname", "testdatabasename", "testusername", "testpassword");
		$this->assertTrue($generator instanceof PDOGenerator);
	}

	private function create($omit = null) {
		$details = array(
			"hostname" => "testhostname",
			"databasename" => "testdatabasename",
			"username" => "testusername",
			"password" => "testpassword",
		);

		if (!empty($omit)) {
			$details[$omit] = null;
		}

		return PDOGenerator::create(
			$details["hostname"],
			$details["databasename"],
			$details["username"],
			$details["password"]
		);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't generate a PDO without a hostname, databasename, username, and password
     */
	public function testItShouldThrowAnExceptionWhenGettingAPDOIfHostnameIsMissing() {
		$this->createAndGet();
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
     * @expectedExceptionMessage Can't generate a PDO without a hostname, databasename, username, and password
     */
	public function testItShouldThrowAnExceptionWhenGettingAPDOIfDatabasenameIsMissing() {
		$this->createAndGet("databasename");
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't generate a PDO without a hostname, databasename, username, and password
     */
	public function testItShouldThrowAnExceptionWhenGettingAPDOIfUsernameIsMissing() {
		$this->createAndGet("username");
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can't generate a PDO without a hostname, databasename, username, and password
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
		$this->assertTrue($db instanceof \LoggedPDO\PDO);
	}

	public function testItShouldLogToFileIfSpecified() {
		$pdo = $this->createAndGet(null, "file");
		$this->assertTrue($db instanceof \LoggedPDO\PDO);
	}
}
