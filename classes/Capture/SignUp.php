<?php

Class Capture_SignUp extends Capture {

	protected $_mailchimpApiKey = null;
	protected $_mailchimpListId = null;

	protected $_mailchimpSendWelcome = true;

	protected $_fields = array(
		'email'    => 'Email', 
		'fname'    => 'Text',
		'lname'    => 'Text',
	);

	protected $_required = array(
		'email',
	);

	protected $_adminTemplate = 'admin-signup.php';

	final public function __construct() {
		
		parent::__construct();

		if( empty($this->_mailchimpApiKey) )
			Throw new Exception("This Capture is not configured with a MailChimp API Key");
		if( empty($this->_mailchimpListId) )
			Throw new Exception("This Capture is not configured with a MailChimp List Id");
	}

	protected function _preCapture( array &$data ) {
		// Do nothing for this
	}

	protected function _postCapture( array $data ) {

		$mailchimp = new MailChimp( $this->_mailchimpApiKey );
		
		$mergeVars = array_change_key_case( $data, CASE_UPPER );
		unset( $mergeVars['EMAIL'] );

		$result = $mailchimp->lists->subscribe( 
			$this->_mailchimpListId, 
			array(
				'email'    => $data['email'],
			),
			$mergeVars,
			'html',
			false,
			false,
			false,
			$this->_mailchimpSendWelcome
		); 

	}

	// protected function action_test() {

	// 	$mailchimp = new MailChimp( $this->_mailchimpApiKey ); 

	// 	$data = array(
	// 		'EMAIL' => 'wi.ll.perring@gmail.com',
	// 		'NAME'  => 'Test Name',
	// 		'EXTRA' => 'Extra Field',
	// 		'POSITION' => 'Test Position',
	// 	);

	// 	echo "<pre>";
	// 	#var_dump( $mailchimp->lists->memberInfo( $this->_mailchimpListId, array(array('email'=>'will.perring@gmail.com') )) );
	// 	$result = $mailchimp->lists->subscribe( 
	// 		$this->_mailchimpListId, 
	// 		array(
	// 			'email'    => $data['EMAIL'],
	// 		),
	// 		$data,
	// 		'html',
	// 		false,
	// 		false,
	// 		false,
	// 		$this->_mailchimpSendWelcome
	// 	); 

	// 	var_dump(
	// 		$result 
	// 	);

	// }

}


