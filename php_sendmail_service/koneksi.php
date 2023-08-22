<?php
	date_default_timezone_set('Asia/Jakarta');

	class Database {
		private $host     = 'localhost';
		private $db_name  = 'db_api';
		private $username = 'root';
		private $password = '';

		public $conn_1;

		public function getConnection(){
			$this->conn_1 = null;

			try {
				$this->conn_1 = new PDO('mysql:host='.$this->host.';dbname='.$this->db_name, $this->username, $this->password);
				$this->conn_1->exec('set names utf8');

				$return = $this->conn_1;
				
			}
			catch (PDOException $exception) {
				$return = 'Connection error: '.$exception->getMessage();
			}

			return $return;
		}
	}
?>