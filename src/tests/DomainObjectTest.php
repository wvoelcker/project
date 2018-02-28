<?php
use PHPUnit\Framework\TestCase;
use WillV\Project\Dataset;
use WillV\Project\DomainObject;

class ExampleDataset extends Dataset {
	protected function setUp() {
		$this->fields = array(
			"id" => array(
				"customValidation" => "ctype_digit"
			),
			"size" => array(
				"allowedValues" => array("small", "medium", "large"),
				"required" => true
			)
		);
	}
}

class ExampleDatasetWithNoIdField extends Dataset {
	protected function setUp() {
		$this->fields = array(
			"size" => array(
				"allowedValues" => array("small", "medium", "large")
			)
		);
	}
}

class ExampleDomainObject extends DomainObject {
	protected function setUp() {
		$this->dataSetName = "ExampleDataset";
	}
}

class ExampleDomainObjectWithNoIdField extends DomainObject {
	protected function setUp() {
		$this->dataSetName = "ExampleDatasetWithNoIdField";
	}
}

// TODO:WV:20180228:This test should really mock the Dataset class rather than use it
class TestDomainObject extends TestCase {

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No ID field in supplied dataset
     */
	public function testItShouldThrowAnExceptionIfTheSuppliedDatasetDoesNotContainAnIdField() {
		$object = ExampleDomainObjectWithNoIdField::create(array(
			"size" => "medium"
		));
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Problems with supplied data: {"size":"This field should have one of the following values: {small, medium, large}"}
     */
	public function testItShouldThrowAnExceptionIfTheSuppliedDataWasNotValid() {
		$object = ExampleDomainObject::create(array(
			"size" => "x-small"
		));
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Invalid field name 'customerName'
     */
	public function testItShouldThrowAnExceptionIfAttemptingToGetAnInvalidField() {
		$object = ExampleDomainObject::create(array(
			"size" => "medium"
		));
		$object->get("customerName");
	}

	public function testItShouldReturnNullIfAttemptingToGetAFieldThatWasNotSet() {
		$object = ExampleDomainObject::create(array(
			"size" => "medium"
		));
		$this->assertNull($object->get("id"));
	}

	public function testItShouldReturnNullIfGettingAFieldThatWasSetToNull() {
		$object = ExampleDomainObject::create(array(
			"id" => null,
			"size" => "medium"
		));
		$this->assertNull($object->get("id"));
	}

	public function testItShouldReturnTheValueOfAFieldWhenGettingThatField() {
		$object = ExampleDomainObject::create(array(
			"id" => "123",
			"size" => "medium"
		));
		$this->assertNotNull($object->get("id"));
		$this->assertEquals("123", $object->get("id"));
	}

	public function testItShouldIncludeAllPublicFieldsWhenRunningGetForPublic() {

	}

	public function testItShouldIncludePublicFieldsWhichAreNotSetWhenRunningGetForPublicAndTheValueShouldBeNull() {

	}

	public function testItShouldOmitAllPrivateFieldsWhenRunningGetForPublic() {
		
	}

	public function testItShouldConfirmThatAValidFieldIsValid() {

	}

	public function testItShouldConfirmThatAnInvalidFieldIsInvalid() {

	}

	public function testItShouldThrowAnExceptionWhenAttemptingToSetAnInvalidField() {

	}

	public function testItShouldSetAValidField() {

	}

	public function testItShouldSetAnyValidFieldsInAnArrayWhenPassedToSetAnyIn() {

	}

	public function testItShouldIgnoreInvalidFieldsInAnArrayWhenPassedToSetAnyIn() {

	}
}