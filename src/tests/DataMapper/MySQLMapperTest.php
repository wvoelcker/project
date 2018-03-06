<?php
namespace WillV\Project\Tests\DataMapper\MySQLMapper;
use PHPUnit\Framework\TestCase;
use WillV\Project\DataMapper\MySQLMapper;
use WillV\Project\DomainObject;
use WillV\Project\Dataset;
use WillV\Project\PDOGenerator;

class Item extends DomainObject {
	protected function setUp() {
		$this->dataSetName = __NAMESPACE__."\ItemDataset";
	}
}

class ItemDataset extends Dataset {
	protected function setUp() {
		$this->fields = array(
			"id" => array(
				"customValidation" => function($v) { return ctype_digit((string)$v); },
			),
			"size" => array(
				"allowedValues" => array("small", "medium", "large"),
				"required" => true,
				"visibility" => "public",
			),
			"name" => array(
				"visibility" => "public",
			),
			"itemId" => array(
				"customValidation" => "is_string",
			)
		);
	}
}

class ItemMapper extends MySQLMapper {
	protected function setUp() {
		$this->primaryDomainObject = __NAMESPACE__."\Item";
		$this->primaryDatabaseTable = "items";
	}

	protected function getColumnMappings() {
		return array(
			"id" => "id",
			"size" => "size",
			"name" => "name",
			"itemId" => array(
				function($object) {
					return array(
						"key" => "item_id",
						"value" => base64_encode($object->get("itemId"))
					);
				},
				function($row) {
					return array(
						"key" => "itemId",
						"value" => base64_decode($row["item_id"])
					);
				}
			)
		);
	}
}

class TestDataMapper extends TestCase {

	// NOTE: These tests require the following user to be able to connect to the MySQL process and create and drop databases, and to create temporary tables
	static private $hostname = "localhost", $username = "phpunit", $password = "phpunit";

	// This will change each time the test is run
	static private $databasename;

	protected function preSetUp() {
		parent::preSetUp();

	}

	static public function setUpBeforeClass() {
		$pdo = self::getPDO(false);
		self::$databasename = "test_".md5(microtime().rand());
		self::prepareAndExecute($pdo, "CREATE DATABASE `".self::$databasename."`", array());
	}

	static public function tearDownAfterClass() {
		$pdo = self::getPDO(false);
		self::prepareAndExecute($pdo, "DROP DATABASE `".self::$databasename."`", array());
	}

	static private function getPDO($includeDB = true) {
		static $pdos = array();

		if (empty($pdos[(bool)$includeDB])) {

			if (empty($includeDB)) {
				$generator = PDOGenerator::create(
					self::$hostname,
					self::$username,
					self::$password
				);
			} else {
				$generator = PDOGenerator::create(
					self::$hostname,
					self::$username,
					self::$password,
					self::$databasename
				);
			}

			try {
				$pdo = $generator->getPDO();
			} catch (\PDOException $e) {
				if ($e->getMessage() == "could not find driver") {
					$this->markTestSkipped("MySQL driver not available");
				} else {
					throw $e;
				}
			}

			$pdos[(bool)$includeDB] = $pdo;
		}

		return $pdos[(bool)$includeDB];
	}

	static private function prepareAndExecute($pdo, $query, $data = array()) {
		$statement = $pdo->prepare($query);
		$statement->execute($data);

		return $statement;
	}

