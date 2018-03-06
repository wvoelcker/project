<?php
namespace WillV\Project\Tests\DataMapper;
use PHPUnit\Framework\TestCase;
use WillV\Project\DataMapper\DataMapper;
use WillV\Project\DomainObject;
use WillV\Project\Dataset;

abstract class ExampleDataMapperType extends DataMapper {
	public $gotRows = array(), $countedRows = array(), $deletedById = array();
	public $rowsFetched = array(), $dateCreatedGot = array(), $savedSingle = array(), $savedMultiple = array();

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
		$this->rowsFetched[] = func_get_args();
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
}

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

class TestDataMapper extends TestCase {

	public function testItShouldFindAnObjectById() {
	}

	public function testItSHouldFindASingleObjectByCriteriaOtherThanId() {

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