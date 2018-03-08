<?php
namespace WillV\Project\DataMapper;

abstract class MySQLMapper extends DataMapper {
	protected $db;

	protected function preSetUp() {
		parent::preSetUp();
		$db = func_get_arg(0);
		$this->db = $db;
	}

	protected final function getRows($sortCol, $sortDir, $offset, $maxResults, $criteria = array()) {
		$whereClauseData = $this->generateWhereClauseData($criteria);
		$query = "SELECT * FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"])." ORDER BY `".$sortCol."` ".$sortDir." LIMIT ".$offset.", ".$maxResults;

		$statement = $this->prepareAndExecute($query, $whereClauseData["queryData"]);
		$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
		return $rows;
	}

	protected final function countRows($criteria) {
		$whereClauseData = $this->generateWhereClauseData($criteria);

		$query = "SELECT COUNT(*) as num FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"]);
		$statement = $this->prepareAndExecute($query, $whereClauseData["queryData"]);
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		return $row["num"];
	}

	protected final function deleteById($id) {
		$whereClauseData = $this->generateWhereClauseData(array("id" => $id));
		$query = "DELETE FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"]);
		$this->prepareAndExecute($query, $whereClauseData["queryData"]);
	}

	protected final function fetchRow($criteria) {
		$whereClauseData = $this->generateWhereClauseData($criteria);

		$query = "SELECT * FROM `".$this->primaryDatabaseTable."` ".$this->generateWhereClause($whereClauseData["whereCriteria"])." LIMIT 1";
		$statement = $this->prepareAndExecute($query, $whereClauseData["queryData"]);
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		return $row;
	}

	private final function generateWhereClause($whereCriteria) {
		if (empty($whereCriteria)) {
			return "";
		}
		return "WHERE (".join(") AND (", $whereCriteria).")";
	}

	private final function generateWhereClauseData($criteria) {
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
					case "in":
						if (!is_array($fieldValue["value"])) {
							throw new \Exception("'in' operator expects an array of values");
						}
						$whereCriteria[] = "`".$fieldName."` IN (".join(", ", array_fill(
							0,
							count($fieldValue["value"]),
							"?"
						)).")";
						$queryData = array_merge($queryData, $fieldValue["value"]);
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

	protected final function getDateCreatedById($id) {
		$dates = $this->getDatesById($id);
		if (empty($dates)) {
			return null;
		}

		return $dates["created_utc"];
	}

	protected final function getDateUpdatedById($id) {
		$dates = $this->getDatesById($id);
		if (empty($dates)) {
			return null;
		}

		return $dates["updated_utc"];
	}

	private function getDatesById($id) {
		$query = "SELECT created_utc, updated_utc FROM `".$this->primaryDatabaseTable."` WHERE id = :id LIMIT 1";

		$statement = $this->prepareAndExecute($query, array("id" => $id));
		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		if (empty($row)) {
			return null;
		}

		return array(
			"created_utc" => new \DateTime("@".strtotime($row["created_utc"]), new \DateTimeZone("UTC")),
			"updated_utc" => new \DateTime("@".strtotime($row["updated_utc"]), new \DateTimeZone("UTC")),
		);
	}

	protected final function doSave($object) {

		// Generate column names and values
		$queryData = array();
		$fieldsForSQL = array();
		foreach ($this->mapFieldsToDatabase($object) as $fieldName => $fieldValue) {
			$fieldsForSQL[] = $fieldName;
			$queryData[] = $fieldValue;
		}

		// Add modified and created dates
		$now = gmdate("Y-m-d H:i:s");
		$creationDateIndex = count($fieldsForSQL);
		$fieldsForSQL[] = "created_utc";
		$queryData[] = $now;
		$fieldsForSQL[] = "updated_utc";
		$queryData[] = $now;
		$id = $object->get("id");

		// Generate field names and values
		$assignmentList = $this->buildAssignmentList($fieldsForSQL);

		// Generate the rest of the SQL query
		$query = "INSERT INTO `".$this->primaryDatabaseTable."` SET ".$assignmentList;
		$query .= " ON DUPLICATE KEY UPDATE ".$this->buildAssignmentList($this->removeFromArray($fieldsForSQL, $creationDateIndex));
		$queryData = array_merge($queryData, $this->removeFromArray($queryData, $creationDateIndex));

		// Run query
		$this->prepareAndExecute($query, $queryData);

		if (empty($id)) {
			$object->set("id", $this->db->lastInsertId());
		}

		return $object;
	}

	private function removeFromArray($array, $offset) {
		unset($array[$offset]);
		return array_values($array);
	}

	private function buildAssignmentList($allFields) {
		$assignmentList = "";
		foreach ($allFields as $fieldName) {
			$assignmentList .= ", `".$fieldName."`= ? ";
		}
		$assignmentList = substr($assignmentList, 2);

		return $assignmentList;
	}

	protected final function doInsertMultiple($objects) {
		$query = "INSERT INTO `".$this->primaryDatabaseTable."` ";

		// Generate data for forming mysql query
		$queryData = array();
		$isFirst = true;
		foreach ($objects as $object) {

			$fieldNames = array();
			$numFields = 0;

			foreach ($this->mapFieldsToDatabase($object) as $fieldName => $fieldValue) {

				if ($isFirst) {
					$fieldNames[] = $fieldName;
				}

				$queryData[] = $fieldValue;
				$numFields++;
			}

			// Add created- and modified- dates
			if ($isFirst) {
				$fieldNames[] = "updated_utc";
				$fieldNames[] = "created_utc";
			}
			$now = gmdate("Y-m-d H:i:s");
			$queryData[] = $now;
			$queryData[] = $now;
			$numFields += 2;

			if ($isFirst) {
				$query .= "(`".join("`, `", $fieldNames)."`) VALUES ";
			}

			$query .= (($isFirst)?"":", ")."(".substr(str_repeat(", ? ", $numFields), 1).")";

			if ($isFirst) {
				$isFirst = false;
			}
		}

		return $this->prepareAndExecute($query, $queryData);
	}

	private final function prepareAndExecute($query, $data) {
		$statement = $this->db->prepare($query);
		$statement->execute($data);

		return $statement;
	}
}
