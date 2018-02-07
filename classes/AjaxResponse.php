<?php

/**
 * AjaxResponse Class
 *
 * Provides a standardised way of returning information about a submission
 * to the front end of whichever site is utilising the capture application
 *
 * Response structure:
 * {
 * 	success : (Boolean),
 *	message : (String),
 *	invalid : [ (String) ], 	(optional)
 *	missing : [ (String) ]		(optional)	
 * }
 * 
 */
Class AjaxResponse {

	/** @var $_message String   The status message to return */
	private $_message = '';
	/** @var $_status  Boolean  The true/false success state of the interaction */
	private $_status  = true;

	/** @var $_invalid [String] Fields that have failed validation */
	private $_invalid = array();
	/** @var $_missing [String] Fields that are required but missing */
	private $_missing = array();
	/** @var $_debug   [String] Array of debug information when something goes wrong */
	private $_debug   = array();

	/**
	 * Fail a request
	 *
	 * Call this method to indicate that a request was unsuccessful
	 *
	 * @return AjaxResponse Self-reference for method chaining
	 */
	public function fail() {
		$this->_status = false;
		return $this;
	}

	/**
	 * Set the status message
	 *
	 * @param $message String The status message to return
	 * @return AjaxResponse Self-reference for method chaining
	 */
	public function setMessage( $message ) {
		$this->_message = $message;
		return $this;
	}

	/**
	 * Set the fields that failed validation
	 *
	 * @param $invalid [String] Fields that failed validation
	 * @return AjaxResponse Self-reference for method chaining
	 */
	public function setInvalid( array $invalid ) {
		$this->_invalid = $invalid;
		return $this;
	}

	/**
	 * Set the required fields that are missing
	 *
	 * @param $invalid [String] Fields that failed validation
	 * @return AjaxResponse Self-reference for method chaining
	 */
	public function setMissing( array $missing ) {
		$this->_missing = $missing;
		return $this;
	}

	/**
	 * Add debug Information to the response
	 *
	 * @param $string String Debug information to add
	 * @return AjaxResponse Self-reference for method chaining
	 */
	public function addDebug( $string ) {
		$this->_debug[] = $string;
		return $this;
	}

	/**
	 * Send the response
	 *
	 * @return void
	 */
	public function send() {

		$payload = array(
			'success' => $this->_status, 
			'message' => $this->_message,
		);

		if( count($this->_invalid) ) 
			$payload['invalid'] = $this->_invalid;
		if( count($this->_missing) )
			$payload['missing'] = $this->_missing;
		if( count($this->_debug) )
			$payload['debug']   = $this->_debug;

		echo json_encode( $payload );
	}

}