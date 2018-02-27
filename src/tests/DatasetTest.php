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
			"this-field-has-a-set-of-allowed-values" => array("allowedValues" => array("alpha", "beta", "gamma")),
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

	private function confirmValidationFails($data, $expectedError) {
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid($data, $errors);
		$this->assertFalse($isValid);
		$this->assertArrayHasKey($expectedError["fieldName"], $errors);
		$this->assertEquals($expectedError["errorMessage"], $errors[$expectedError["fieldName"]]);
	}

	private function confirmValidationPasses($data, $fieldName) {
		$dataSet = ExampleDataset::create();
		$isValid = $dataSet->isValid($data, $errors);
		$this->assertArrayNotHasKey($fieldName, $errors);		
	}

	public function testItShouldReportIfANotEmptyFieldIsSetButEmpty() {
		$this->confirmValidationFails(
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
		$this->confirmValidationFails(
			array(),
			array(
				"fieldName" => "this-field-cannot-be-empty",
				"errorMessage" => "This field should not be empty"
			)
		);
	}

	public function testItShouldReportIfARequiredFieldIsMissing() {
		$this->confirmValidationFails(
			array(),
			array(
				"fieldName" => "this-field-must-be-supplied",
				"errorMessage" => "This field is required"
			)
		);
	}

	public function testItShouldNotReportIfAnOptionalFieldIsMissing() {
		$this->confirmValidationPasses(array(), "this-field-is-optional");
	}

	public function testItShouldNotAttemptToValidateFieldsThatAreNotRequiredAndWereNotProvided() {
		$this->confirmValidationPasses(array(), "this-field-has-a-set-of-allowed-values");
	}

	public function testItShouldReportIfTheFieldHasASetOfAllowedValuesAndTheSuppliedValueWasNotOneOfThem() {
		$this->confirmValidationFails(
			array("this-field-has-a-set-of-allowed-values" => "delta"),
			array(
				"fieldName" => "this-field-has-a-set-of-allowed-values",
				"errorMessage" => "This field should have one of the following values: {alpha, beta, gamma}"
			)
		);
	}

	public function testItShouldNotReportIfTheFieldHasASetOfAllowedValuesAndTheSuppliedValueWasOneOfThem() {
		$this->confirmValidationPasses(
			array("this-field-has-a-set-of-allowed-values" => "beta"),
			"this-field-has-a-set-of-allowed-values"
		);
	}

	public function testItShouldAllowAValid12HourTimeInTheMorning() {
		$this->confirmValidationPasses(
			array("this-field-should-be-a-12-hour-time" => "12:14am"),
			"this-field-should-be-a-12-hour-time"
		);
	}

	public function testItShouldAllowAValid12HourTimeInTheAfternoon() {
		$this->confirmValidationPasses(
			array("this-field-should-be-a-12-hour-time" => "12:14pm"),
			"this-field-should-be-a-12-hour-time"
		);
	}

	public function testItShouldReportIfA24HourTimeIsSubmittedAsA12HourTime() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-12-hour-time" => "22:12"),
			array(
				"fieldName" => "this-field-should-be-a-12-hour-time",
				"errorMessage" => "Expected a time in the format h:m(am/pm)"
			)
		);
	}

	public function testItShouldReportIfSubmitted12HourTimeHasAnInappropriateSuffix() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-12-hour-time" => "10:42bc"),
			array(
				"fieldName" => "this-field-should-be-a-12-hour-time",
				"errorMessage" => "Expected a time in the format h:m(am/pm)"
			)
		);
	}

	public function testItShouldReportIfSubmitted12HourTimeIsTotallyInvalid() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-12-hour-time" => "10:42 in the morning"),
			array(
				"fieldName" => "this-field-should-be-a-12-hour-time",
				"errorMessage" => "Expected a time in the format h:m(am/pm)"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateWasInMySQLFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-uk-format-date" => "2018-02-27"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd-mm-yyyy"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateWasInUSFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-uk-format-date" => "12-30-2018"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd-mm-yyyy"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateWasInLongFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-uk-format-date" => "30th of December 2018"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd-mm-yyyy"
			)
		);
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
