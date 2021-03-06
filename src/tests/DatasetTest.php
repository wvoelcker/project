<?php
namespace WillV\Project\Tests\DatasetTest;
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
			"this-field-has-custom-validation" => array(
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
			"this-field-has-custom-validation" => array("customValidation" => "ctype_digit"),
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
			array("this-field-should-be-a-uk-format-date" => "2018/02/27"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd/mm/yyyy"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateWasInUSFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-uk-format-date" => "12/30/2018"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd/mm/yyyy"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateWasInLongFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-uk-format-date" => "30th of December 2018"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd/mm/yyyy"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateUsedDashesInsteadOfSlashes() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-uk-format-date" => "30-12-2018"),
			array(
				"fieldName" => "this-field-should-be-a-uk-format-date",
				"errorMessage" => "Not a date in the format dd/mm/yyyy"
			)
		);
	}

	public function testItShouldAllowValidUKFormatDates() {
		$this->confirmValidationPasses(
			array("this-field-should-be-a-uk-format-date" => "30/12/2018"),
			"this-field-should-be-a-uk-format-date"
		);
	}

	public function testItShouldReportIfASubmittedMySQLFormatDateWasInUKFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-mysql-format-date" => "30/12/2018"),
			array(
				"fieldName" => "this-field-should-be-a-mysql-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-dd"
			)
		);
	}

	public function testItShouldReportIfASubmittedMySQLFormatDateWasInUSFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-mysql-format-date" => "12/30/2018"),
			array(
				"fieldName" => "this-field-should-be-a-mysql-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-dd"
			)
		);
	}

	public function testItShouldReportIfASubmittedMySQLFormatDateWasInLongFormat() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-mysql-format-date" => "30th of December 2018"),
			array(
				"fieldName" => "this-field-should-be-a-mysql-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-dd"
			)
		);
	}

	public function testItShouldReportIfASubmittedUKFormatDateUsedSlashesInsteadOfDashes() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-mysql-format-date" => "2018/12/30"),
			array(
				"fieldName" => "this-field-should-be-a-mysql-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-dd"
			)
		);
	}

	public function testItShouldAllowValidMySQLFormatDates() {
		$this->confirmValidationPasses(
			array("this-field-should-be-a-mysql-format-date" => "2018-12-30"),
			"this-field-should-be-a-mysql-format-date"
		);
	}

	public function testItShouldReportIfASubmittedISO8601DateWasAUKDate() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-iso-8601-format-date" => "2018/12/30"),
			array(
				"fieldName" => "this-field-should-be-a-iso-8601-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-ddThh:mm:ss(+/-hh:mm)"
			)
		);
	}

	public function testItShouldReportIfASubmittedISO8601DateWasALongFormatDate() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-iso-8601-format-date" => "30th December 2018"),
			array(
				"fieldName" => "this-field-should-be-a-iso-8601-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-ddThh:mm:ss(+/-hh:mm)"
			)
		);
	}

	public function testItShouldReportIfASubmittedISO8601DateWasAMySQLDate() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-iso-8601-format-date" => "2018-12-30"),
			array(
				"fieldName" => "this-field-should-be-a-iso-8601-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-ddThh:mm:ss(+/-hh:mm)"
			)
		);
	}

	public function testItShouldReportIfASubmittedISO8601DateWasAMySQLDateTime() {
		$this->confirmValidationFails(
			array("this-field-should-be-a-iso-8601-format-date" => "2018-12-30 15:19:21"),
			array(
				"fieldName" => "this-field-should-be-a-iso-8601-format-date",
				"errorMessage" => "Not a date in the format yyyy-mm-ddThh:mm:ss(+/-hh:mm)"
			)
		);
	}

	public function testItShouldPassASubmittedISO8601DateWithoutTimezone() {
		$this->confirmValidationPasses(
			array("this-field-should-be-a-iso-8601-format-date" => "2018-12-30T15:19:21"),
			"this-field-should-be-a-iso-8601-format-date"
		);
	}

	public function testItShouldPassASubmittedISO8601DateWithTimezone() {
		$this->confirmValidationPasses(
			array("this-field-should-be-a-iso-8601-format-date" => "2018-12-30T15:19:21+00:00"),
			"this-field-should-be-a-iso-8601-format-date"
		);
	}

	public function testItShouldReportAnObviouslyInvalidEmailAddress() {
		$this->confirmValidationFails(
			array("this-field-should-be-an-email-address" => "not an email address"),
			array(
				"fieldName" => "this-field-should-be-an-email-address",
				"errorMessage" => "Not a valid email address"
			)
		);
	}

	public function testItShouldReportALessObviouslyInvalidEmailAddress() {
		$this->confirmValidationFails(
			array("this-field-should-be-an-email-address" => "#@%^%#$@#$@#.com"),
			array(
				"fieldName" => "this-field-should-be-an-email-address",
				"errorMessage" => "Not a valid email address"
			)
		);
	}

	public function testItShouldAllowAnObviouslyValidEmailAddress() {
		$this->confirmValidationPasses(
			array("this-field-should-be-an-email-address" => "test@example.com"),
			"this-field-should-be-an-email-address"
		);
	}

	public function testItShouldPassAnyCustomValidationWhereTheDataIsValid() {
		$this->confirmValidationPasses(
			array("this-field-has-custom-validation" => "9827"),
			"this-field-has-custom-validation"
		);
	}

	public function testItShouldReportAnyCustomValidationWhereTheDataIsNotValid() {
		$this->confirmValidationFails(
			array("this-field-has-custom-validation" => "not-digits"),
			array(
				"fieldName" => "this-field-has-custom-validation",
				"errorMessage" => "This field is invalid"
			)
		);
	}

}