	public function setUp() {
		$testData = array(
			array("id" => 1, "size" => "medium", "name" => "thing1", "item_id" => "YWJjZGVm", "created_utc" => "2018-02-08 00:00:00", "updated_utc" => "2018-02-08 00:10:00"),
			array("id" => 2, "size" => "large", "name" => "thing2", "item_id" => "eng5ODcxYg==", "created_utc" => "2018-03-08 00:00:00", "updated_utc" => "2018-03-08 20:00:00"),
			array("id" => 4, "size" => "small", "name" => "thing3", "item_id" => "c2Rmc2s4NzIz", "created_utc" => "2018-04-08 00:00:00", "updated_utc" => "2018-04-08 00:30:00"),
			array("id" => 9, "size" => "large", "name" => "thing4", "item_id" => "ZGZjdg==", "created_utc" => "2018-05-08 00:00:00", "updated_utc" => "2018-05-08 00:40:00"),
		);

		$pdo = self::getPDO();
		self::prepareAndExecute($pdo, "
			CREATE TEMPORARY TABLE `users` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) DEFAULT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MEMORY
		");

		self::prepareAndExecute($pdo, "
			CREATE TEMPORARY TABLE `items` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`size` varchar(50) DEFAULT NULL,
				`name` varchar(100) DEFAULT NULL,
				`item_id` varchar(50) DEFAULT NULL,
				`created_utc` datetime DEFAULT NULL,
				`updated_utc` datetime DEFAULT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MEMORY
		");

		foreach ($testData as $testDatum) {
			self::prepareAndExecute($pdo, "INSERT INTO items SET id=:id, size=:size, name=:name, item_id=:item_id, created_utc=:created_utc, updated_utc=:updated_utc", $testDatum);
		}
	}

	public function tearDown() {
		$pdo = self::getPDO();
		self::prepareAndExecute($pdo, "DROP TABLE `users`");
		self::prepareAndExecute($pdo, "DROP TABLE `items`");
	}

	public function testItShouldFindAnObjectById() {
		$mapper = $this->getMapper();
		$item = $mapper->findById(2);
		$this->assertEquals("thing2", $item->get("name"));
	}

	private function getMapper() {
		$pdo = self::getPDO();
		return ItemMapper::create($pdo);
	}

	// TODO:WV:20180306:Test setting and maintaining creation and updated dates, somehow

	public function testItShouldFindASingleObjectByCriteriaOtherThanId() {
		$mapper = $this->getMapper();
		$item = $mapper->findSingleFromCriteria(array("size" => "small"));
		$this->assertEquals("thing3", $item->get("name"));
	}

	public function testItShouldNotThrowAnExceptionIfThereWereNoResultsWhenGeneratingAPageOfObjects() {
		$mapper = $this->getMapper();
		$e = null;
		try {
			$items = $mapper->generatePage("id", "asc", 0, 10, array("name" => "non-existent item"));
		} catch (\Exception $e) {
			// Do nothing
		}
		$this->assertEmpty($e);
	}

	public function testItShouldAllowFilteringByCriteriaWhenGeneratingAPageOfObjects() {
		$mapper = $this->getMapper();
		$items = $mapper->generatePage("id", "asc", 0, 10, array("size" => "large"));
		$this->assertEquals(2, count($items));
		$this->assertEquals(2, $items[0]->get("id"));
		$this->assertEquals(9, $items[1]->get("id"));
	}

	public function testItShouldAllowNotFilteringByCriteriaWhenGeneratingAPageOfObjects() {
		$mapper = $this->getMapper();
		$items = $mapper->generatePage("id", "asc", 0, 10);
		$this->assertEquals(4, count($items));
		$this->assertEquals(1, $items[0]->get("id"));
		$this->assertEquals(2, $items[1]->get("id"));
		$this->assertEquals(4, $items[2]->get("id"));
		$this->assertEquals(9, $items[3]->get("id"));
	}

	public function testItShouldAllowForAnOffsetWhenGeneratingAPageOfObjects() {
		$mapper = $this->getMapper();
		$items = $mapper->generatePage("id", "asc", 2, 10);
		$this->assertEquals(2, count($items));
		$this->assertEquals(4, $items[0]->get("id"));
		$this->assertEquals(9, $items[1]->get("id"));
	}

	public function testItShouldAllowForAMaxResultsNumberWhenGeneratingAPageOfObjects() {
		$mapper = $this->getMapper();
		$items = $mapper->generatePage("id", "asc", 1, 2);
		$this->assertEquals(2, count($items));
		$this->assertEquals(2, $items[0]->get("id"));
		$this->assertEquals(4, $items[1]->get("id"));
	}

	public function testItShouldSortRowsByTheCorrectColumnWhenGeneratingAPageOfObjects() {
		$this->confirmSorting("asc");
	}

	private function confirmSorting($sortDir) {
		$mapper = $this->getMapper();

		$items = $mapper->generatePage("size", $sortDir, 0, 10);
		$this->assertEquals(4, count($items));

		if ($sortDir == "asc") {
			$this->assertEquals("large", $items[0]->get("size"));
			$this->assertEquals("large", $items[1]->get("size"));
			$this->assertEquals("medium", $items[2]->get("size"));
			$this->assertEquals("small", $items[3]->get("size"));
		} else {
			$this->assertEquals("small", $items[0]->get("size"));
			$this->assertEquals("medium", $items[1]->get("size"));
			$this->assertEquals("large", $items[2]->get("size"));
			$this->assertEquals("large", $items[3]->get("size"));
		}
	}

	public function testItShouldSupportSortingRowsInBothAscendingAndDescendingOrderWhenGeneratingAPageOfObjects() {
		$this->confirmSorting("asc");
		$this->confirmSorting("desc");
	}

	public function testItShouldConvertTheDatabaseDataIntoDomainObjectsWhenGeneratingAPageOfObjects() {
		$mapper = $this->getMapper();
		$items = $mapper->generatePage("id", "asc", 0, 2);
		$this->assertTrue($items[0] instanceof Item);
		$this->assertTrue($items[1] instanceof Item);
	}

	public function testItShouldDeleteAnObjectThatHasAnId() {
		$mapper = $this->getMapper();
		$item = $mapper->findById(2);
		$mapper->delete($item);
		$index = $mapper->getIndexById(2);
		$this->assertEmpty($index);
	}

	public function testItShouldReturnTheCreationDateOfAnObjectThatDoesHaveAnId() {
		$mapper = $this->getMapper();
		$item = $mapper->findById(2);
		$dateTime = $mapper->getDateCreated($item);
		$this->assertEquals(1518134400, $dateTime->format("U"));
	}

	public function testItShouldSaveAnObjectWithoutAnId() {
		$item = Item::create(array(
			"size" => "medium",
			"name" => "thing5",
			"itemId" => "q1w2e3r4",
		));
		$mapper = $this->getMapper();
		$mapper->save($item);
		$this->assertEquals("thing5", $mapper->testData[4]["name"]);
	}

	public function testItShouldSaveAnObjectWithAnId() {
		$item = Item::create(array(
			"id" => 97,
			"size" => "medium",
			"name" => "thing6",
			"itemId" => "q1w2e3r4",
		));
		$mapper = $this->getMapper();
		$mapper->save($item);
		$this->assertEquals(97, $mapper->testData[4]["id"]);
	}

	public function testItShouldExplicitlyInsertASingleObject() {
		$item = Item::create(array(
			"id" => 97,
			"size" => "medium",
			"name" => "thing6",
			"itemId" => "q1w2e3r4",
		));
		$mapper = $this->getMapper();
		$mapper->insert($item);
		$this->assertEquals(97, $mapper->testData[4]["id"]);
	}

	public function testItShouldInsertMultipleObjects() {
		$items = array();
		$items[] = Item::create(array(
			"id" => 97,
			"size" => "medium",
			"name" => "thing6",
			"itemId" => "q1w2e3r4",
		));
		$items[] = Item::create(array(
			"id" => 98,
			"size" => "medium",
			"name" => "thing7",
			"itemId" => "mxncvsdjhf",
		));

		// TODO:WV:20180306:Confirm desired behaviour if one item doesn't have an ID
		$items[] = Item::create(array(
			"size" => "medium",
			"name" => "thing8",
			"itemId" => "pksjshdf87123",
		));
		$mapper = $this->getMapper();
		$mapper->insert($items);
		$this->assertEquals(97, $mapper->testData[4]["id"]);
		$this->assertEquals(98, $mapper->testData[5]["id"]);
		$this->assertEquals(99, $mapper->testData[6]["id"]);
	}


}