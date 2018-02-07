<?php
require( dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php' );
Router::route( $_SERVER['REDIRECT_URL'] );
