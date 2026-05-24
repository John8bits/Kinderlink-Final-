<?php
    class Database{

        private $host ="localhost";
        private $user = "root";
        private $password = "";
        private $dbname = "KinderLink";

        public $conn;

        public function __construct(){
            
            try{
                $this->conn = new PDO("mysql:host=".$this->host . ";dbname=".$this->dbname,
                $this->user,$this->password);

                $this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
                #echo "Connection success";
            }catch(PDOException $e){
                die("Connection Failed ".$e->getMessage());
            }

        }

        public function getConnection() {
            return $this->conn;
        }

    }

?>