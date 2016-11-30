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
		$statement = $this->db->prepare($query);
		$statement->execute(array($placeholder => $fieldValue));

		$row = $statement->fetch(\PDO::FETCH_ASSOC);

		return $row;
	}

	private function createFromRow($row) {
		$objectData = $this->mapFields($row);

		$objectClass = $this->primaryDomainObject;
		$object = $objectClass::create($objectData);

		return $object;
	}

	abstract protected function mapFields($row);

	protected function sanitiseForPlaceholder($name) {
		$sanitisedName = strtolower($name);
		$sanitisedName = preg_replace("/[^a-z]/", "", $name);
		return $sanitisedName;
	}

}
