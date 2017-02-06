<?php
namespace WillV\Project;

abstract class DataMapper {
	use Trait_AbstractTemplate;
	protected $db, $primaryDomainObject, $primaryDatabaseTable;

	protected function preSetUp() {
		$db = func_get_arg(0);
		$this->db = $db;
	}

	public function createSingle($criteria) {
		$row = $this->fetchRow($criteria);

		if (empty($row)) {
			return null;
		}

		$object = $this->createFromRow($row);

		return $object;
	}


	private function fetchRow($criteria) {
		$whereClauseData = $this->generateWhereClauseData($criteria);

		$query = "SELECT * FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"])." LIMIT 1";
		$statement = $this->prepareAndExecute($query, $whereClauseData["queryData"]);
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		return $row;
	}

	private function generateWhereClause($whereCriteria) {
		if (empty($whereCriteria)) {
			return array();
		}
		return "WHERE (".join(") AND (", $whereCriteria).")";
	}

	private function generateWhereClauseData($criteria) {
		$whereCriteria = array();
		$queryData = array();
		foreach ($criteria as $fieldName => $fieldValue) {

			if (is_scalar($fieldValue)) {
				$placeholder = $this->sanitiseForPlaceholder($fieldName);
				$whereCriteria[] = "`".$fieldName."` = :".$placeholder;
				$queryData[$placeholder] = $fieldValue;

			} elseif (is_array($fieldValue)) {

				switch ($fieldValue["type"]) {
					case "is null":
						$whereCriteria[] = "`".$fieldName."` IS NULL";
						break;
					case "is not null":
						$whereCriteria[] = "`".$fieldName."` IS NOT NULL";
						break;
					default:
						throw new \Exception("Unknown field value type");
				}

			} else {
				throw new \Exception("Invalid field value");
			}
		}

		return array("whereCriteria" => $whereCriteria, "queryData" => $queryData);
	}

	public function createPage($sortCol, $sortDir, $offset, $maxResults, $criteria = array()) {
		$sortDir = strtoupper($sortDir);
		if (!in_array($sortDir, array("ASC", "DESC"))) {
			throw new \Exception("Invalid sort direction (should be 'asc' or 'desc')");
		}
		if (!ctype_digit((string)$offset)) {
			throw new \Exception("Invalid offset");
		}
		if (!ctype_digit((string)$maxResults)) {
			throw new \Exception("Invalid maximum results");
		}

		$whereClauseData = $this->generateWhereClauseData($criteria);
		$query = "SELECT * FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"])." ORDER BY `".$sortCol."` ".$sortDir." LIMIT ".$offset.", ".$maxResults;

		$statement = $this->prepareAndExecute($query, $whereClauseData["queryData"]);
		$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

		$output = array();
		foreach ($rows as $row) {
			$output[] = $this->createFromRow($row);
		}

		return $output;
	}

	private function createFromRow($row) {
		$objectData = $this->mapFieldsFromDatabase($row);
		$objectClass = $this->primaryDomainObject;
		$object = $objectClass::create($objectData);

		return $object;
	}

	public function getDateCreated($object) {
		$id = $object->get("id");

		if (empty($id)) {
			return null;
		}

		$placeholder = $this->sanitiseForPlaceholder($fieldName);
		$query = "SELECT created_utc FROM `".$this->primaryDatabaseTable."` WHERE id = :id LIMIT 1";

		$statement = $this->prepareAndExecute($query, array("id" => $id));
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		if (empty($row)) {
			return null;
		}

		$date = new \DateTime($row["created_utc"], new \DateTimeZone("UTC"));

		return $date;
	}

	public function save($object) {

		// Generate column names, values, and placeholders for SQL query
		$queryData = array();
		$fieldsForSQL = array();

		foreach ($this->mapFieldsToDatabase($object) as $fieldName => $fieldValue) {
			$placeholder = $this->sanitiseForPlaceholder($fieldValue);
			$fieldsForSQL["`".$fieldName."`"] = ":".$placeholder;
			$queryData[$placeholder] = $fieldValue;
		}

		$id = $object->get("id");

		// Add modified and created dates
		$queryData["NOW"] = gmdate("Y-m-d H:i:s");
		$fieldsForSQL["updated_utc"] = ":NOW";
		if (empty($id)) {
			$fieldsForSQL["created_utc"] = ":NOW";
		}

		// Generate field names and values
		$query = "";
		foreach ($fieldsForSQL as $fieldName => $fieldValue) {
			$query .= ", ".$fieldName."=".$fieldValue;
		}
		$query = substr($query, 2);

		// Generate the rest of the SQL query
		if (empty($id)) {
			$query = "INSERT INTO `".$this->primaryDatabaseTable."` SET ".$query;
		} else {
			$query = "UPDATE `".$this->primaryDatabaseTable."` SET ".$query." WHERE id = :id";
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
		if ($name === null) {
			return "NULL";
		}
		$sanitisedName = strtolower($name);
		$sanitisedName = str_replace(
			array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9"),
			array("zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine"),
			$sanitisedName
		);
		$sanitisedName = preg_replace("/[^a-z]/", "", $sanitisedName);
		return $sanitisedName;
	}

}
