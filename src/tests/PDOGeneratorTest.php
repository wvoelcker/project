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

	public function testItShouldGenerateTheCorrectConnectionStringIfThereIsNoDatabaseProvided() {
		$pdo = $this->createAndGet();
		$this->confirmHostnameAndUser($pdo);
	}

	private function confirmHostnameAndUser($pdo) {
		$statement = $this->prepareAndExecute($pdo, "select @@hostname", array());
		$results = $statement->fetchAll();
		$this->assertEquals(php_uname("n"), $results[0]["@@hostname"]);

		$statement = $this->prepareAndExecute($pdo, "select CURRENT_USER()", array());
		$results = $statement->fetchAll();
		$this->assertEquals($this->username."@".$this->hostname, $results[0]["CURRENT_USER()"]);
	}

	public function testItShouldGenerateTheCorrectConnectionStringIfThereIsADatabaseProvided() {
		$pdo = $this->createAndGet();
		$this->confirmHostnameAndUser($pdo);

		$dbname = "test_".md5(microtime().rand());

		// TODO:WV:20180303:Escape dbname for mysql
		$this->prepareAndExecute($pdo, "CREATE DATABASE `".$dbname."`", array());

		$pdoDB = PDOGenerator::create(
			$this->hostname,
			$this->username,
			$this->password,
			$dbname
		)->getPDO();

		$statement = $this->prepareAndExecute($pdoDB, "select DATABASE()", array());
		$results = $statement->fetchAll();
		$this->assertEquals($dbname, $results[0]["DATABASE()"]);

		// TODO:WV:20180303:Escape dbname for mysql
		$this->prepareAndExecute($pdo, "DROP DATABASE `".$dbname."`", array());
	}

	private function prepareAndExecute($pdo, $query, $data) {
		$statement = $pdo->prepare($query);
		$statement->execute($data);

		return $statement;
	}
}
