<?php
	/**
	* Database Connection
	*/
	class DatabaseConnect {
  /**
   * For live enviroment:
	 * private $user = 'reftarka_database';
	 * private $pass = 'reftarka_database';
   *
   * For Local enviroment:
	 * private $user = 'root';
   * private $pass = '';
   * 
   **/
		private $server = 'localhost';
		private $dbname = 'reftarka_client';
		private $user = 'root';
    private $pass = '';

		public function connect() {
			try {
				$conn = new PDO('mysql:host=' .$this->server .';dbname=' . $this->dbname, $this->user, $this->pass);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				return $conn;
			} catch (\Exception $e) {
				echo "Database Error: " . $e->getMessage();
			}
		}
        
	}
?>