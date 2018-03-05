<?php
namespace WillV\Project\Tests\DataMapper;
use PHPUnit\Framework\TestCase;
use WillV\Project\DataMapper\DataMapper;

class ArrayDBMapper extends DataMapper {
	protected $data;

	protected function preSetUp() {
		parent::preSetUp();
		$this->data = array(
			"customers" => array(
				array("id" => 1, "name" => "Alice", "age" => 28, "created_utc" => new \DateTime("1517788800", new \DateTimeZone("UTC"))),
				array("id" => 2, "name" => "Bob", "age" => 32, "created_utc" => new \DateTime("1517875200", new \DateTimeZone("UTC"))),
				array("id" => 5, "name" => "Jeremy", "age" => 54, "created_utc" => new \DateTime("1517961600", new \DateTimeZone("UTC"))),
			),
			"items" => array(
				array("id" => 1, "size" => "medium", "name" => "thing1", "itemId" => "abcdef", "created_utc" => new \DateTime("1518048000", new \DateTimeZone("UTC"))),
				array("id" => 2, "size" => "large", "name" => "thing2", "itemId" => "zx9871b", "created_utc" => new \DateTime("1518134400", new \DateTimeZone("UTC"))),
				array("id" => 4, "size" => "small", "name" => "thing3", "itemId" => "sdfsk8723", "created_utc" => new \DateTime("1518220800", new \DateTimeZone("UTC"))),
			),
			"categories" => array(
				array("id" => 14, "name" => "Jackets", "category" => "clothing", "created_utc" => new \DateTime("1518307200", new \DateTimeZone("UTC"))),
				array("id" => 15, "name" => "Baked Beans", "category" => "food", "created_utc" => new \DateTime("1518393600", new \DateTimeZone("UTC"))),
			),
		);
	}

	protected function getRows($sortCol, $sortDir, $offset, $maxResults, $criteria = array()) {

	}

	protected function countRows($criteria) {

	}

	protected function deleteById($id) {

	}

	protected function fetchRow($criteria) {

	}

	protected function getDateCreatedById($id) {
	}

	protected function doSave($object, $forceInsert = false) {

	}

	protected function doInsertMultiple($objects) {

	}
}

class ItemMapper extends ArrayDBMapper {
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
	public function testFirstTest() {
		echo "fish\n";
	}
}