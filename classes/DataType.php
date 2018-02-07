<?php

/**
 * Base class for all DataTypes
 *
 * DataTypes are used to provide validation rules and SQL Column definitions
 * for any type of data the the capture may be required to recieve. Descendent
 * classes should be named DataType_[Type] and expose two static methods, 
 * 'getSqlType()' and 'validate( $value, &$errors )'
 *
 * @abstract
 */
Abstract Class DataType {

	/**
	 * Validate a value by data type
	 *
	 * @static
	 * @see DataType::validateClass()
	 * @param $value  Mixed     Value to validate
	 * @param $type   String    Name of data type to validate against
	 * @param $errors &[String] Optional array to recieve error information (ref)
	 * @return bool True if valid
	 */
	public static function validateValue( $value, $type, &$errors=array() ) {
		$class = static::validateClass( $type );
		return $class::validate( $value, $errors );
	}

	/**
	 * Get a Column definition by data type
	 *
	 * @static
	 * @see DataType::validateClass()
	 * @param $type String Data type to get definiton for
	 * @return String Column definition
	 */
	public static function getSqlTypeFor( $type ) {
		$class = static::validateClass( $type );
		return $class::getSqlType();
	}

	/**
	 * Validate and return a datatype class
	 *
	 * Accepts a name and attempts to instantiate a class of DataType_[Name]
	 *
	 * @static
	 * @internal
	 * @throws Exception
	 * @return DataType
	 */
	private static function validateClass( $type ) {
		$typeClass = "DataType_" . $type;
		if( !class_exists($typeClass) )
			Throw new Exception( "Unable to locate DataType class for '{$type}'");
		return $typeClass;
	}

}