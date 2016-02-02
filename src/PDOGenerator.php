<?php
namespace WillV\Project;

class PDOGenerator {
	protected $pdo, $hostname, $databasename, $username, $password;

	static public function create($hostname, $databasename, $username, $password) {
		$db = new PDOGenerator;
		$db->hostname = $hostname;
		$db->databasename = $databasename;
		$db->username = $username;
		$db->password = $password;
		return $db;
	}

	private function __construct() {
	}

	public function getPDO() {
		if (empty($this->pdo)) {
			if (empty($this->activeEnvironment)) {
				throw new Exception("Can't generate a PDO without an active environment having been supplied");
			}

			$this->pdo = new \pdo(
				"mysql:host=".$this->hostname.";dbname=".$this->databasename.";charset=utf8",
				$this->username,
				$this->password,
				array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
			);
		}
		return $this->pdo;
	}
}