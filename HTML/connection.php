<?php
// connection.php
class Database {
    private static $instance = null;
    private $conn;
    
    private $servername = "localhost";
    private $username = "root";
    private $password = "g8gbV0noL$3&fA6x-GAMER";
    private $dbname = "perfectfit";

    private function __construct() {
        $this->conn = new mysqli(
            $this->servername, 
            $this->username, 
            $this->password, 
            $this->dbname
        );

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}