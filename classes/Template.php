<?php

/**
 * The Template Class
 *
 * This class, in essence, provides a sort of back-end handlebars implementation.
 * Template fields are demarqued with double pairs of curly braces like {{this}}.
 * Optionally, modifiers can be applied for either existing functions, such as
 * this {{example|ucwords}}; this would apply the ucwords() function to the value
 * of the 'example' variable, or applied using the Template::addFilter() method.
 */
Class Template {

	/** @var $_filters {String:Callable} Array for registering template filters */
	private static $_filters = array();

	/**
	 * Add a Template Filter
	 *
	 * Adds a modifier to the library of available filters
	 *
	 * @see bootstrap.php
	 * @static
	 * @param $name   String  Name of the filter
	 * @param $filter Closure Filter function, accepts one param and returns a string
	 * @return void
	 */
	public static function addFilter( $name, Closure $filter ) {
		static::$_filters[ $name ] = $filter;
	}

	/** @var $_template String Contents of template file */
	private $_template = '';

	/**
	 * Constructor function
	 *
	 * @throws Exception
	 * @param $filename String Path and name of template file
	 * @return void
	 */
	public function __construct( $filename ) {

		$templatePath = TMPL_DIR . DS . $filename;

		if( !file_exists($templatePath) )
			Throw new Exception("Template '{$filename}' not found");

		$this->_template = file_get_contents( $templatePath );

	}

	/**
	 * Render the template using an array of substitution data
	 *
	 * Accepts a key-value pair array of placeholder => substitution value data
	 *
	 * @param $data [Mixed] Substitution data
	 * @return String Rendered template
	 */
	public function render( $data ) {

		$substitutionVars = $this->_createSubstitutionVars( $data );
		$replacementKeys  = $this->_findReplacementKeys( $this->_template );
		
		return $this->_performReplacements( $this->_template, $replacementKeys, $substitutionVars );
	}

	/**
	 * Create an array of substition string pairs to be rendered into the template
	 *
	 * This function takes an array of data, and converts them into placeholder identifiers
	 * and the value they should be replaced with. Primarily, this function exists to allow
	 * the data array to contain other data arrays. In this case, array keys are dot notated, as 
	 * per firstarraykey.secondarraykey.finalkey
	 *
	 * @internal
	 * @param $data   [Mixed] Array of data
	 * @param $prefix String  Used in recursion. Shouldn't contain a value when initially called
	 * @return {String:String} Substitution pairs
	 */
	private function _createSubstitutionVars( array $data, $prefix='' ) {

		$result = array();

		foreach( $data as $key => $value ) {

			$key = str_replace(' ', '_', $key);

			if( is_array($value) ) {
				$result = array_merge( $result, $this->_createSubstitutionVars($value, "{$prefix}{$key}.") );
			} 
			
			// Moved this out of the condition, to make arrays accessible to functions
			$result[ $prefix . $key ] = $value;
		}

		return $result;
	}

	/**
	 * Filter a template value through a named filter
	 * 
	 * This function accepts two parameters, a named filter and a value. If the filter anme
	 * is blank, the value is returned unmodified. If a matching named filter exists in the 
	 * Template filter registry, the value is passed through that and returned. Finally, if no
	 * match has been found, but the named filter matches a PHP function name, the value is
	 * processed and returned that way.
	 *
	 * @internal
	 * @param $filter String Named filter to use. Can be blank
	 * @param $value  Mixed  Value to filter
	 * @return Mixed Filtered value
	 */
	private function _filterValue( $filter, $value ) {
		
		if( empty($filter) )
			return $value;
		if( isset(static::$_filters[$filter]) ) {
			$filterFunction = static::$_filters[ $filter ];
			return $filterFunction( $value );
		}
		if( function_exists($filter) )
			return call_user_func( $filter, $value );

		trigger_error( "Unknown template filter: {$filter}", E_USER_WARNING );
		return $value;
	}

	/**
	 * Locate placeholder markings within the raw template
	 *
	 * Returns an array of arrays, where the secondary array has two keys:
	 * Key, and Filter. Key represents the placeholder variable, and Filter 
	 * the value filter to apply.
	 *
	 * @internal
	 * @param $source String Raw template contents
	 * @return [{String:[{String:String}]}] List of placeholders and their respective data keys
	 */
	private function _findReplacementKeys( $source ) {

		$keyRegex = '/\{\{(?<key>[A-Za-z0-9_\-.]+)(\|(?<filter>[A-Za-z0-9_\-]+))?\}\}/';

		$matches = array();
		if( !preg_match_all( $keyRegex, $source, $matches, PREG_SET_ORDER ) ) {
			return array();
		}

		$result = array();

		foreach( $matches as $match ) {
			$result[ $match[0] ] = array(
				'key'    =>  $match['key'],
				'filter' => @$match['filter'] ?: null,
			);
		}

		return $result;
	}

	private function _performReplacements( $source, $keys, $vars ) {

		if( !count($keys) )
			return $source;

		$replacements = array();

		foreach( $keys as $key => $details ) {
			$replacements[ $key ] = $this->_filterValue( 
				$details['filter'], 
				@$vars[ $details['key'] ] ?: null 
			);
		}

		return str_replace( array_keys($replacements), array_values($replacements), $source );

	}


}