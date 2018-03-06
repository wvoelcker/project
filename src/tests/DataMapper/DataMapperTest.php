<?php
namespace WillV\Project\Tests\DataMapper;
use PHPUnit\Framework\TestCase;
use WillV\Project\DataMapper\DataMapper;
use WillV\Project\DomainObject;
use WillV\Project\Dataset;


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


abstract class ExampleDataMapperType extends DataMapper {
	public $gotRows = array(), $countedRows = array(), $deletedById = array();
	public $rowsFetched = array(), $dateCreatedGot = array(), $savedSingle = array(), $savedMultiple = array();

	public $testData = array();

	protected function preSetUp() {
		parent::preSetUp();
		$this->testData = array(
			array("id" => 1, "size" => "medium", "name" => "thing1", "item_id" => "YWJjZGVm", "created_utc" => new \DateTime("@1518048000", new \DateTimeZone("UTC"))),
			array("id" => 2, "size" => "large", "name" => "thing2", "item_id" => "eng5ODcxYg==", "created_utc" => new \DateTime("@1518134400", new \DateTimeZone("UTC"))),
			array("id" => 4, "size" => "small", "name" => "thing3", "item_id" => "c2Rmc2s4NzIz", "created_utc" => new \DateTime("@1518220800", new \DateTimeZone("UTC"))),
			array("id" => 9, "size" => "large", "name" => "thing4", "item_id" => "ZGZjdg==", "created_utc" => new \DateTime("@1518220805", new \DateTimeZone("UTC"))),
		);
	}

	protected function getRows($sortCol, $sortDir, $offset, $maxResults, $criteria = array()) {

		if ($sortCol != "id" and $sortCol != "size") {
			throw new \Exception("Sorting by columns other than id and size not supported in this test");
		}

		$output = $this->filter($this->testData, $criteria);

		usort($output, function($a, $b) use ($sortCol, $sortDir) {

			if (is_string($a[$sortCol])) {
				if ($sortDir == "asc") {
					return strcasecmp($a[$sortCol], $b[$sortCol]);
				} else {
					return strcasecmp($b[$sortCol], $a[$sortCol]);
				}
			} else {
				if ($sortDir == "asc") {
					return $b[$sortCol] - $a[$sortCol];
				} else {
					return $a[$sortCol] - $b[$sortCol];
				}
			}

		});

		$output = array_slice($output, $offset, $maxResults);

		return $output;
	}

	protected function countRows($criteria) {
		$this->countedRows[] = func_get_args();
	}

	protected function deleteById($id) {
		$this->deletedById[] = func_get_args();
	}

	protected function fetchRow($criteria) {
		$filtered = $this->filter($this->testData, $criteria);

		// TODO:WV:20180306:What to return if the filtered array is empty?  See also the MySQL Mapper.
		return $filtered[0];
	}

	protected function filter($data, $criteria) {
		return array_values(array_filter($data, function($v) use ($criteria) {
			foreach ($criteria as $key => $value) {
				if (!isset($v[$key]) or $v[$key] != $value) {
					return false;
				}
			}
			return true;
		}));
	}

	protected function getDateCreatedById($id) {
		$this->dateCreatedGot[] = func_get_args();
	}

	protected function doSave($object, $forceInsert = false) {
		$id = $object->get("id");
		$existingIndex = null;
		if (!empty($id)) {
			foreach ($this->testData as $i => $row) {
				if ($row["id"] == $object->get("id")) {
					$existingIndex = $i;
					break;
				}
			}
		}
		if (empty($id)) {
			echo "id: ".(max(array_map(function($v) { return $v["id"]; }, $this->testData)) + 1)."\n";
			$object->set("id", max(array_map(function($v) { return $v["id"]; }, $this->testData)) + 1);
		}
		$dataForDB = $this->mapFieldsToDatabase($object);
		if ($forceInsert or empty($existingIndex)) {
			$this->testData[] = $dataForDB;
		} else {
			$this->testData[$existingIndex] = $dataForDB;
		}
	}

	protected function doInsertMultiple($objects) {
		$this->savedMultiple[] = func_get_args();
	}
}

class ItemMapper extends ExampleDataMapperType {
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

	public function testItShouldMapDomainObjectToDatabaseColumnsByDirectNameMappings() {
		$item = Item::create(array(
			"id" => 10,
			"size" => "medium",
			"name" => "thing5",
			"itemId" => "q1w2e3r4",
		));
		$mapper = ItemMapper::create();
		$mapper->save($item);
		$this->assertTrue($mapper->testData[4]["id"] == 10);
		$this->assertTrue($mapper->testData[4]["size"] == "medium");
		$this->assertTrue($mapper->testData[4]["name"] == "thing5");
	}

	public function testItShouldMapDomainObjectPropertiesToDatabaseColumnsByFunctions() {
		$mapper = ItemMapper::create();
		$item = $mapper->findById(2);
		$this->assertEquals(2, $item->get("id"));
		$this->assertEquals("thing2", $item->get("name"));
		$this->assertEquals("large", $item->get("size"));
	}

	public function testItShouldMapDatabaseColumnsToDomainObjectPropertiesByDirectNameMappings() {

	}

	public function testItShouldMapDatabaseColumnsToDomainObjectPropertiesByFunctions() {

	}

	public function testItShouldThrowAnExceptionWhenAttemptingToDeleteAnObjectWithNoId() {

	}

	public function testItShouldDeleteAnObjectThatHasAnId() {

	}

	public function testItShouldReturnNullWhenAttemptingToGetTheCreationDateOfAnObjectWithNoId() {

	}

	public function testItShouldReturnTheCreationDateOfAnObjectThatDoesHaveAnId() {

	}

	public function testItShouldSaveAnObjectWithoutAnId() {

	}

	public function testItShouldSaveAnObjectWithAnId() {

	}

	public function testItShouldExplicitlyInsertASingleObject() {

	}

	public function testItShouldInsertMultipleObjects() {

	}


}