<?php
class PdoAdapter implements IDatabaseAdapter {
	public $connection;	
	private $host = "";
	private $username = "";
	private $password = "";
	private $dbname = "";
	
	function __construct() {
		$this->connect();	
	}
	
	private function connect() {
		$this->connection = new PDO("mysql:host=$this->host;dbname=$this->dbname",$this->username,$this->password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));	
		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 	
	}

	public function getLastInsertId() {
		return $this->connection->lastInsertId();
	}
	
	private function filterParameters($data) {
		$result = array();
		foreach($data as $key => $val) {
			if(($val !== null) && (!$val instanceof EntityProxy) & (!$val instanceof CollectionProxy) && (gettype($val) !== "object")) {
				//because fields are separated by underscores in a database but used camelcase in php, they are being converted when needed
				$fieldparts = preg_split('/(?=[A-Z])/', $key);		
				$field = "";
				if(sizeof($fieldparts) > 1) {
					foreach($fieldparts as $fieldpart) {
						$field .= strtolower($fieldpart) . "_";
					}
					$newKey = rtrim($field, "_");
				} else {
					$newKey = $key;
				}
				$result[$newKey] = $val;
			}
		}		
		return $result;
	}
	
	//for serialization purposes when using proxy objects
	public function __sleep() {
		return array();
	}
	
	public function __wakeup() {
		$this->connect();
	}
	
	public function insert($table, array $data) {
		$data = $this->filterParameters($data);		
		$cols = implode(", ", array_keys($data));		
		$values = implode(", :", array_keys($data));
		foreach($data as $col => $value) {			
			unset($data[$col]);
			$data[":" . $col] = $value;
		}
		$sql = "insert into " . $table . " (" . $cols . ")  values (:" . $values . ")";	
		return $this->query($sql,$data);
	}

	public function update($table, array $data, $where = "") {		
		$data = $this->filterParameters($data);
		$set = array();
		foreach($data as $col => $value) {
			unset($data[$col]);
			$data[":" . $col] = $value;
			$set[] = $col . " = :" . $col;
		}
		$sql = "UPDATE " . $table . " SET " . implode(", ", $set) . (($where) ? " WHERE " . $where : " ");		
		return $this->query($sql,$data);
	}
	
	public function delete($table, $where = "") {
		$sql = "DELETE FROM " . $table . (($where) ? " WHERE " . $where : " ");
		return $this->query($sql);			
	}
	
	public function query($sql,array $bind = array()) {
		$stmt = $this->connection->prepare($sql);
		if(preg_match("/^(" . implode("|", array("select")) . ") /i", $sql)) {
            $stmt->execute($bind);			
			return $stmt->fetchAll(PDO::FETCH_OBJ);
		} else {
			return $stmt->execute($bind);
		}
	}
	
	private function parseConditions(&$sql, $params, &$bind) {
		if(isset($params["conditions"])) {
			$sql .= " where " . $params["conditions"][0];			
			array_shift($params["conditions"]);
			$bind = $params["conditions"];		
		}
	}
	
	public function findOne($table, array $params = array()) {
		$bind = array();
		$sql = "select * from " . $table;			
		$this->parseConditions($sql, $params, $bind);
		$result = $this->query($sql, $bind);
		return empty($result) ? null : $result[0];
	}
	
	public function findMany($table, array $params = array()) {
		$bind = array();
		$sql = "select * from " . $table;		
		$this->parseConditions($sql, $params, $bind);
		//order		
		if(isset($params["sort"]) && isset($params['sort'][0])) {
			$sql .= " order by " . $params["sort"][0];
			isset($params["sort"][1]) ? $sql .= " " . $params["sort"][1] : $sql .= " asc";			
		}		
		//limit
		if(isset($params["limit"])) {
			$sql .= " limit " . $params["limit"];
			//offset
			if(isset($params["offset"])) {
				$sql .= " OFFSET " . $params["offset"];
			}
		}
		return $this->query($sql,$bind);		
	}
}