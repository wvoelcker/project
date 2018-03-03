<?php
namespace WillV\Project;

class PDOGenerator {
	protected $pdo, $hostname = null, $databasename = null, $username = null, $password = null;
	protected $logger;

	static public function create($hostname, $username, $password, $databasename = null) {
		$db = new PDOGenerator;
		$db->hostname = $hostname;
		$db->username = $username;
		$db->password = $password;
		$db->databasename = $databasename;
		return $db;
	}

	private function __construct() {
	}

	public function setLogMode($logMode) {
		$logNameSpace = "willv-project-pdo-generator";
		if ($logMode === true) {
			$this->logger = \Log::factory("console", "", $logNameSpace);
		} else {
			$this->logger = \Log::factory("file", $logMode, $logNameSpace);
		}

		return $this;
	}

	public function getPDO() {
		if (($this->hostname === null) or ($this->username === null) or ($this->password === null)) {
			throw new \Exception("Can't generate a PDO without a hostname, username, and password");
		}

		$connectionString = "mysql:host=".$this->hostname.(empty($this->databasename)?"":(";dbname=".$this->databasename)).";charset=utf8";

		if (!empty($this->logger)) {
			return new \LoggedPDO\PDO(
				$connectionString,
				$this->username,
				$this->password,
				array(
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
				),
				$this->logger
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