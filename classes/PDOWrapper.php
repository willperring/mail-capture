<?php

/**
 * Wrapper class for PDO object
 *
 * This wrapper standardises the PDO generation process,
 * simplifying their configuration process and allowing a simple
 * way to augment their functionality, as per the assertTable() function.
 */
Class PDOWrapper {

	/** @var $_pdo  PDO    Instantiated PDO Object */
	private $_pdo;
	/** @var $_path String Internal reference to location of SQLite file */ 
	private $_path;

	/** 
	 * Constructor function
	 *
	 * Applies a standard set of configuration options to the PDO object
	 *
	 * @throws Exception
	 * @param $path String Path to target folder for SQLite file
	 * @return void
	 */
	public function __construct( $path ) {

		$this->_path = $path;

		try {

			if( !is_dir($path) && !mkdir($path) )
				Throw new Exception("Unable to create data directory");

			if( !is_writable($path) && !chmod($path, 0744) )
				Throw new Exception("Unable to make data directory writeable");

			$sqlitePath  = $path . DS . "data.sqlite";

			$this->_pdo = new PDO("sqlite:/{$path}/data.sqlite");
			$this->_pdo->setAttribute( PDO::ATTR_TIMEOUT,            60 		            );
			$this->_pdo->setAttribute( PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION );
			$this->_pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC       );

		} catch( Exception $e ) {
			Throw $e;
		}
	}

	/**
	 * Generic pass-through function handler
	 *
	 * This magic method attempts to pass any function calls that are not
	 * explicitly defined in this class onto the PDO object contained 
	 * within
	 *
	 * @see PDO
	 * @param $name      String  Name of the function called
	 * @param $arguments [Mixed] Array of parameters passed to the function
	 * @return mixed Result of function call
	 */
	public function __call( $name, $arguments ) {
		return call_user_func_array( array($this->_pdo, $name), $arguments );
	}

	/** 
	 * Assert the presence of a named table
	 *
	 * @param $table String The name of the table to check for
	 * @return Bool True if the table exists
	 */
	public function assertTable( $table ) {
		$query = $this->_pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?;");
		$query->execute( array($table) );
		return !! $query->fetch();
	}

}