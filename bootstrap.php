<?php

define( 'DS', DIRECTORY_SEPARATOR );
define( 'BASE_DIR',   __DIR__ );
define( 'CLASS_DIR', BASE_DIR . DS . 'classes'   );
define( 'LIB_DIR',   BASE_DIR . DS . 'lib'       );
define( 'CAPT_DIR',  BASE_DIR . DS . 'captures'  );
define( 'TMPL_DIR',  BASE_DIR . DS . 'templates' );

// Enable Local Debug
if( in_array( $_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1') ) ) {
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
} else {
	ini_set( 'display_errors', 0 );
}

require_once( LIB_DIR . DS . 'Mailchimp.php' );
require_once( LIB_DIR . DS . 'Mandrill.php'  );

spl_autoload_register( function($class) {
	$class = str_replace( '_', DS, $class ); 
	$path  = CLASS_DIR . DS . $class . '.php';
	if( file_exists($path) )
		require_once( $path );
});

Template::addFilter( 'mailto', function($value) {
	return "<a href='mailto:{$value}'>{$value}</a>";
});

Template::addFilter( 'fieldlist', function($value) {
	if( !is_array($value) )
		return $value;

	$result = '';
	foreach( $value as $key => $field ) {

		$result .= ucwords($key) . ": {$field}\n";
	}

	return $result;
});


