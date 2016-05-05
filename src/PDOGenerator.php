<?php
namespace WillV\Project;

class PDOGenerator {
	protected $pdo, $hostname = null, $databasename = null, $username = null, $password = null;
	protected $logger;

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


	// The following function expects a PEAR logger
	public function setLogger(\Log $logger) {
		$this->logger = $logger;
	}

	public function getPDO() {
		if (($this->hostname === null) or ($this->databasename === null) or ($this->username === null) or ($this->password === null)) {
			throw new \Exception("Can't generate a PDO without a hostname, databasename, username, and password");
		}

		$connectionString = "mysql:host=".$this->hostname.";dbname=".$this->databasename.";charset=utf8";

		if (!empty($this->logger)) {
			return new \LoggedPDO\PDO(
				$connectionString,
				$this->username,
				$this->password,
				array(
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
				),
				$logger
			);
		}

		return new \pdo(
			$connectionString,
			$this->username,
			$this->password,
			array(
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
			)
		);
	}
}