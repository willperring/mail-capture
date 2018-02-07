<?php

Class DataType_Email extends DataType {

	public static function validate( $value, &$errors ) {
		return filter_var( $value, FILTER_VALIDATE_EMAIL );
	}

	public static function getSqlType() {
		return "VARCHAR(255) NULL";
	}
}