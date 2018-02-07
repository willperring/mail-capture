<?php

Class Capture_ContactForm extends Capture {

	protected $_mandrillApiKey = null;

	protected $_senderName  = null;
	protected $_senderEmail = null;

	protected $_recipientName  = null;
	protected $_recipientEmail = null;

	protected $_fields = array(
		'name'    => 'Text',
		'email'   => 'Email',
		'phone'   => 'Text',
		'message' => 'LargeText',
	);

	protected $_required = array(
		'name',
		'email',
		'message',
	);

	protected $_adminTemplate = 'admin-contact.php';
	protected $_emailTemplate = 'email-contact.php';

	final public function __construct() {

		parent::__construct();

		if( empty($this->_mandrillApiKey) )
			Throw new Exception("This Capture is not configured with a Mandrill API Key");
		if( empty($this->_recipientName) || empty($this->_recipientEmail) )
			Throw new Exception("This Capture does not have valid recipient name and email information");
	}

	protected function _preCapture( array &$data ) {
		
	}

	protected function _postCapture( array $data ) {

		$mandrill = new Mandrill( $this->_mandrillApiKey );
		$template = new Template( $this->_emailTemplate );

		$body = $template->render( array(
			'sender' => $data,
			'server' => $_SERVER,
			'date'   => date('jS M Y \a\t H:i:s'),
		));

		$message = array(
			'text'    => $body,
			'subject' => "{$this->_name} Contact Form submission",
			'from_email' => $data['email'],
			'from_name'  => $data['name'],
			'to'      => array(
				array(
					'name'  => $this->_recipientName,
					'email' => $this->_recipientEmail,
					'type'  => 'to',
				),
			),
		);

		$result = $mandrill->messages->send( $message );
		
		if( $result[0]['status'] != 'sent' ) {
			$mandrillProblem = new Exception( $result[0]['reject_reason'] );
			Throw new Exception( "Unable to send submission email", 0, $mandrillProblem );
		}

	}
}