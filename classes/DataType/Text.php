<?php

Class DataType_Text extends DataType {

	public static function validate( $value, &$errors ) {
		return is_string( $value ) && ( mb_strlen($value) <= 255 );
	}

	public static function getSqlType() {
		return "VARCHAR(255) NULL";
	}
}