<?php
use PHPUnit\Framework\TestCase;
use WillV\Project\Dataset;

class ExampleDataset extends Dataset {

	protected function setUp() {
		$this->fields = array(
			"this-field-cannot-be-empty" => array(
				"notempty" => true
			),
			"this-field-must-be-supplied" => array(
				"required" => true
			),
			"this-field-is-optional" => array(
				"required" => false
			),
			"this-field-has-a-set-of-allowed-values" => array(
				"allowedValues" => array("alpha", "beta", "gamma")
			),
			"this-field-should-be-a-12-hour-time" => array(
				"validate12HrTime" => true
			),
			"this-field-should-be-a-uk-format-date" => array(
				"validateDateUK" => true
			),
			"this-field-should-be-a-mysql-format-date" => array(
				"validateDateMySQL" => true
			),
			"this-field-should-be-a-iso-8601-format-date" => array(
				"validateDateISO8601" => true
			),
			"this-field-should-be-an-email-address" => array(
				"validateEmailAddress" => true
			),
			"this-field-has-custom-validation-to-check-if-it-is-a-digit" => array(
				"customValidation" => "ctype_digit"
			),
		);
	}
}

class ExampleDatasetWithNoFields extends Dataset {
	protected function setUp() {}
}

class TestDataset extends TestCase {

	public function testItShouldReturnAllFieldsAndNoExtraFieldsWhenGetFieldsIsCalled() {
		$dataSet = ExampleDataset::create();
		$fields = $dataSet->getFields();
		$this->assertEquals(array(
			"this-field-cannot-be-empty" => array("notempty" => true),
			"this-field-must-be-supplied" => array("required" => true),
			"this-field-is-optional" => array("required" => false),
			"this-field-has-a-set-of-allowed-values" => array("allowedValues" => array("aalpha", "beta", "gamma")),
			"this-field-should-be-a-12-hour-time" => array("validate12HrTime" => true),
			"this-field-should-be-a-uk-format-date" => array("validateDateUK" => true),
			"this-field-should-be-a-mysql-format-date" => array("validateDateMySQL" => true),
			"this-field-should-be-a-iso-8601-format-date" => array("validateDateISO8601" => true),
			"this-field-should-be-an-email-address" => array("validateEmailAddress" => true),
			"this-field-has-custom-validation-to-check-if-it-is-a-digit" => array("customValidation" => "ctype_digit"),
		), $fields);
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage No valid definitions have been supplied
     */
	public function testItShouldThrowAnExceptionIfIsValidIsCalledWithoutAnyFieldsBeingDefined() {
		$dataSet = ExampleDatasetWithNoFields::create();
		$dataSet->isValid(array(), $errors);
	}

	public function testItShouldSetErrorsToAnArrayEvenIfThereWerentAny() {
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid(array(
			"this-field-cannot-be-empty" => true,
			"this-field-must-be-supplied" => true
		), $errors);
		$this->assertTrue($isValid);
		$this->assertTrue(is_array($errors));
	}

	private function validateDataset($data, $expectedError) {
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid($data, $errors);
		$this->assertFalse($isValid);
		$this->assertArrayHasKey($expectedError["fieldName"], $errors);
		$this->assertEquals($expectedError["errorMessage"], $errors[$expectedError["fieldName"]]);
	}

	public function testItShouldReportIfANotEmptyFieldIsSetButEmpty() {
		$this->validateDataset(
			array(
				"this-field-cannot-be-empty" => null
			),
			array(
				"fieldName" => "this-field-cannot-be-empty",
				"errorMessage" => "This field should not be empty"
			)
		);
	}

	public function testItShouldReportIfANotEmptyFieldIsNotSet() {
		$this->validateDataset(
			array(),
			array(
				"fieldName" => "this-field-cannot-be-empty",
				"errorMessage" => "This field should not be empty"
			)
		);
	}

	public function testItShouldReportIfARequiredFieldIsMissing() {
		$this->validateDataset(
			array(),
			array(
				"fieldName" => "this-field-must-be-supplied",
				"errorMessage" => "This field is required"
			)
		);
	}

	public function testItShouldNotReportIfAnOptionalFieldIsMissing() {
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid(array(), $errors);
		$this->assertArrayNotHasKey("this-field-is-optional", $errors);
	}

	public function testItShouldNotAttemptToValidateFieldsThatAreNotRequiredAndWereNotProvided() {

		// Other fields (e.g. dates) would error if it attempted to validate them despite being not provided
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid(array(
			"this-field-cannot-be-empty" => true,
			"this-field-must-be-supplied" => true
		), $errors);
		$this->assertTrue($isValid);
	}

	public function testItShouldReportIfTheFieldHasASetOfAllowedValuesAndTheSuppliedValueWasNotOneOfThem() {
		$this->validateDataset(
			array("this-field-has-a-set-of-allowed-values" => "delta"),
			array(
				"fieldName" => "this-field-has-a-set-of-allowed-values",
				"errorMessage" => "This field should have one of the following values: {alpha, beta, gamma}"
			)
		);
	}

	public function testItShouldNotReportIfTheFieldHasASetOfAllowedValuesAndTheSuppliedValueWasOneOfThem() {
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid(array("this-field-has-a-set-of-allowed-values" => "beta"), $errors);
		$this->assertArrayNotHasKey("this-field-has-a-set-of-allowed-values", $errors);
	}

	public function testItShouldNotReportAnyMessagesAboutAllowedValuesIfTheFieldDoesNotHaveASetOfAllowedValues() {
		
	}

	public function testItShouldValidate12HourTimes() {

	}

	public function testItShouldValidateUKDates() {

	}

	public function testItShouldValidateMySQLDates() {

	}

	public function testItShouldValidateISO8601Dates() {

	}

	public function testItShouldValidateEmailAddresses() {

	}

	public function testItShouldDoAnyCustomValidation() {

	}

}
