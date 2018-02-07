<?php

Class DataType_LargeText extends DataType {

	public static function validate( $value, &$errors ) {
		return is_string( $value );
	}

	public static function getSqlType() {
		return "TEXT NULL";
	}
}