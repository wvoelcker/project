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
		$this->primaryDomainObject = "\WillV\Project\Tests\DataMapper\Item";
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

	// NOTE: These tests require the following user to be able to connect to the MySQL process and create and drop databases
	private $hostname = "localhost", $username = "phpunit", $password = "phpunit";

	// This will change each time the test is run
	private $databasename;

	protected function preSetUp() {
		parent::preSetUp();

	}

	public function setUpBeforeClass() {
		$pdo = $this->getPDO(false);
		$this->databasename = "test_".md5(microtime().rand());
		$this->prepareAndExecute($pdo, "CREATE DATABASE `".$this->databasename."`", array());
	}

	public function tearDownAfterClass() {
		$pdo = $this->getPDO(false);
		$this->prepareAndExecute($pdo, "DROP DATABASE `".$this->databasename."`", array());
	}

	private function getPDO($includeDB = true) {

		if (empty($includeDB)) {
			$generator = PDOGenerator::create(
				$this->hostname,
				$this->username,
				$this->password
			);			
		} else {
			$generator = PDOGenerator::create(
				$this->hostname,
				$this->username,
				$this->password,
				$this->databasename
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

		return $pdo;
	}

	private function prepareAndExecute($pdo, $query, $data) {
		$statement = $pdo->prepare($query);
		$statement->execute($data);

		return $statement;
	}

	public function setUp() {
		$testData = array(
			array("id" => 1, "size" => "medium", "name" => "thing1", "item_id" => "YWJjZGVm", "created_utc" => new \DateTime("@1518048000", new \DateTimeZone("UTC"))),
			array("id" => 2, "size" => "large", "name" => "thing2", "item_id" => "eng5ODcxYg==", "created_utc" => new \DateTime("@1518134400", new \DateTimeZone("UTC"))),
			array("id" => 4, "size" => "small", "name" => "thing3", "item_id" => "c2Rmc2s4NzIz", "created_utc" => new \DateTime("@1518220800", new \DateTimeZone("UTC"))),
			array("id" => 9, "size" => "large", "name" => "thing4", "item_id" => "ZGZjdg==", "created_utc" => new \DateTime("@1518220805", new \DateTimeZone("UTC"))),
		);

		$pdo = $this->getPDO();

		// Create temporary table in memory
		// Insert test data
	}

	public function tearDown() {
		// Drop temporary table
	}

	public function testItShouldFindAnObjectById() {
		$mapper = ItemMapper::create();
		$item = $mapper->findById(2);
		$this->assertEquals("thing2", $item->get("name"));
	}

	public function testItShouldFindASingleObjectByCriteriaOtherThanId() {
		$mapper = ItemMapper::create();
		$item = $mapper->findSingleFromCriteria(array("size" => "small"));
		$this->assertEquals("thing3", $item->get("name"));
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid sort direction (should be 'asc' or 'desc')
     */
	public function testItShouldThrowAnExceptionIfTheSortDirectionWasInvalidWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "sideways", 0, 10);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid offset
     */
	public function testItShouldThrowAnExceptionIfTheOffsetWasNotAnIntegerWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", "zero", 10);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid maximum results
     */
	public function testItShouldThrowAnExceptionIfTheMaximumNumberOfResultsWasNotAnIntegerWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", 0, "ten");
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Can only sort by application properties that directly map to database columns
     */
	public function testItShouldThrowAnExceptionIfAttemptingToSortByAPropertyNameThatDoesNotMapDirectlyToAColumnNameWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("itemId", "asc", 0, 10);
	}

	public function testItShouldAllowFilteringByCriteriaWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", 0, 10, array("size" => "large"));
		$this->assertEquals(2, count($items));
		$this->assertEquals(2, $items[0]->get("id"));
		$this->assertEquals(9, $items[1]->get("id"));
	}

	public function testItShouldAllowNotFilteringByCriteriaWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", 0, 10);
		$this->assertEquals(4, count($items));
		$this->assertEquals(1, $items[0]->get("id"));
		$this->assertEquals(2, $items[1]->get("id"));
		$this->assertEquals(4, $items[2]->get("id"));
		$this->assertEquals(9, $items[3]->get("id"));
	}

	public function testItShouldAllowForAnOffsetWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", 2, 10);
		$this->assertEquals(2, count($items));
		$this->assertEquals(4, $items[0]->get("id"));
		$this->assertEquals(9, $items[1]->get("id"));
	}

	public function testItShouldAllowForAMaxResultsNumberWhenGeneratingAPageOfObjects() {
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", 1, 2);
		$this->assertEquals(2, count($items));
		$this->assertEquals(2, $items[0]->get("id"));
		$this->assertEquals(4, $items[1]->get("id"));
	}

	public function testItShouldSortRowsByTheCorrectColumnWhenGeneratingAPageOfObjects() {
		$this->confirmSorting("asc");
	}

	private function confirmSorting($sortDir) {
		$mapper = ItemMapper::create();

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
		$mapper = ItemMapper::create();
		$items = $mapper->generatePage("id", "asc", 0, 2);
		$this->assertTrue($items[0] instanceof Item);
		$this->assertTrue($items[1] instanceof Item);
	}

	public function testItShouldMapDomainObjectPropertiesToDatabaseColumnsByDirectNameMappings() {
		$item = Item::create(array(
			"id" => 10,
			"size" => "medium",
			"name" => "thing5",
			"itemId" => "q1w2e3r4",
		));
		$mapper = ItemMapper::create();
		$mapper->save($item);
		$this->assertEquals(10, $mapper->testData[4]["id"]);
		$this->assertEquals("medium", $mapper->testData[4]["size"]);
		$this->assertEquals("thing5", $mapper->testData[4]["name"]);
	}

	public function testItShouldMapDomainObjectPropertiesToDatabaseColumnsByFunctions() {
		$item = Item::create(array(
			"id" => 10,
			"size" => "medium",
			"name" => "thing5",
			"itemId" => "q1w2e3r4",
		));
		$mapper = ItemMapper::create();
		$mapper->save($item);
		$this->assertEquals("cTF3MmUzcjQ=", $mapper->testData[4]["item_id"]);
	}

	public function testItShouldMapDatabaseColumnsToDomainObjectPropertiesByDirectNameMappings() {
		$mapper = ItemMapper::create();
		$item = $mapper->findById(2);
		$this->assertEquals(2, $item->get("id"));
		$this->assertEquals("thing2", $item->get("name"));
		$this->assertEquals("large", $item->get("size"));
	}

	public function testItShouldMapDatabaseColumnsToDomainObjectPropertiesByFunctions() {
		$mapper = ItemMapper::create();
		$item = $mapper->findById(2);
		$this->assertEquals("zx9871b", $item->get("itemId"));
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Cannot delete objects with no ID
     */
	public function testItShouldThrowAnExceptionWhenAttemptingToDeleteAnObjectWithNoId() {
		$item = Item::create(array(
			"size" => "medium",
			"name" => "thing9",
			"itemId" => "cn76sdfkj190",
		));
		$mapper = ItemMapper::create();
		$mapper->delete($item);
	}

	public function testItShouldDeleteAnObjectThatHasAnId() {
		$mapper = ItemMapper::create();
		$item = $mapper->findById(2);
		$mapper->delete($item);
		$index = $mapper->getIndexById(2);
		$this->assertEmpty($index);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Cannot get creation dates of objects with no ID
     */
	public function testItShouldThrowAnExceptionWhenAttemptingToGetTheCreationDateOfAnObjectWithNoId() {
		$item = Item::create(array(
			"size" => "medium",
			"name" => "thing9",
			"itemId" => "cn76sdfkj190",
		));
		$mapper = ItemMapper::create();
		$mapper->getDateCreated($item);
	}

	public function testItShouldReturnTheCreationDateOfAnObjectThatDoesHaveAnId() {
		$mapper = ItemMapper::create();
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
		$mapper = ItemMapper::create();
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
		$mapper = ItemMapper::create();
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
		$mapper = ItemMapper::create();
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
		$mapper = ItemMapper::create();
		$mapper->insert($items);
		$this->assertEquals(97, $mapper->testData[4]["id"]);
		$this->assertEquals(98, $mapper->testData[5]["id"]);
		$this->assertEquals(99, $mapper->testData[6]["id"]);
	}


}