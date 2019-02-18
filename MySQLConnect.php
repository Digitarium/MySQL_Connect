<?php
	namespace Connect
	require_once("iConnectData.php");
	
	class MySQLConnect implements iConnectData{
		public $error_code = 0;
		public $error_message = '';
		public $sql_result = array();
		private $host = null;
		private $user = null;
		private $password = null;
		private $db = null;
		private $port = null;
		private $MySqlConnect;
		
		public function __construct(){
			if (is_readable('config.ini')){
				$conf = parse_ini_file('config.ini', true);
				if ($conf){
					if(array_key_exists('MySQLDB', $conf)){
						if(array_key_exists('host', $conf['MySQLDB']))
							$this->host = $conf['MySQLDB']['host'];
						if(array_key_exists('port', $conf['MySQLDB']))
							$this->port = $conf['MySQLDB']['port'];
						if(array_key_exists('db', $conf['MySQLDB'])){
							if(preg_match('/^[A-Za-z\-_\d]{1,64}$/', $conf['MySQLDB']['db'])) //limit 64 characters dbname in MySQL
								$this->db = $conf['MySQLDB']['db'];
						}
						if(array_key_exists('user', $conf['MySQLDB']))
							$this->user = $conf['MySQLDB']['user'];
						if(array_key_exists('password', $conf['MySQLDB']))
							$this->password = $conf['MySQLDB']['password'];
					}
				}
			}
			try{
				$this->MySqlConnect = new mysqli($this->host, $this->user, $this->password, $this->db, $this->port);
				if ($this->MySqlConnect->connect_errno){
					throw new Exception($this->MySqlConnect->connect_error, $this->MySqlConnect->connect_errno);
				}
				if (!$this->MySqlConnect->set_charset("utf8")) {
					throw new Exception("Не выполнена загрузка набора символов UTF-8", 8);
				}
				return true;
			}
			catch (Exception $e){
				$this->error_code = $e->getCode();
				$this->error_message = $e->getMessage();
				return false;
			}
		}
			
		public function execute_query($query, $params = array()){
			$this->sql_result = array();
			
			if (!is_string($query)){
				$this->error_code = 1;
				$this->error_message = 'Первый аргумент QUERY не является строковой переменной';
				return false;
			}
			if (!is_array($params)){
				$this->error_code = 2;
				$this->error_message = 'Последний аргумент PARAMS не является массивом';
				return false;
			}
			else
				$query = str_replace('?','%s',$query);	
			if (substr_count($query, '%s') != count($params)){
				$this->error_code = 3;
				$this->error_message = 'Количество аргументов sql-запроса не сооответствует количеству аргументов в массиве';
				return false;
			}
			foreach ($params as &$param) {
				if (!is_string($param) && !is_int($param) && !is_float($param)){
					$this->error_code = 4;
					$this->error_message = 'Элементы массива параметров запроса не являются числами или строками';
					return false;
				} 
				else
					$param = $this->MySqlConnect->real_escape_string($param);				
			}
			$query = vsprintf($query,$params);
			$this->MySqlConnect->multi_query($query);
			do {
				if (!$this->MySqlConnect->errno){
					if ($result = $this->MySqlConnect->store_result()) {
						$sql_result_temp = array();
						while ($row = $result->fetch_assoc()) {
							array_push($sql_result_temp, $row);
						}
						array_push($this->sql_result, $sql_result_temp);
						$result->free();
					}
				}
				else{
					$this->error_code = $this->MySqlConnect->errno;
					$this->error_message = $this->MySqlConnect->error;
					$this->sql_result = array();
					return false;
				}
			} while ($this->MySqlConnect->more_results() && $this->MySqlConnect->next_result());
			return true;	
		}
		
		public function __destruct(){
			if (!$this->MySqlConnect->connect_errno)
				$this->MySqlConnect->Close();
			$this->MySqlConnect = NULL;
		}
	}
?>