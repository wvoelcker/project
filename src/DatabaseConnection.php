<?php
namespace WillV\Project;

class DatabaseConnection {
	protected $pdo, $activeEnvironment;

	static public function create($activeEnvironment) {
		$db = new DatabaseConnection;
		$db->setActiveEnvironment($activeEnvironment);
		return $db;
	}

	public function getPDO() {
		if (empty($this->pdo)) {
			$this->pdo = new \pdo(
				"mysql:host=".$this->activeEnvironment->get("database-host").";dbname=".$this->activeEnvironment->get("database-name").";charset=utf8",
				$this->activeEnvironment->get("database-username"),
				$this->activeEnvironment->get("database-password"),
				array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
			);
		}
		return $this->pdo;
	}

	public function setActiveEnvironment($activeEnvironment) {
		$this->activeEnvironment = $activeEnvironment;
		return $this;
	}
}