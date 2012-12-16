<?php

/**
* MarySQL class
* Developed by Omar BERRAYTI
* <berrayti.omar@gmail.com>
* You can use this class for personal or commercial project.
* You can edit on the class but you are not allowed to remove the author copyrights.
**/

class MarySQL{

	// Connection to the DB
	public $Hostname = MYSQL_HOST;	// MySQL Hostname
	public $Username = MYSQL_USER;	// MySQL Username
	public $Password = MYSQL_PASS;	// MySQL Password
	public $Database = MYSQL_NAME;	// MySQL Database

	// Debuging
	public $debug = true;

	// Check if there is a connection
	protected $isConnected = false;

	// PDO connection
	protected $pdo;


	/**
	* The class constructor
	**/
	public function __construct(){
		$this->Connect();
	}

	/**
	* Responsable of showing the errors
	* @param string $msg the message to show if the $debug is set to true
	* @param string $msg2 the message to show if the $debug is set to false
	**/
	protected function log($msg,$msg2=null){
		if($this->debug == true){
			die($msg);
		}else{
			if(isset($msg2))
				die($msg2);
			else
				die("An error occurred.");
		}
	}	


	/**
	* Connect to the DB
	**/
	public function Connect(){
		if ($this->isConnected) return;
		try {
			$this->pdo = new PDO('mysql:host='.$this->Hostname.';dbname='.$this->Database, $this->Username, $this->Password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->isConnected = true;
		} catch (PDOException $e) {
			$this->log("<strong>Error !</strong> ".$e->getMessage(),"Can not connect to the database");
		}
	}

	/**
	* Bind params
	* @param PDOStatement $s PDO Statement
	* @param array $values the values to bind
	* @return void
	**/
	protected function bindParams($s,$values){
		foreach ($values as $k => &$v) {
			if(is_integer($k)){
				if(is_null($v)){
					$s->bindValue($k+1,null,PDO::PARAM_NULL);
				}else{
					$s->bindParam($k+1,$v,PDO::PARAM_INT);
				}
			}else{
				if(is_null($v)){
					$s->bindValue($k,null,PDO::PARAM_NULL);
				}else{
					$s->bindParam($k,$v,PDO::PARAM_STR);
				}
			}
		}
	}

	/**
	* Execute a SQL query
	* @param string $sql the sql query to run
	* @param array  $values an array containing the values you want to bind
	* @param string $fetch the type of result you want (object,array....)
	* @return the type of data you want (assoc,object,default)
	**/
	public function execute($sql,$values=array(),$fetch='assoc'){
		$fetches = array(
			'assoc'   => PDO::FETCH_ASSOC,
			'default' => PDO::FETCH_BOTH,
			'object'  => PDO::FETCH_OBJ
		);
		try {
			$query = $this->pdo->prepare($sql);
			$this->bindParams($query,$values);
			$query->execute();
			return $query->fetchAll($fetches[$fetch]);
		} catch (PDOException $e) {
			$this->log("<strong>Error !</strong> ".$e->getMessage());
		}
	}

	/**
	* Find some data from the DB
	* @param array $query the query conditions
	**/
	public function find($query,$values=array(),$fetch='assoc'){
		$sql = "SELECT ";


		// Fields
		if(isset($query['fields'])){
			if(is_array($query['fields'])){
				$sql .= implode(',', $query['fields']).' ';
			}else{
				$sql .= $query['fields'];
			}
		}else{
			$sql .= "* ";
		}

		// Table
		$sql .= " FROM ".$query['table']." as ".$query['table']." ";

		// Joins
		if(isset($query['join'])){
			$joins = array(
				'full'  => 'FULL',
				'inner' => 'INNER',
				'right' => 'RIGHT',
				'left'  => 'LEFT'
			);
			$join = $joins[$query['join'][0]];
			foreach ($query['join'][1] as $table => $fields) {
				$sql .= "$join JOIN $table as $table ON $fields ";
			}
		}

		// Conditions
		if(isset($query['conditions'])){
			$sql .= "WHERE ";
			if(!is_array($query['conditions'])){
				$sql .= $query['conditions'];
			}else{
				$sql .= implode(' AND ', $this->secure($query['conditions']));
			}
		}

		// Order
		if(isset($query['order'])){
			$sql .= " ORDER BY ".$query['order'];
		}

		// Limit
		if(isset($query['limit'])){
			$sql .= " LIMIT ".$query['limit'];
		}		

		return $this->execute($sql,$values,$fetch);
	}

	/**
	* Find one result
	* @param array $query the query conditions
	**/
	public function findFirst($query,$values=array(),$fetch='assoc'){
		return current($this->find($query,$values,$fetch));
	}

	/**
	* Insert or update data
	* @param array $data an array containing the data you want to save
	* @return true if everything is righ, or an error if there is any errors while saving the data
	**/
	public function Save($data){
		$fields = array();
		foreach ($data['data'] as $k => $v) {
			$fields[] = "$k=:$k";
		}
		if(isset($data['id']) && !empty($data['id'])){
			$sql = "UPDATE ".$data['table']." SET ".implode(',',$fields)." WHERE ".$data['id'][0]."=".$data['id'][1];
		}else{
			$sql = "INSERT INTO ".$data['table']." SET ".implode(',',$fields);
		}
		$query = $this->pdo->prepare($sql);
		$this->bindParams($query,$data['data']);
		$query->execute();
		return true;	
	}

	/**
	* Delete data
	* @param string $table the table name
	* @param array $conditions the conditions of the row to delete
	* @param array $values if you want to pass variables to the query
	**/
	public function delete($table,$conditions,$values=array()){
		$sql = "DELETE FROM $table WHERE ";
		if(!is_array($conditions)){
			$sql .= $conditions;
		}else{
			$sql .= implode(' AND ', $this->secure($conditions));
		}
		//die($sql);
		$query = $this->pdo->prepare($sql);
		$this->bindParams($query,$values);
		$query->execute();
	}

	/**
	* Secure data
	**/
	protected function secure($data){
		$result = array();
		foreach ($data as $k => $v) {
			$v = (strpos($v, ':') === 0) ? $v : '"'.mysql_escape_string($v).'"';
			$result[] = "$k=$v";
		}
		return $result;
	}

}

?>