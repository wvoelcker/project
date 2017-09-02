<?php
namespace WillV\Project;

abstract class DataMapper {
	use Trait_AbstractTemplate;
	protected $db, $primaryDomainObject, $primaryDatabaseTable;

	protected function preSetUp() {
		$db = func_get_arg(0);
		$this->db = $db;
	}

	public function findById($id) {
		return $this->createSingle(array("id" => $id));
	}

	public function createSingle($criteria) {
		$row = $this->fetchRow($criteria);

		if (empty($row)) {
			return null;
		}

		$object = $this->createFromRow($row);

		return $object;
	}

	public function count($criteria = array()) {
		$whereClauseData = $this->generateWhereClauseData($criteria);

		$query = "SELECT COUNT(*) as num FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"]);
		$statement = $this->prepareAndExecute($query, $whereClauseData["queryData"]);
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		return $row["num"];
	}

	public function delete($obj) {
		$id = $obj->get("id");
		if (empty($id)) {
			throw new \Exception("Cannot delete objects with no ID");
		}
		$whereClauseData = $this->generateWhereClauseData(array("id" => $id));
		$query = "DELETE FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"]);
		$this->prepareAndExecute($query, $whereClauseData["queryData"]);
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
			return "";
		}
		return "WHERE (".join(") AND (", $whereCriteria).")";
	}

	private function generateWhereClauseData($criteria) {
		$whereCriteria = array();
		$queryData = array();

		foreach ($criteria as $fieldName => $fieldValue) {

			if (is_scalar($fieldValue)) {
				$whereCriteria[] = "`".$fieldName."` = ?";
				$queryData[] = $fieldValue;

			} elseif (is_array($fieldValue)) {

				switch ($fieldValue["type"]) {
					case "is null":
						$whereCriteria[] = "`".$fieldName."` IS NULL";
						break;
					case "is not null":
						$whereCriteria[] = "`".$fieldName."` IS NOT NULL";
						break;
					case "less than":
						$whereCriteria[] = "`".$fieldName."` < ?";
						$queryData[] = $fieldValue["value"];
						break;
					case "greater than":
						$whereCriteria[] = "`".$fieldName."` > ?";
						$queryData[] = $fieldValue["value"];
						break;
					default:
						throw new \Exception("Unknown field value type");
				}

			} else {
				throw new \Exception("Invalid field value ".$fieldName." ".$fieldValue);
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

		$query = "SELECT created_utc FROM `".$this->primaryDatabaseTable."` WHERE id = :id LIMIT 1";

		$statement = $this->prepareAndExecute($query, array("id" => $id));
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		if (empty($row)) {
			return null;
		}

		$date = new \DateTime($row["created_utc"], new \DateTimeZone("UTC"));

		return $date;
	}

	private function doSave($object, $forceInsert = false) {

		// Generate column names and values
		$queryData = array();
		$fieldsForSQL = array();
		foreach ($this->mapFieldsToDatabase($object) as $fieldName => $fieldValue) {
			$fieldsForSQL[] = $fieldName;
			$queryData[] = $fieldValue;
		}

		// Add modified and created dates
		$now = gmdate("Y-m-d H:i:s");
		$queryData[] = $now;
		$fieldsForSQL[] = "updated_utc";
		$id = $object->get("id");
		$isInsert = (empty($id) or $forceInsert);
		if ($isInsert) {
			$fieldsForSQL[] = "created_utc";
			$queryData[] = $now;
		}

		// Generate field names and values
		$query = "";
		foreach ($fieldsForSQL as $fieldName) {
			$query .= ", `".$fieldName."`= ? ";
		}
		$query = substr($query, 2);

		// Generate the rest of the SQL query
		if ($isInsert) {
			$query = "INSERT INTO `".$this->primaryDatabaseTable."` SET ".$query;
		} else {
			$query = "UPDATE `".$this->primaryDatabaseTable."` SET ".$query." WHERE id = ?";
			$queryData[] = $object->get("id");
		}

		// Run query
		$this->prepareAndExecute($query, $queryData);

		if (empty($id)) {
			$object->set("id", $this->db->lastInsertId());
		}

		return $object;
	}

	public function save($object) {
		return $this->doSave($object);
	}

	public function insert($objects) {
		if (is_array($objects)) {

			$query = "INSERT INTO `".$this->primaryDatabaseTable."` ";

			// Generate data for forming mysql query
			$queryData = array();
			$isFirst = true;
			foreach ($objects as $objectId => $object) {

				$fieldNames = array();
				$numFields = 0;

				foreach ($this->mapFieldsToDatabase($object) as $fieldName => $fieldValue) {

					if ($isFirst) {
						$fieldNames[] = $fieldName;
					}

					$queryData[] = $fieldValue;
					$numFields++;
				}

				if ($isFirst) {
					$query .= "(`".join("`, `", $fieldNames)."`) VALUES ";
				}

				$query .= (($isFirst)?"":", ")."(".substr(str_repeat(", ? ", $numFields), 1).")";

				if ($isFirst) {
					$isFirst = false;
				}
			}

			return $this->prepareAndExecute($query, $queryData);

		} else {
			return $this->doSave($objects, true);
		}
	}

	private function prepareAndExecute($query, $data) {
		$statement = $this->db->prepare($query);
		$statement->execute($data);

		return $statement;
	}

	abstract protected function mapFieldsFromDatabase($row);
	abstract protected function mapFieldsToDatabase($object);
}
