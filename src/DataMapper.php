<?php
namespace WillV\Project;

abstract class DataMapper {
	use Trait_AbstractTemplate;
	protected $db, $primaryDomainObject, $primaryDatabaseTable;

	protected function preSetUp() {
		$db = func_get_arg(0);
		$this->db = $db;
	}

	public function createSingle($criterion) {
		$row = $this->fetchRow($criterion);

		if (empty($row)) {
			return null;
		}

		$object = $this->createFromRow($row);

		return $object;
	}


	private function fetchRow($criterion) {
		list($fieldName, $fieldValue) = each($criterion);

		$placeHolder = $this->sanitiseForPlaceholder($fieldName);
		$query = "SELECT * FROM `".$this->primaryDatabaseTable."` WHERE `".$fieldName."` = :".$placeHolder." LIMIT 1";

		$statement = $this->prepareAndExecute($query, array($placeholder => $fieldValue));
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		return $row;
	}

	private function createFromRow($row) {
		$objectData = $this->mapFieldsFromDatabase($row);

		$objectClass = $this->primaryDomainObject;
		$object = $objectClass::create($objectData);

		return $object;
	}

	public function save($object) {

		// Generate column names, values, and placeholders for SQL query
		$queryData = array();
		$fieldsForSQL = array();
		foreach ($this->mapFieldsToDatabase($object) as $fieldName => $fieldValue) {
			$placeHolder = $this->sanitiseForPlaceholder($fieldValue);
			$fieldsForSQL["`".$fieldName."`"] = ":".$placeHolder;
			$queryData[$placeHolder] = $fieldValue;
		}

		$id = $object->get("id");

		// Add modified and created dates
		$now = gmdate("Y-m-d H:i:s");
		$fieldsForSQL["updated_utc"] = $now;
		if (empty($id)) {
			$fieldsForSQL["created_utc"] = $now;
		}

		// Generate the rest of the SQL query
		if (empty($id)) {
			$query = "INSERT INTO `".$this->primaryDatabaseTable."` SET ".join(", ", $fieldsForSQL);
		} else {
			$query = "UPDATE `".$this->primaryDatabaseTable."` SET ".join(", ", $fieldsForSQL)." WHERE id = :id";
			$queryData["id"] = $object->get("id");
		}

		// Run query
		$this->prepareAndExecute($query, $queryData);

		if (empty($id)) {
			$object->set("id", $this->db->lastInsertId());
		}

		return $object;
	}

	private function prepareAndExecute($query, $data) {
		$statement = $this->db->prepare($query);
		$statement->execute($data);

		return $statement;
	}

	abstract protected function mapFieldsFromDatabase($row);
	abstract protected function mapFieldsToDatabase($object);

	protected function sanitiseForPlaceholder($name) {
		$sanitisedName = strtolower($name);
		$sanitisedName = preg_replace("/[^a-z]/", "", $name);
		return $sanitisedName;
	}

}
