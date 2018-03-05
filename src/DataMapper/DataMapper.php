<?php
namespace WillV\Project\DataMapper;
use WillV\Project\Trait_AbstractTemplate;

abstract class DataMapper {
	use Trait_AbstractTemplate;
	protected $primaryDomainObject, $primaryDatabaseTable;
	protected $columnMappings = array();

	// This function should be overridden in the application
	abstract protected function getColumnMappings();

	protected function preSetUp() {
		foreach ($this->getColumnMappings() as $key => $value) {
			$this->columnMappings[$key] = ($value === true?$key:$value);
		}
	}

	public function findById($id) {
		return $this->createSingle(array("id" => $id));
	}

	public function findSingleFromCriteria($applicationCriteria) {
		$databaseCriteria = $this->mapCriteria($applicationCriteria);
		return $this->createSingle($databaseCriteria);
	}

	public function generatePage($applicationSortCol, $sortDir, $offset, $maxResults, $applicationCriteria = array()) {

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

		if (!isset($this->columnMappings[$applicationSortCol]) or !is_string($this->columnMappings[$applicationSortCol])) {
			throw new \Exception("Can only sort by application properties that directly map to database columns");
		}
		$databaseSortCol = $this->columnMappings[$applicationSortCol];
		$databaseCriteria = $this->mapCriteria($applicationCriteria);

		$rows = $this->getRows($databaseSortCol, $sortDir, $offset, $maxResults, $databaseCriteria);
		$output = array();
		foreach ($rows as $row) {
			$output[] = $this->createFromRow($row);
		}

		return $output;
	}

	private function mapCriteria($applicationCriteria) {
		$databaseCriteria = array();

		foreach ($applicationCriteria as $key => $value) {
			if (!isset($this->columnMappings[$key])) {
				throw new \Exception("No mapping for property '".$key."'");
			}

			if (is_string($this->columnMappings[$key])) {
				$databaseCriteria[$this->columnMappings[$key]] = $value;
				continue;
			}

			throw new \Exception("Can only use criteria that directly map application properties to database columns");
		}

		return $databaseCriteria;
	}

	private function createSingle($criteria) {
		$row = $this->fetchRow($criteria);

		if (empty($row)) {
			return null;
		}

		$object = $this->createFromRow($row);

		return $object;
	}

	public function delete($obj) {
		$id = $obj->get("id");
		if (empty($id)) {
			throw new \Exception("Cannot delete objects with no ID");
		}
		return $this->deleteById($id);
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

		$date = $this->getDateCreatedById($id);
		return $date;
	}

	public function save($object) {
		return $this->doSave($object);
	}

	public function insert($objects) {
		if (is_array($objects)) {
			return $this->doInsertMultiple($objects);
		} else {
			return $this->doSave($objects, true);
		}
	}

	protected function mapFieldsToDatabase($object) {
		return $this->mapFields($object);
	}

	protected function mapFieldsFromDatabase($row) {
		return $this->mapFields($row, false);
	}

	protected function mapFields($item, $isToDatabase = true) {
		$output = array();
		$mapFunc = ($isToDatabase?0:1);
		foreach ($this->columnMappings as $applicationField => $databaseField) {
			if (is_string($databaseField)) {
				$outputKey = ($isToDatabase?$databaseField:$applicationField);
				$outputValue = ($isToDatabase?$item->get($applicationField):$item[$databaseField]);
				$output[$outputKey] = $outputValue;

			} elseif (is_array($databaseField) and isset($databaseField[$mapFunc]) and is_callable($databaseField[$mapFunc])) {
				$mappedData = call_user_func_array($databaseField[$mapFunc], array($item));
				if (!is_array($mappedData) or !isset($mappedData["key"]) or !isset($mappedData["value"])) {
					throw new \Exception("Invalid mapping function");
				}
				$output[$mappedData["key"]] = $mappedData["value"];

			} else {
				throw new \Exception("Invalid column mapping");
			}
		}

		return $output;
	}

	// These functions should be overridden in intermediary classes for communicating with particular database engines (e.g. MySQL)
	abstract protected function getRows($sortCol, $sortDir, $offset, $maxResults, $criteria = array());
	abstract protected function countRows($criteria);
	abstract protected function deleteById($id);
	abstract protected function fetchRow($criteria);
	abstract protected function getDateCreatedById($id);
	abstract protected function doSave($object, $forceInsert = false);
	abstract protected function doInsertMultiple($objects);
}
