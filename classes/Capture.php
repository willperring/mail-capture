<?php

/**
 * Capture Base Class
 *
 * This class is the ancestor of all other capture classes. The two main capture
 * types, SignUp and ContactForm derive from this. As such, all shared functionality
 * should be located in this class
 *
 * @abstract
 */
Abstract Class Capture {

	/** @var $_name String Name of the Capture */
	protected $_name     = null;
	/** @var $_location String Location of the capture files, set automatically by the Router */
	protected $_location = null;

	/** @var $_response AjaxResponse Response Object created to reply on behalf of the handler */
	protected $_response = null; 
	/** @var $_fields [String] Associative array of fields in the form field name => data type */
	protected $_fields   = array();
	/** @var $_required [String] Indexed array of required fields */
	protected $_required = array();
	/** @var $_users [String] Associative array of instance users in format user name => password hash */
	protected $_users    = array();

	/** @var $_debug Boolean Set to true to enable debugging */
	protected $_debug    = false;

	/**
	 * Array of Global Users to apply to all captures
	 * 
	 * This is private rather than protected so it can only be accessed via this class. 
	 * This is because the method to validate
	 * 
	 * @var $_globalUsers [String] Associative array of global users in format user name => password hash md5 */
	private $_globalUsers = array(
		'masteradmin' => "<<set md5 hash here>>", 
	);

	/**
	 * Pre-capture data processing callback
	 *
	 * This function is called to apply any data transformations BEFORE the data is saved to the 
	 * container file. This is primarily for reformatting the data into something more usable or
	 * appropriate for the storage medium.
	 *
	 * This function passes the array parameter by reference
	 *
	 * @abstract
	 * @param $data 
	 * @return void
	 */
	abstract protected function _preCapture( array &$data );


	/**
	 * Post-capture data processing callback
	 *
	 * This function is called to process any actions AFTER the data is saved to the 
	 * container file. This is generally used to add the user to MailChimp lists, send
	 * emails through Mandrill, etc.
	 *
	 * @abstract
	 * @param $data 
	 * @return void
	 */
	abstract protected function _postCapture( array $data );

	/**
	 * Final data processing callback
	 *
	 * This function is called to allow any final actions to the
	 * capture, but without overriding the postCapture callback
	 * which normally is used to provide the addition of data
	 * to a MailChimp list, or other similar action. This isn't
	 * abstract, so as to not be necessary to define in descendent captures
	 *
	 * @param $data 
	 * @return void
	 */
	protected function _finalCallback( array $data ) {}

	/**
	 * Object Constructor
	 *
	 * Performs any instantiation requirement checks. At this level,
	 * it checks to see if the capture has some configured fields. Descendant
	 * classes may augment this further.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function __construct() {

		$this->_response = new AjaxResponse();

		if( !count($this->_fields) ) 
			Throw new Exception("This Capture isn't configured to receive any data");
	}

	/**
	 * Set the name of the capture
	 *
	 * @final
	 * @see Router->getHandler()
	 * @param $name String Name of the capture
	 * @return void
	 */
	final public function setName( $name ) {
		$this->_name = $name;
	}

	/**
	 * Set the location of the handler
	 *
	 * Sets the path to the folder that the handler is located in. This is used
	 * to instantiate the database.
	 *
	 * @final
	 * @see Router->getHandler()
	 * @param $location String Location to the handler
	 * @return void
	 */
	final public function setHandlerLocation( $location ) {
		$this->_location = $location;
		return $this;
	}

	/**
	 * Perform an action on the current handler
	 *
	 * "Actions" are methods that each handler can perform, such as receiving data
	 * (which is the default), listing field information, or providing a place
	 * to view captures. This attempts to perform a named action on the handler,
	 * accepting a data bundle of parameters.
	 * Callable action functions are prefixed action_
	 *
	 * @final
	 * @see Bundle
	 * @param $action String Name of action to attempt to perform
	 * @param $bundle Bundle Bundle of data from request parameters
	 * @throws Exception
	 * @return Mixed Result of handler function
	 */
	final public function performAction( $action, Bundle $bundle ) {

		// Actions that can be accessed via URL are prefixed for security
		$action = 'action_' . ( $action ?: 'receive' );

		if( !method_exists( $this, $action ) ) {
			Throw new Exception("Handler is unsure how to process action '{$action}'");
		}

		return call_user_func( array($this, $action), $bundle );
	}

	/**
	 * Provide authentication for an action
	 *
	 * If called by an action, this internal function will enable and require
	 * http basic authentication before progressing. Credentials are retained 
	 * for the duration of the session in the $_SERVER array.
	 *
	 * @final
	 * @internal
	 * @return void
	 */
	final protected function _authenticate() {

		$captureId = $this->_getCaptureIdentifier();
		$userKey   = $captureId . "-user";

		if( isset($_SESSION[ $userKey ]) )
			return $_SESSION[ $userKey ];

		// @'d to skip the isset() and empty() checks here
		if( $this->_validateUser( @$_SERVER['PHP_AUTH_USER'], @$_SERVER['PHP_AUTH_PW']) ) {
			$_SESSION[ $userKey ] = $_SERVER['PHP_AUTH_USER'];
			return $_SERVER['PHP_AUTH_USER'];
		}

		header('WWW-Authenticate: Basic realm="Email Registration Management"');
    	header('HTTP/1.0 401 Unauthorized');
    	echo 'This action requires you to be authenticated.';
    	exit;

	}

	/**
	 * Action to provide information about capture fields
	 *
	 * When called, outputs a json-encoded object containing information 
	 * about the configured fields and their datatypes, and a list of 
	 * which fields are required.
     *
     * @final
     * @return void
     */
	final private function action_fields() {
		echo json_encode( array(
			'fields'   => $this->_fields,
			'required' => $this->_required,
		));
	}

	/**
	 * Attempt to log the user out
	 *
	 * IMPORTANT: I'm not 100% sure if this works, owing to how basic auth
	 * information seems to be managed by Apache. Don't rely on this.
	 *
	 * @final
	 * @return void
	 */
	final private function action_logout() {

		$captureId = $this->_getCaptureIdentifier();
		$userKey   = $captureId . "-user";

		$_SESSION[ $userKey ] = false;
		
		// this could require some investigation as to how it operates
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );

		echo "You have logged out!";
		#var_dump("<pre>", $_SERVER);

	}

	/**
	 * Action to receive submitted data
	 *
	 * This is the main event - the action that collects, processes and saves user
	 * submissions. Firstly, this will validate all submitted against the declared datatype
	 * using the DataType class validate methods. Once this is complete, the preCapture hook
	 * is called to process the data into whatever format we want to save it in. The data is then
	 * written to the SQLite container file, and then the postCapture hook is called, to engage with
	 * sny third party services such as MailChimp or Mandrill.
	 *
	 * @final
	 * @param $bundle Bundle Data bundle from request
	 * @return void
	 */
	final protected function action_receive( Bundle $bundle ) {

		$data  = $bundle->getPost();
		$state = $this->_validateData( $data );

		// Must accept data from other domains
		header("Access-Control-Allow-Origin: *");

		if( !$state->valid ) {

			$adjectives = array();
			if( count($state->invalid) )
				$adjectives[] = "invalid";
			if( count($state->missing) )
				$adjectives[] = "missing";

			$adjectives = implode( ' and ', $adjectives );

			$this->_response->fail()
				->setMessage("There are {$adjectives} fields")
				->setMissing( $state->missing )
				->setInvalid( $state->invalid )
				->send();
			exit;

		}

		// Apply any transformations
		$data = $state->data;
		$this->_preCapture( $data );

		// State data has been validated against column names
		$this->_saveData( $data );
		$this->_postCapture( $data );
		$this->_finalCallback( $data );

		$this->_response
			->setMessage("Your details have been saved")
			->send();
	}

	/**
	 * Get a unique identifier for the capture
	 *
	 * In order to get a truly unique identifier for the capture, a crc32 hash
	 * of the file path is generated. This means no two captures can have the same
	 * ID as they would need to exist in the same location. However, it also means 
	 * that the ID will change if the capture is moved.
	 *
	 * The generated ID is used as part of the session identifier, so that data from
	 * one capture doesn't bleed through to another if a user access the data for 
	 * more than one
	 * 
	 * @final
	 * @see $this->_authenticate()
	 * @return String Unique identifier for the capture
	 */
	final private function _getCaptureIdentifier() {
		$relector = new ReflectionClass( get_called_class() );
		return crc32( $relector->getFilename() );
	}

	/** 
	 * Validate a username/password combination
	 *
	 * Validates a user against the defined user password hash tables. These are
	 * combined from the global users defined in this class and the capture-specifc
	 * users defined in each derived class.
	 *
	 * @final
	 * @see $this->authenticate()
	 * @param $user String Username
	 * @param $pass String Password
	 * @return bool True if user/password pair is valid
	 */
	final private function _validateUser( $user, $pass ) {

		if( empty($user) || empty($pass) )
			return false;

		$users = array_merge( $this->_globalUsers, $this->_users );
		return ( isset($users[$user]) && $users[$user] == md5($pass) );
	}

	/**
	 * Action to display the Admin page for the capture
	 *
	 * @return mixed
	 */
	protected function action_admin() {
		$user = $this->_authenticate();
		$data = $this->_getData();

		$privateTemplate = CAPT_DIR . DS . $this->_name . DS . $this->_adminTemplate;
		if( file_exists($privateTemplate) )
			return include( $privateTemplate );
		include( TMPL_DIR . DS . $this->_adminTemplate );
	}

	/**
	 * Action to provide a download of the captured data
	 *
	 * This action uses the ouput buffer to write the CSV data to,
	 * which is then captured, read for size, and output through to
	 * the user with download file headers
	 *
	 * @return void
	 */
	protected function action_download( Bundle $bundle ) {

		$user = $this->_authenticate();
		$data = $this->_getData();

		$payload = "";
		$headers = false;

		ob_start();
		$fh = fopen('php://output', 'w');

		foreach( $data as $row ) {
			$headers = $headers || fputcsv( $fh, array_keys($row) );
			fputcsv( $fh, $row );
		}

		fclose( $fh );

		$payload = ob_get_contents();
		ob_clean();

		if( empty($payload) )
			die("No data");

		$stamp = date('Y-m-d_H-i-s');

		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header("Content-Disposition: attachment; filename={$this->_name}_{$stamp}.csv"); 
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . mb_strlen($payload) );
		
		echo $payload;
	}

	/**
	 * Get All Data stored in capture
	 *
	 * Executes an SQL statement to return all columns of all rows
	 * from within the PDO. Filters out the 'deleted' system column.
	 *
	 * @return [{String:Mixed}] Capture data
	 */
	protected function _getData() {

		$pdo = $this->_getPDO();

		$columns = $this->_fields;
		unset( $columns['deleted'] );
		$columns = implode( ',', array_keys($columns) );

		$sql  = "SELECT {$columns}, created FROM Data;";
		$stmt = $pdo->query( $sql );

		if( $this->_debug ) {
			echo "Select SQL: " . $sql . "\n";
		}

		return $stmt->fetchAll();
	}
	
	/**
	 * Generate Data Table SQL Create Statement
	 *
	 * operating from the field definition list, and appending columns for a creation
	 * timestamp and a deletion flag, this function generates the SQL statement to
	 * create the data table for the capture
	 *
	 * @see DataType::getSqlTypeFor()
	 * @throws Exception
	 * @return String SQL statement to create table
	 */
	protected function _getDataTableCreateSql() {

		$columns = array();
		foreach( $this->_fields as $field => $type ) {
			
			$sqlDefinition = DataType::getSqlTypeFor( $type );
			if( empty($sqlDefinition) )
				Throw new Exception("Unable to get SQL Column Definition for type '{type}'");
			
			$columns[] = $field . ' ' . $sqlDefinition;
		}

		$columns = implode(', ', $columns);
		$sql     = "CREATE TABLE Data ( {$columns}, created TIMESTAMP NULL, deleted INTEGER );";

		if( $this->_debug ) {
			echo "Table Creation SQL: " . $sql . "\n";
		}

		return $sql;
		
	}

	/**
	 * Get A PDO Object
	 *
	 * As well as generating the PDO Object, this also verifies the presence of
	 * the data table, and if it isn't present, creates it
	 *
	 * @see PDOWrapper
	 * @return PDOWrapper PDO Object (inside wrapper)
	 */
	protected function _getPDO() {
		
		$pdo = new PDOWrapper( $this->_location . DS . 'data' );
		
		if( !$pdo->assertTable('Data') ) { 
			$sql = $this->_getDataTableCreateSql();
			$pdo->exec( $sql );
		}

		return $pdo;
	}

	/**
	 * Save an array of data into the capture data table
	 * 
	 * @throws Exception
	 * @param $data {String:Mixed} Array of data to save
	 * @return Capture Self-reference for method chaining
	 */
	protected function _saveData( array $data ) {

		$pdo = $this->_getPDO();

		$fields = array_keys( $data );
		$fields = implode( ', ', $fields );

		$placeholders = array_fill( 0, count($data), '?' );
		$placeholders = implode( ', ', $placeholders );

		$sql  = "INSERT INTO Data ( {$fields}, created, deleted ) VALUES ( {$placeholders}, DateTime('now'), 0 );";
		if( $this->_debug ) {
			echo $sql . "\n";
			var_dump( $data );
			return $this;
		}		

		$stmt = $pdo->prepare( $sql );
		
		if( ! $stmt->execute( array_values($data) ) )
			Throw new Exception("Unable to save data");
		
		return $this;
	}

	/**
	 * Validate an array of data
	 *
	 * This uses the defined field list to validate an array of 
	 * data using the DataType validation methods. The return va;ue of
	 * this functigon is a Standard Object, with properties of 'invalid'
	 * and 'missing'; these are both arrays of fields that are either
	 * not valid or missing, 'valid'; this is true if the data passes 
	 * validation, and 'data'; this contains the original passed data stripped
	 * of any undefined fields.
	 *
	 * @see DataType::validateValue()
	 * @param $data {String:Mixed} Data to validate
	 * @return StdClass Object containing validation information
	 */
	protected function _validateData( $data ) {

		// Strip erroneous data
		$data = array_intersect_key( $data, $this->_fields );

		$result = new StdClass();
		$result->invalid = array();
		$result->missing = array();
		$result->valid   = true;
		$result->data    = $data;

		// Check for missing values
		foreach( $this->_required as $required ) {
			if( empty( $data[$required]) ) {
				$result->missing[] = $required;
				$result->valid     = false;
			}
		}

		// Check for invalid values
		foreach( $data as $field => $value ) {
			// don't validate empty - may be optional.
			if( empty($value) )
				continue;

			$dataType = $this->_fields[ $field ];

			if( ! DataType::validateValue( $value, $dataType ) ) {
				$result->invalid[] = $field;
				$result->valid     = false;
			}
		}

		return $result;

	}

}