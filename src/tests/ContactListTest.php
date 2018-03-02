<?php
namespace WillV\Project\Tests\ContactListTest;
use PHPUnit\Framework\TestCase;
use WillV\Project\ContactList;

class TestContactList extends ContactList {
	protected function setUp() {

		$this->addContact("testContact1", array(
			"email" => "test1@example.com",
			"name" => "Test Contact 1",
		));

		$this->addContact("testContact2", array(
			"email" => "test2@example.com",
			"name" => "Test Contact 2",
		));

		$this->addContact("testContact3", array(
			"email" => "test3@example.com",
			"name" => "Test Contact 3",
		));
	}
}

class TestBrokenContactList extends ContactList {
	protected function setUp() {

		$this->addContact("testContact1", array(
			"email" => "test1@example.com",
			"name" => "Test Contact 1",
		));

		$this->addContact("testContact1", array(
			"email" => "test1@example.com",
			"name" => "Test Contact 1",
		));
	}
}

class ContactListTest extends TestCase {

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Contact 'testContact1' already added
     */
	public function testItShouldThrowAnExceptionIfTheSameContactAddedTwice() {
		TestBrokenContactList::create();
	}

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Contact 'nonExistentContact' not found
     */
	public function testItShouldThrowAnExceptionIfTheContactIsNotFound() {
		TestContactList::create()->getContact("nonExistentContact");
	}

	public function testItShouldFindAndReturnDataForTheFirstContactInTheList() {
		$contact = TestContactList::create()->getContact("testContact1");
		$this->assertEquals($contact, array(
			"email" => "test1@example.com",
			"name" => "Test Contact 1",
		));
	}

	public function testItShouldFindAndReturnDataForTheSecondContactInTheList() {
		$contact = TestContactList::create()->getContact("testContact2");
		$this->assertEquals($contact, array(
			"email" => "test2@example.com",
			"name" => "Test Contact 2",
		));
	}

	public function testItShouldFindAndReturnDataForTheThirdContactInTheList() {
		$contact = TestContactList::create()->getContact("testContact3");
		$this->assertEquals($contact, array(
			"email" => "test3@example.com",
			"name" => "Test Contact 3",
		));
	}
}
