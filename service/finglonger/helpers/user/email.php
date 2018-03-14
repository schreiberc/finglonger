<?php
use Mailgun\Mailgun;

/**
 * Class to manage email functionality.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

class EmailDelivery{
	
	const DEFAULT_SYSTEM_EMAIL_FIRST_NAME = 'Hubert';
	const DEFAULT_SYSTEM_EMAIL_LAST_NAME = 'Jay';
	const DEFAULT_SYSTEM_EMAIL = 'whatif@finglonger.io';
	
	const DEFAULT_MAIL_FUNCTION = 'sendPHPEmail';
	
	private $mailGunData;
	private $systemReturnEmail;
	
	/**
	 * Constructor
	 *
	 */
	public function __construct(){
		
		//Empty Constructor
		
	}
	
	/**
	 * Initialize the object
	 *
	 * @return void
	 */
	public function init(){
						
		//Set email send method
		$this->setEmailSendMethod();
		
		//Set system return email
		$this->setSystemReturnEmail();

		$this->error = '';
		
		$this->message = '';
		
		return true;
		
	}
	
	/**
	 * Set the system return email.
	 *
	 * @return void
	 */
	public function setSystemReturnEmail(){
		
		$systemReturnEmailSQL = 'SELECT meta_name, meta_value FROM fl_setting_meta LEFT JOIN fl_settings
							ON fl_setting_meta.setting_id = fl_settings.setting_id
							WHERE fl_settings.setting_name = "System Contact"';
		
		$systemReturnEmailResult = jan_stmtQuery2($systemReturnEmailSQL);
		
		if($systemReturnEmailResult === false){
			
			$this->message = 'EmailDelivery: '.__LINE__.' Querying email settings fail hardcoded values will be used.';
			
			$this->systemReturnEmail['first_name'] = self::DEFAULT_SYSTEM_EMAIL_FIRST_NAME;
			$this->systemReturnEmail['last_name'] = self::DEFAULT_SYSTEM_EMAIL_LAST_NAME;
			$this->systemReturnEmail['email'] = self::DEFAULT_SYSTEM_EMAIL;
			
			
		}else{
			
			$returnEmailData = $systemReturnEmailResult['data'];
			$sizeOfEmailReturnData  = sizeof($returnEmailData);
			
			//Loop through the data set properly of meta data/meta value pairs.
			for($i = 0; $i < $sizeOfEmailReturnData; $i++){
				$this->systemReturnEmail[$returnEmailData[$i]['meta_name']] = $returnEmailData[$i]['meta_value'];
			}
						
			//Make sure we have the correct meta values
			if(!isset($this->systemReturnEmail['first_name'])){
				$this->systemReturnEmail['first_name'] = self::DEFAULT_SYSTEM_EMAIL_FIRST_NAME;
			}
			
			if(!isset($this->systemReturnEmail['last_name'])){
				$this->systemReturnEmail['last_name'] = self::DEFAULT_SYSTEM_EMAIL_LAST_NAME;
			}
			
			if(!isset($this->systemReturnEmail['email'])){
				$this->systemReturnEmail['email'] = self::DEFAULT_SYSTEM_EMAIL;
			}
			
		}
		
	}
	
	/**
	 * Set the system email method.  The default is stanard PHP mail or an integration with a third party service. In this case MailGun.
	 *
	 * @return void
	 */
	private function setEmailSendMethod(){
		
		//For now, we're only offereing Mailgun as an integration to use for email vs std php mail.
		//TODO Expand for more integrations if needed.
		//Check if this integration is present
		$mailGunAvailableSQL = 'SELECT meta_name, meta_value FROM fl_integration_meta LEFT JOIN fl_integrations
								ON fl_integration_meta.integration_id = fl_integrations.integration_id
								WHERE integration_name = "mailgun" AND enabled = "true"';
		
		$mailGunAvailableResult = jan_stmtQuery2($mailGunAvailableSQL);
			
		//Make sure we have some data
		if($mailGunAvailableResult != false && isset($mailGunAvailableResult['affected_rows']) && $mailGunAvailableResult['affected_rows'] == 2){
				
			$sizeOfMailGunAvailableResult = sizeof($mailGunAvailableResult['data']);
				
			for($i = 0; $i < $sizeOfMailGunAvailableResult; $i++){
				$this->mailGunData[$mailGunAvailableResult['data'][$i]['meta_name']] = $mailGunAvailableResult['data'][$i]['meta_value'];
			}
				
			$this->sendEmailMethod = 'sendMailGunEmail';
				
		}else{
				
			$this->sendEmailMethod = self::DEFAULT_MAIL_FUNCTION;
				
		}
		
	}
	
	/**
	 * Send an email
	 *
	 * @param String $_to
	 * @param String $_from
	 * @param String $_subject
	 * @param String $_html_body
	 * @param String $_text_body
	 * @param String $_cc
	 *   
	 * @return bool True on success
	 * @return bool False on failure
	 */
	public function sendEmail($_to, $_from = null, $_subject, $_html_body, $_text_body, $_cc = null){
				
		$emailMethod = $this->sendEmailMethod;
		
		//Use default system email if none is passed.
		if(is_null($_from)){
			$_from = $this->systemReturnEmail['first_name'].' '.$this->systemReturnEmail['last_name'].' <'.$this->systemReturnEmail['email'].'>';
		}
				
		$result = $this->$emailMethod($_to, $_from, $_subject, $_html_body, $_text_body, $_cc = null);
		
		if($result === false && $this->sendEmailMethod != self::DEFAULT_MAIL_FUNCTION){			
			$defaultEmailFunction = self::DEFAULT_MAIL_FUNCTION;
			$this->message = 'Mailgun failed PHP mail was attempted.';
			return $this->$defaultEmailFunction($_to, $_from, $_subject, $_html_body, $_text_body, $_cc = null);
		}
		
		return $result;
		
	}
	
	
	/**
	 * Send an email via PHP mail function
	 *
	 * @param String $_to
	 * @param String $_from
	 * @param String $_subject
	 * @param String $_html_body
	 * @param String $_text_body
	 * @param String $_cc
	 *   
	 * @return bool True on success
	 * @return bool False on failure
	 */
	private function sendPHPEmail($_to, $_from = null, $_subject, $_html_body, $_text_body, $_cc = null){
		
		//Construct the header
		//Construct and send the email
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		
		// Additional headers
		$headers[] = 'From: '.$_from;
		
		if(is_null($_cc) === false){
			$headers = "CC: ".$_cc;
		}
		
		return mail($_to, $_subject, $_html_body, implode("\r\n", $headers));	
		
	}
	
	/**
	 * Send an email via Mailgun
	 *
	 * @param String $_to
	 * @param String $_from
	 * @param String $_subject
	 * @param String $_html_body
	 * @param String $_text_body
	 * @param String $_cc
	 *
	 * @return bool True on success
	 * @return bool False on failure
	 */
	function sendMailGunEmail($_to, $_from = null, $_subject, $_html_body, $_text_body = null, $_cc = null){
		
		//One of html body or text body needs to not be null
		if(is_null($_html_body) && is_null($_text_body)){
			return false;
		}
		
		//Set up data to send to mail fun
		$sendData = array();
			
		$sendData['to'] = $_to;
		$sendData['from'] = $_from;
		$sendData['subject'] = $_subject;
		
		if(!is_null($_text_body)){
			$sendData['text'] =  $_text_body;
		}
		
		if(!is_null($_html_body)){
			$sendData['html'] = $_html_body;
		}
		
		if(!is_null($_cc)){
			$sendData['cc'] = $_cc;
		}
		
		try{
			
			$mgClient = new Mailgun($this->mailGunData['api_key']);
			$result = $mgClient->sendMessage($this->mailGunData['domain'], $sendData);
		
		}catch (Exception $ex){
			$this->error = $ex->getMessage();
			return false;
		}
		
		//Check for success
		if($result->http_response_code == 200){
			return true;
		}else{
			return false;
		}
	}
		
}

?>