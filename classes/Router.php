<?php

/**
 * Router to handle all requests and covert them to a handler and action
 */
Class Router {

	/**
	 * Perform a routing lookup for a path
	 *
	 * @static
	 * @param $path String Requested path
	 * @return void
	 */
	public static function route( $path ) {

		// We don't want to cache anything on this site. ever.
		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		try {

			$req     = static::getPathParts( $path ); 
			$handler = static::getHandler( $req->capture ); 

			$bundle = new Bundle( $_GET, $_POST, $req->params );
			$handler->performAction( $req->action, $bundle );

		} catch( Exception $e ) {

			$response = new AjaxResponse();
			$response->fail()
				->setMessage( $e->getMessage() );
			
			while( $e = $e->getPrevious() ) {
				$response->addDebug( $e->getMessage() );
			}

			$response->send();
			exit;
		}
	}

	/**
	 * Extracts routing information from the path
	 *
	 * This function assumes that the first level folder of the path will dictate the handler.
	 * The second level folder of the path will dictate the handler.
	 * Remaining folder levels will be added to the bundle as parameters. i.e,
	 * /handler/action/param/param/,,,
	 *
	 * @static
	 * @internal
	 * @param $path String Path to extract parts from
	 * @return StdClass Routing information (props: capture, action, params)
	 */
	private static function getPathParts( $path ) {
		
		$path = trim( $path, ' /' );
		$path = explode( '/', $path );

		$data = new StdClass();
		$data->capture = array_shift( $path ) ?: null;
		$data->action  = array_shift( $path ) ?: null;
		$data->params  = $path;

		return $data;
	}

	/**
	 * Instantiates a named capture handler
	 *
	 * Taking the name of the parent folder, this function attempts to locate, configure
	 * and return a concrete instance of the Capture handler.
	 *
	 * @static
	 * @internal
	 * @throws Exception
	 * @param $capture String Name of the parent folder for capture
	 * @return Capture Capture handler
	 */
	private static function getHandler( $capture ) {

		$capture = strtolower( $capture );
		$path    = CAPT_DIR . DS . $capture;
		$file    = $path . DS . 'capture.php';
		
		if( empty($capture) )
			Throw new Exception( "No capture specified");
		if( !is_dir( $path ) )
			Throw new Exception( "No capture is configured for '{$capture}'");
		if( !file_exists( $file ) )
			Throw new Exception( "Unable to locate handler file for '{$capture}'");

		try {
			require_once( $file );
			$handler = new Handler();
		} catch( Exception $e ) {
			Throw new Exception("Unable to instantiate handler file for '{$capture}'", 0, $e);
		}

		$handler->setName( $capture );
		$handler->setHandlerLocation( $path );
		return $handler;
	}

}