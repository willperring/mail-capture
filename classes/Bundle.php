<?php

/**
 * Bundle Class
 *
 * The bundle class is a convenient way to pass parameters from the Query String,
 * POST Data and url slugs to the Handler classes.
 */
Class Bundle {

	/** @var $_get  [Mixed] Data from the Query string */
	private $_get;
	/** @var $_post [Mixed] Data submitted via POST */
	private $_post;
	/** @var $_url  [Mixed] Data submitted via URL slugs */
	private $_url;

	/**
	 * Constructor function
	 *
	 * @param $get  [Mixed] Array of data from the query string
	 * @param $post [Mixed] Array of data submitted via POST
	 * @param $url  [Mixed] Array of data submitted via URL slugs
	 */
	public function __construct( $get=null, $post=null, $url=null ) {
		$this->setGet( $get )
			->setPost( $post )
			->setUrl( $url );
	}

	/**
	 * Set the data from the Query string
	 *
	 * @param $get [Mixed] Array of data from the query string 
	 * @return Bundle Self-reference for method chaining
	 */
	public function setGet( $get ) {
		$this->_get = $get;
		return $this;
	}

	/**
	 * Set the data submitted via POST
	 *
	 * @param $post [Mixed] Array of POST data
	 * @return Bundle Self-reference for method chaining
	 */
	public function setPost( $post ) {
		$this->_post = $post;
		return $this;
	}

	/**
	 * Set the data from the URL slugs
	 *
	 * @param $url [Mixed] Array of data from the URL slugs
	 * @return Bundle Self-reference for method chaining
	 */
	public function setUrl( $url ) {
		$this->_url = $url;
		return $this;
	}

	/**
	 * Test is POST data was submitted
	 *
	 * @return bool True is POST data was part of the request
	 */
	public function hasPost() {
		return !empty($this->_post) && count( $this->_post );
	}

	/**
	 * Return the query string data
	 *
	 * Returns a specific field, if a key is specified.
	 * If not, the entire array is returned.
	 * @see $this->get();
	 *
	 * @param $key string|null An optional key to retrieve a specific field
	 * @return Mixed|[Mixed] The specified key, or the whole array
	 */
	public function getGet( $key=null ) {
		return $this->get( $key, $this->_get );
	}

	/**
	 * Return the POST data
	 *
	 * Returns a specific field, if a key is specified.
	 * If not, the entire array is returned.
	 * @see $this->get();
	 *
	 * @param $key string|null An optional key to retrieve a specific field
	 * @return Mixed|[Mixed] The specified key, or the whole array
	 */	
	public function getPost( $key=null ) {
		return $this->get( $key, $this->_post );
	}

	/**
	 * Return the array of URL slug data
	 *
	 * @return [Mixed] Array of data from URL slugs
	 */
	public function getUrl() {
		return $this->_url;
	}

	/**
	 * Function to standardise return process
	 *
	 * If no key is specified, it returns the array
	 * If a key is specified and exists, it returns the value
	 * If a key is specified and DOESN'T exist, it returns NULL
	 * 
	 * @param $key    string|null Optional key to look up
	 * @param $source [Mixed]     Source array
	 * @return Mixed|[Mixed]|null
	 */
	private function get( $key, $source ) {
		if( empty($key) )
			return $source;
		return @$source[ $key ] ?: null ;
	}


}