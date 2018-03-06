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
				"customValidation" => "ctype_digit",
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
			array("id" => "1", "size" => "medium", "name" => "thing1", "item_id" => "YWJjZGVm", "created_utc" => new \DateTime("@1518048000", new \DateTimeZone("UTC"))),
			array("id" => "2", "size" => "large", "name" => "thing2", "item_id" => "eng5ODcxYg==", "created_utc" => new \DateTime("@1518134400", new \DateTimeZone("UTC"))),
			array("id" => "4", "size" => "small", "name" => "thing3", "item_id" => "c2Rmc2s4NzIz", "created_utc" => new \DateTime("@1518220800", new \DateTimeZone("UTC"))),
		);
	}

	protected function getRows($sortCol, $sortDir, $offset, $maxResults, $criteria = array()) {
		$this->gotRows[] = func_get_args();
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
		$this->savedSingle[] = func_get_args();
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
						"value" => base64_encode($object->itemId)
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

	public function testItShouldThrowAnExceptionIfTheSortDirectionWasInvalidWhenGeneratingAPageOfObjects() {

	}

	public function testItShouldThrowAnExceptionIfTheOffsetWasNotAnIntegerWhenGeneratingAPageOfObjects() {

	}

	public function testItShouldThrowAnExceptionIfTheMaximumNumberOfResultsWasNotAnIntegerWhenGeneratingAPageOfObjects() {

	}

	public function testItShouldThrowAnExceptionIfAttemptingToSortByAPropertyNameThatDoesNotMapDirectlyToAColumnNameWhenGeneratingAPageOfObjects() {

	}

	public function testItShouldFetchTheCorrectRowsWhenGeneratingAPageOfObjects() {

	}

	public function testItShouldConvertTheDatabaseDataIntoDomainObjectsWhenGeneratingAPageOfObjects() {

	}

	public function testItShouldMapDomainObjectToDatabaseColumnsByDirectNameMappings() {

	}

	public function testItShouldMapDomainObjectPropertiesToDatabaseColumnsByFunctions() {

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

	public function testItShouldSaveAnObject() {

	}

	public function testItShouldInsertASingleObject() {

	}

	public function testItShouldInsertMultipleObjects() {

	}


}