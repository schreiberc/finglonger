<?php
/**
 * Class to manages finglonger users.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */
class User{
	
	private $resources;
	private $userResources;
	private $con;
	private $firstName;
	private $lastName;
	private $userName;
	private $userId;
	private $userTypeId;
	private $loggedIn;
	public $sessionTimeStamp;
	
	const SERVICE_FUNCITON_PREFIX = 'fl_'; //Don't change this user.  Things will break.
	const BASE_USER_TYPE = '1';  //Same as above this is important don't mess with it.
	const SUPER_USER_TYPE = '2';  //Same as above this is important don't mess with it.
	const PASSWORD_RECOVERY_EXPIRY_IN_DAYS = '1'; //Needs to be an integer number of days.
	
	/**
	 * Constructor
	 *
	 */
	public function __construct(){

		//Empty Constructor
		
	}
	
	/**
	 * Iniialize the user object
	 *
	 * @return Boolean true on success
	 * @return Boolean false on error
	 */
	public function init(){
		
		//Set a connection
		$this->con = jan_getConnection();
		
		if($this->con == false){
			$this->error = 'USER: '.__LINE__. ' Unable to connect to database.';
		}	
		
		//Initialize current login state of the user
		$this->loggedIn = false;
		$this->userTypeId = self::BASE_USER_TYPE;
		
		//Initialize messaging strings
		$this->error = '';
		$this->message = '';
		
		//Generate the resources available to this user
		$this->resources = $this->generateAccessibleResources();
		
		if($this->resources === false){
			$this->error.= ' USER '.__LINE__;
			return false;
		}
		
		//Generate list of the user class specific resources
		$userClassMethods = get_class_methods($this);
		$this->userResources = array();
		
		$sizeOfUserClassMethods = sizeof($userClassMethods);
		for($i = 0; $i < $sizeOfUserClassMethods; $i++){
			
			if(substr($userClassMethods[$i], 0, 3) == self::SERVICE_FUNCITON_PREFIX){
				$this->userResources[] = $userClassMethods[$i];
			}
		}

		//Initialize the current session timestamp
		$this->sessionTimeStamp = time();
		
		//Create an empty ship
		$this->ship = new Ship();
		
		//Initialize the ship
		if($this->ship->init() === false){
			$this->error = 'USER '.__LINE__.' : '.$this->ship->error;
			return false;
		}
		
		//Create an email delivery boy
		$this->emailDelivery = new EmailDelivery();
		
		//Initialzie email delivery
		if($this->emailDelivery->init() === false){
			$this->error = 'USER '.__LINE__.' : '.$this->emailDeliery->error;
			return false;
		}
		
		return true;
	}
	
	/**
	 * Re-initialize the user object
	 *
	 * @return Boolean true on success
	 * @return Boolean false on error
	 */
	public function re_init(){
		
		//Generate the resources this available to this user
		$this->resources = $this->generateAccessibleResources();
		
		if($this->resources === false){
			$this->error.= ' USER '.__LINE__. ' Unable to generate available user resources.';
			return false;
		}
		
		//Initialize the ship
		if($this->ship->init() === false){
			$this->error = 'USER '.__LINE__.' : '.$this->ship->error;
			return false;
		}
		
		//Generate list of the user class specific resources.  This probabaly has not changed but if a user
		//has decided to add functionality to the user class.
		$userClassMethods = get_class_methods($this);
		$this->userResources = array();
		
		$sizeOfUserClassMethods = sizeof($userClassMethods);
		for($i = 0; $i < $sizeOfUserClassMethods; $i++){
				
			if(substr($userClassMethods[$i], 0, 3) == self::SERVICE_FUNCITON_PREFIX){
				$this->userResources[] = $userClassMethods[$i];
			}
		}
	}
	
	/**
	 * Determine if a requested resource is can be executed by the User class
	 *
	 * @param String $_requestedResource
	 * @return Boolean true on success
	 */
	public function isUserResource($_requestedResource){
		
		return in_array(self::SERVICE_FUNCITON_PREFIX.$_requestedResource, $this->userResources);
		
	}
	
	public function go($_requestedResource){
		
		//Set a connection
		$this->con = jan_getConnection();
		
		if($this->con == false){
			$this->error = 'USER: '.__LINE__. ' Unable to connect to database.';
		}
		
		//Reset messaging
		$this->message = '';
		$this->error = '';
		
		return call_user_func(array($this, self::SERVICE_FUNCITON_PREFIX.$_requestedResource));
	}
	
	public function getAllResources(){
		
		$formattedResources = array();
		
		$sizeOfResources = sizeof($this->userResources);
		for($i = 0; $i < $sizeOfResources; $i++){
			$formattedResources[] = str_replace('_', '-', str_replace(self::SERVICE_FUNCITON_PREFIX, '', $this->userResources[$i]));
		}
		
		return $formattedResources;
		
	}
		
	public function fl_fl_users(){
		
		//Route Post, Get and Delete through this function to use instead of the users table.
		$requestMethod = $_SERVER['REQUEST_METHOD'];
		
		switch ($requestMethod){
			
			case 'GET':
						
				$userData = $this->ship->retrieveData();
				
				if($userData === false){
					$this->error = $this->ship->error;
					return false;
				}
								
				//Determine if we have one or multiple
				unset($userData['password']);
				unset($userData['email_confirmation_token']);
				unset($userData['email_confirmation_required']);
				unset($userData['email_confirmed']);
				unset($userData['password']);
				
				//Remove some of the fields that the client doesn't need
				$sizeOfUserData = sizeof($userData);
				for($i = 0; $i < $sizeOfUserData; $i++){
					unset($userData[$i]['password']);
					unset($userData[$i]['email_confirmation_token']);
					unset($userData[$i]['email_confirmation_required']);
					unset($userData[$i]['email_confirmed']);
					unset($userData[$i]['password']);
				}
				
				return $userData;
			
			break;
			
			case 'POST':
				
				//Here we will seperate out if we are updating or creating new record.
				//Determined by the presense of an id within the URI
				$resourceRequest = jan_generateResourceRequest();
				
				$postData = json_decode($resourceRequest->postData, true);
				
				$user_id = (isset($postData['user_id']) === true) ? 'USER ID' : 'NO USER ID';
				
				if($user_id == 'NO USER ID'){
					return $this->postUser();
				}
				
				return $this->putUser();
				
			break;
			
			case 'DELETE':
				
				$result = $this->ship->deleteData();
				
				if($result === false){
					$this->error = $this->ship->error;
				}
				
				return $result;
				
			break;
			
			default:
				
				$this->error = 'USER: '.__LINE__. ' Method not recognized.';
				
			break;
		}
		
	}
	
	public function postUser(){
				
		//Generate the resource request
		$resourceRequest = jan_generateResourceRequest();
		 
		if($resourceRequest === false){
			$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
			return false;
		}
		
		//Retreive post data
		$data = $resourceRequest->postData;
		
		//Check to see if we have a username, email and password within the JSON string.
		$userData = json_decode($data, true);
		
		if(!isset($userData['user_name']) || !isset($userData['password']) || !isset($userData['email'])){
			$this->error = 'USER: '.__LINE__.' A username, email and password are required to create a new user.';
			return false;
		}
		
		$uniqueDataErrorMessage = '';
		
		//The user name and email must be unique.  Let's return this as a single error
		if($this->isUserFieldUnique('user_name',$userData['user_name']) === false){
			$uniqueDataErrorMessage .= ' Username already exists.';
		}
		
		//The email must be unique
		if($this->isUserFieldUnique('email',$userData['email']) === false){
			$uniqueDataErrorMessage .= ' Email already exists.';
		}
		
		if(strlen($uniqueDataErrorMessage) > 0){
			$this->error = 'USER: '.__LINE__ . $uniqueDataErrorMessage;
			return false;
		}
		
		$userName = $userData['user_name'];
		$userTypeId = $userData['user_type_id'];
		$password = $userData['password'];
		$email = $userData['email'];
		$emailConfirmationRequired = false;
		
		//First and Last names are optional, see if we were passed those elments
		$firstName = (isset($userData['first_name'])) ? $userData['first_name'] : '';
		$lastName = (isset($userData['last_name'])) ? $userData['last_name'] : '';
		
		//Hash the password
		$hashedPassword = create_hash($userData['password']);
		
		//Create the new user
		$insertUserSQL = "INSERT INTO fl_users (user_name, email, password, first_name, last_name, email_confirmation_required, user_type_id) VALUES (?,?,?,?,?,?, ?)";
		$userCreatedData = jan_stmtQuery2($insertUserSQL, array($userName, $email, $hashedPassword, $firstName, $lastName, $emailConfirmationRequired, $userTypeId));
		 
		if($userCreatedData  === false){
			$this->error = 'USER: '.__LINE__. ' An error has occured attempting to create the user.';
			return false;
		}
				
		//Check to see if email verification is required.
		if(isset($userCreatedData['requires_confirmation']) && $userCreatedData['requires_confirmation'] === true){
				
			//Send the confirmation email
			$this->fl_send_email_confirm_email($userCreatedData['data']);
			
			//Set email confirmation requirement to true
			$emailConfirmationRequired = true;
		}		
		
		//Pull the newly created primary key and add pass it back along with passed in data.
		return array('user_id'=>$userCreatedData['data'], 'first_name'=>$firstName, 'last_name'=>$lastName, 'email'=>$email, 'email_confirmation_required' => $emailConfirmationRequired);
		 
	}
	
	private function putUser(){
		
		//Generate the resource request
		$resourceRequest = jan_generateResourceRequest();
			
		if($resourceRequest === false){
			$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
			return false;
		}
		
		//Retreive post data
		$userData = json_decode($resourceRequest->postData ,true);

		//We need a user id, email, 
		if(isset($userData['user_id']) === false){
			$this->error = 'USER: '.__LINE__. ' Unable to complete request no user id found in post data.';
			return false;
		}
		
		$uniqueDataErrorMessage = '';
		
		//If the user name has been changed check to see if it is still unique
		if($userData['user_name'] != $this->userName){
			if($this->isUserFieldUnique('user_name',$userData['user_name']) === false){
				$uniqueDataErrorMessage .= ' Username already exists.'. $this->userName;
			}
		}
		
		//The email must be unique
		if($userData['email'] != $this->email){
			if($this->isUserFieldUnique('email',$userData['email']) === false){
				$uniqueDataErrorMessage .= ' Email already exists.'.$this->email;
			}
		}
		
		if(strlen($uniqueDataErrorMessage) > 0){
			$this->error = 'USER: '.__LINE__ . $uniqueDataErrorMessage;
			return false;
		}
		
		$updateUserSQL = 'UPDATE fl_users set';
		$updateUserSQLData = array();
		
		//Check what values we have to determine what is being updated
		if(isset($userData['first_name'])){
			$firstName = $userData['first_name'];
			array_push($updateUserSQLData, $firstName);
			$updateUserSQL .= ' first_name = ?,';
		}
		
		if(isset($userData['last_name'])){
			$lastName = $userData['last_name'];
			array_push($updateUserSQLData, $lastName);
			$updateUserSQL .= ' last_name = ?,';
		}
		
		if(isset($userData['user_name'])){
			$userName = $userData['user_name'];
			array_push($updateUserSQLData, $userName);
			$updateUserSQL .= ' user_name = ?,';
		}
		
		if(isset($userData['email'])){
			$email = $userData['email'];
			array_push($updateUserSQLData, $email);
			$updateUserSQL .= ' email = ?,';
		}
		
		if(isset($userData['user_type_id'])){
			$userTypeId = $userData['user_type_id'];
			array_push($updateUserSQLData, $userTypeId);
			$updateUserSQL .= ' user_type_id = ?,';
		}
			
		if(isset($userData['password'])  && strlen($userData['password']) >  0){
			$password = $userData['password'];
			$updateUserSQL .= ' password = ?,';
			
			//Hash the password
			$hashedPassword = create_hash($password);
			array_push($updateUserSQLData, $hashedPassword);
		}
		
		$userId = $userData['user_id'];
		array_push($updateUserSQLData, $userId);
		
		$updateUserSQL = rtrim($updateUserSQL, ',');
		$updateUserSQL .= ' WHERE user_id = ?';
		
		//Update the user
		$userUpdatedData = jan_stmtQuery2($updateUserSQL, $updateUserSQLData);
			
		if($userUpdatedData  === false){
			$this->error = 'USER: '.__LINE__. ' An error has occured attempting to update the user.';
			return false;
		}
		
		return (array('user_name' => $username, 'first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'user_type_id' => $userTypeId));
		
	}
	
	public function fl_fl_user_types(){
		
		//Route Post, Get and Delete through this function to use instead of the users table.
		$requestMethod = $_SERVER['REQUEST_METHOD'];
		
		switch ($requestMethod){
				
			case 'GET':
								
				$resourceRequest = jan_generateResourceRequest();
				$resourceRequest->fullResource = 'fl-user-types';
				$resourceRequest->parentResource = 'fl_user_types';
		
				$userTypeData = $this->ship->retrieveData(null,$resourceRequest);
		
				if($userTypeData === false){
					$this->error = $this->ship->error;
					return false;
				}
				
				
				return $userTypeData;
					
				break;
					
			case 'POST':
				
				$resourceRequest = jan_generateResourceRequest();
				$postData = json_decode($resourceRequest->postData, true);
				
				
				if(isset($postData->postData['user_type_id']) === true){
					return $this->ship->patchData();
				}
				
				//Make sure we have some posted data
				if(!isset($resourceRequest->postData)){
					$this->error  = 'USER '.__LINE__.' : No data was posted to resource';
					return false;
				}
				
				$postUserTypeResult = $this->ship->patchData();				
				
				if(isset($postUserTypeResult['user_type_id']) === false){
					$this->error  = 'USER '.__LINE__.' : An error occured posting the user type. '. $this->ship->error;;
					return false; 
				}
				
				//Get the new user type to be used later.
				$user_type_id = $postUserTypeResult['user_type_id'];
				
				//On a post we need to create access records for the new user type			
				$resourceSQL = 'SELECT resource_id FROM fl_resources';
				$resourceResult = jan_stmtQuery2($resourceSQL);
				
				if($resourceResult  === false){
					$this->error = 'USER '.__LINE__.' : Unable to pull resources for default access';
					return false;
				}
				
				$resourceData = (isset($resourceResult['data'])) ? $resourceResult['data'] : array();
				$sizeOfResourceData = sizeof($resourceData);
				
				$userTypeAccessSQL = 'INSERT into fl_user_type_resource_access (user_type_id, resource_id) VALUES ';

				for($i = 0; $i < $sizeOfResourceData; $i++){
					$userTypeAccessSQL .= '('.$user_type_id.','.$resourceData [$i]['resource_id'].'),';
				}
				
				$userTypeAccessSQL = rtrim($userTypeAccessSQL, ',');
	
				/*$stmt = $this->con->prepare($userTypeAccessSQL);
				 
				//Ensure prepare succeeded
				if ($stmt === false) {
					$this->error = 'USER: '.__LINE__.' '.$this->con->error;
					return false;
				}*/
				
				$userTypeAccessResult = jan_stmtQuery2($userTypeAccessSQL);
				
				if($userTypeAccessResult == false){
					$this->error = 'USER: '.__LINE__.' ';
					return false;
				}
							
				if(isset($userTypeAccessResult['affected_rows']) == false || $userTypeAccessResult['affected_rows'] != $sizeOfResourceData){
					$this->error = 'USER: '.__LINE__.' An error occured creating default resource access records';
					return false;
				}
				
				//We need to update any access levels to resources associated with the open resource user type
				$openTypeResourceAccessSQL = 'UPDATE fl_user_type_resource_access as a0
											 	LEFT JOIN (SELECT r1.resource_id as rid1, r1.resource_id as rid2, get_allowed, post_allowed, delete_allowed FROM fl_resources as r1 RIGHT JOIN fl_user_type_resource_access as r2 on r1.resource_id = r2.resource_id WHERE r2.user_type_id = '.self::BASE_USER_TYPE.') as a1 
												on a0.resource_id = rid2
												SET a0.get_allowed = a1.get_allowed, a0.post_allowed = a1.post_allowed, a0.delete_allowed = a1.delete_allowed
												WHERE a0.user_type_id = '.$user_type_id;
				
				$openTypeResourceAccessResult = jan_stmtQuery2($openTypeResourceAccessSQL);
				
				if($openTypeResourceAccessResult == false){
					$this->error = 'USER: '.__LINE__.' '.$openTypeResourceAccessSQL;
					return false;
				}
				
				if(isset($openTypeResourceAccessResult['affected_rows']) == false){
					$this->error = 'USER: '.__LINE__.' An error occured creating default open resource access records';
					return false;
				}
				
				return $postUserTypeResult;
				
				break;
					
			case 'DELETE':
		
				$result = $this->ship->deleteData();
		
				if($result === false){
					$this->error = $this->ship->error;
				}
		
				return $result;
		
				break;
					
			default:
		
				$this->error = 'USER: '.__LINE__. ' Method not recognized.';
		
				break;
		}
		
	}
	
	public function fl_unique_email(){
		
		return $this->isUserFieldUnique('email');
		
	}
	
	public function fl_unique_user_name(){
		
		return $this->isUserFieldUnique('user_name');
		
	}
	
	private function isUserFieldUnique($_field_name, $_value){
		
		$fieldUniqueSQL = 'SELECT * from fl_users WHERE '.$_field_name.' = ?';
		
		$fieldlUniqueData = jan_stmtQuery2($fieldUniqueSQL, array($_value));
		
		if($fieldlUniqueData === false){
			$this->error = 'USER: '.__LINE__. ' An error has occured attempting to query the database.';			
			return false;
		}
			
		if($fieldlUniqueData['affected_rows'] > 0){
			$this->error = 'USER: '.__LINE__. ' '.strtoupper($_field_name).' '.$_value.' already exists.';
			return false;
		}
		
		return true;
		
	}
		
	 /**
     * Attempts to login a user.
     *
     * @param String JSON string with username and password for login attempt.
     * @return Array containing user information on success.
     * @return Boolean false on error 
     */
    public function fl_login(){
    	
    	//Generate the resource request
    	$resourceRequest = jan_generateResourceRequest();
    	
    	if($resourceRequest === false){
    		$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
    		return false;
    	}
    	
    	//Retreive post data
    	$data = $resourceRequest->postData; 
    	
    	//Check to see if we have a username and password within the JSON string.
        $userData = json_decode($data, true);
    
    	if(!isset($userData['user_name']) || !isset($userData['password'])){
   			$this->error ='USER: '.__LINE__.' Username and Password not provided';
    		return false;
    	}
   
    	//Validate the user name by pulling the password hash to validate.
    	$sql = "SELECT first_name, last_name, user_type_id, user_name, user_id, email_confirmation_required, email_confirmed, password, email FROM fl_users WHERE user_name = ?";
   	
    	$stmt = $this->con->prepare($sql);
    	
    	//Ensure prepare succeeded
    	if ($stmt===false) {
    		$this->error = 'USER LOGIN: '.__LINE__.' '.$this->con->error;
    		return false;
    	}
    	
    	$userName = $userData['user_name'];
    	$password = $userData['password'];
    	
    	$stmt->bind_param('s', $userName);
    	
    	if($stmt===false){
    		$this->error = 'USER LOGIN: '.__LINE__.' '.$this->con->error;
    		return false;
    	}
    	
    	$stmt->execute();
    	
    	if($stmt===false){	
    		$this->error = 'USER LOGIN: '.__LINE__.' '.$this->con->error;
    		return false;
    	}
    	
    	$stmt->store_result();
    	$rowNumber = $stmt->num_rows;
    	
    	if($rowNumber > 1){
    		$this->error = 'USER: '.__LINE__.' Multiple user accounts tied to these credentials';
    		return false;
    	}
    	
    	if($rowNumber < 1){
    		$this->error = 'USER: '.__LINE__.' No user with given username found.';
    		return false;
    	}
    	    	
    	$stmt->bind_result($firstName, $lastName, $userTypeId, $userName, $userId, $emailConfirmationRequired, $emailConfirmed, $hashedPassword, $email);
    	$stmt->fetch();
    	
    	//Validate the password
    	if(validate_password($password, $hashedPassword) === false){
    		$this->error = 'USER: '.__LINE__." The password provided is incorrect. ".$password.' '.md5($password).' '.$hashedPassword;
    		return false;
    	}
    	
    	
    	if($emailConfirmationRequired === true && $emailConfirmed === false){
    		//Login fails email not yet confirmed
    		$this->error = 'USER: '.__LINE__.' Email confirmation required.';
    		return false;
    	}
    	
    	//Store the information in the user object
    	$this->firstName = $firstName;
    	$this->lastName = $lastName;
    	$this->userTypeId = $userTypeId;
    	$this->userName = $userName;
    	$this->userId = $userId;
    	$this->email = $email;
		$this->loggedIn = true;
		
    	//Reset available resources - Use a temporary variable in case of failure.
    	$newResources = $this->generateAccessibleResources();
    	
    	if($newResources === false){
    		$this->error .= ' USER: '.__LINE__.' Call to create user resources failed.';
    		return false;
    	}
    	 
    	$this->resources = $newResources;
    	
    	return array('first_name'=>$firstName, 'last_name'=>$lastName, 'user_name'=>$userName, 'user_type_id'=>$userTypeId, 'user_id' => $this->userId);
    }
	
    public function fl_logout(){
   
    	//Reset accessible resources
    	$this->resources = $this->generateAccessibleResources();
    	
    	//Check to see if their is currently a user logged in
    	if(isset($this->loggedIn) === false){
    		return array('user_name'=>null);
    	}
    	
    	//Store the username to return to client
    	$userName = $this->userName; 
    	   	
    	if(session_destroy() === false){
    		$this->error = 'USER '.__LINE__.': Could not destroy session.  Logout failed';
    		return false;
    	}
    	
    	return array('user_name'=>$userName);
    	
    }
    
    public function fl_update_user(){

    	
    }
    
    /**
     * Processes a user request to generate a new password. 
     * Log the request in the database.
     * Send recovery email to user.
     *
     * @return TRUE on success
     * @return FAlse on failure
     */
    public function fl_request_change_password(){
    	
    	//Generate the resource request
    	$resourceRequest = jan_generateResourceRequest();
    	
    	if($resourceRequest === false){
    		$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
    		return false;
    	}
    	
    	//Retreive post data
    	$data = $resourceRequest->postData;
			
    	if(is_null($data)){
    		$this->error = 'USER: '.__LINE__. ' Posted data was not found or malformed.';
    		return false;
    	}
    	
    	//Check to see that we have been given an email
        $data = json_decode($data, true);        
    	
    	if(!isset($data['email'])){
    		$this->error = 'USER: '.__LINE__. ' No email has been provided.';
    		return false;
    	}
    	
    	$userEmail = $data['email'];
    	
    	//Check the DB to see if user with this email exists
    	$userExistsSQL = "SELECT user_name, first_name, last_name, user_id FROM fl_users where email = ?";
    	
    	$userExistsData = jan_stmtQuery2($userExistsSQL, array($userEmail));
    	
    	if($userExistsData === false){
    		$this->error = 'USER: '.__LINE__. ' An error has occured attempting to query the database.';
    		return false;
    	}
    	
    	if($userExistsData['affected_rows'] == 0){
    		$this->error = 'USER: '.__LINE__. ' No user was found with given email.';
    		return false;
    	}
    	
    	if($userExistsData['affected_rows'] > 1){
    		$this->error = 'USER: '.__LINE__. ' Multiple users were found with given email.';
    		return false;
    	}
    		
    	$userId = $userExistsData['data'][0]['user_id'];
    	$firstName = $userExistsData['data'][0]['first_name'];
    	$lastName = $userExistsData['data'][0]['last_name'];
    	
    	//Generate a random token
    	$token = substr(md5($userEmail),0,9).jan_generateRandomString(15);
    	    	
    	$insertpasswordResetLogSQL = 'INSERT INTO fl_user_password_reset_log (user_id, email, token) VALUES (?,?,?)';
    	$insertpasswordResetLogResult = jan_stmtQuery2($insertpasswordResetLogSQL, array($userId, $userEmail, $token));
    	
    	if($insertpasswordResetLogResult === false){
    		$this->error = 'USER: '.__LINE__. ' Unable to update user password reset log.';
    		return false;
    	}
    	
    	//Send the confirm email
    	$emailData = $this->getEmailByName('Request New Password');
    	 
    	if($emailData === false){
    		return false;
    	}
    	
    	$emailData = $emailData[0];
    	 
    	//Send the email
    	$emailSubject = $emailData['email_subject'];
    	$emailBodyHtml = $emailData['email_body_html'];
    	$emailBodyText = $emailData['email_body_text'];
    	
    	//Check to see if a link to resolve to was passed along with the request
    	if(isset($data['reset_url'])){
    		$link = $data['reset_url'];
    		
    		if(substr($link, -1) != '/'){
    			$link .= '/';
    		}
    		
    		$link .= $token;
    		
    	}else{
	    	//Generate the link.
	    	$explodedDIR = explode('/', __DIR__);
	    	$directoryName = $explodedDIR[array_search('finglonger', $explodedDIR) - 1];
	    	$explodedRequestURI = explode('/', $_SERVER['REQUEST_URI']);
	    	$indexOfDirectory = array_search($directoryName, $explodedRequestURI);
	    	array_splice($explodedRequestURI, $indexOfDirectory);
	    	$link = implode('/', $explodedRequestURI);
	    	$link = 'http://'.$_SERVER['HTTP_HOST'].$link.'/mom/#/reset-password/'.$token;
    	}
    	
    	$emailBodyHtml = str_replace('[Link]', $link ,$emailBodyHtml);
    	$emailBodyHtml = str_replace('[First Name]', $firstName ,$emailBodyHtml);
    	$fullEmail = $firstName." ".$lastName." <".$userEmail.">";
    	 
    	$sendEmailResult = $this->emailDelivery->sendEmail($fullEmail, null,$emailSubject,$emailBodyHtml,$emailBodyText,null);
		    	
    	if($sendEmailResult === false){
    		$this->error = 'USER '.__LINE__.': Password reset email could not be set. : '.$this->emailDelivery->error;
    		return false;
    	}
    	
    	$this->success = 'USER '.__LINE__.': Good News Everyone!  Password reset email sent successfully';
    	return true;
    }
    
 	/**
     * Updates a user password.
     * Log the request usage in the database.
     * Send recovery email to user.
     *
     * @return TRUE on success
     * @return FAlSE on failure
     */
    public function fl_change_password(){
    	
    	//Generate the resource request
    	$resourceRequest = jan_generateResourceRequest();
    	 
    	if($resourceRequest === false){
    		$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
    		return false;
    	}
    	 
    	//Retreive post data
    	$data = $resourceRequest->postData;
    	
    	$data = json_decode($data, true);
    	
    	if(is_null($data)){
    		$this->error = 'USER: '.__LINE__. ' Posted data was not found or malformed.';
    		return false;
    	}
    	
    	//Make sure we have the correct informaiton
    	if(!(isset($data['token']) && isset($data['password']))){
    		$this->error = 'USER: '.__LINE__. ' Posted data did not contain all required information to update password.';
    		return false;
    	}
    
    	$token = $data['token'];
    	$password = create_hash($data['password']);
    	
    	//Check the DB to see if there is a valid log entry for this request
    	//TODO: add the email in here.
    	
    	$resetLogExistsSQL = 'SELECT user_password_reset_id, user_id, email FROM fl_user_password_reset_log WHERE date_created + INTERVAL '.self::PASSWORD_RECOVERY_EXPIRY_IN_DAYS.' DAY > now() AND date_used IS NULL AND TOKEN = ? ORDER BY date_created DESC';
		
    	$resetLogExistsData = jan_stmtQuery2($resetLogExistsSQL, array($token));
    	
    	if($resetLogExistsData === false){
    		$this->error = 'USER: '.__LINE__. ' An error occured querying the user record.';
    		return false;
    	}
    	 
    	if(sizeof($resetLogExistsData['data']) == 0){
    		$this->error = 'USER: '.__LINE__. ' No user matched by posted data.';
    		return false;
    	}
    	 
    	if(sizeof($resetLogExistsData['data']) > 1){
    		$this->error = 'USER: '.__LINE__. ' An unknown error has occured multiple user records matched by posted data.';
    		return false;
    	}
    	
    	$userId = $resetLogExistsData['data'][0]['user_id'];
    	$userPasswordResetId = $resetLogExistsData['data'][0]['user_password_reset_id'];
    	$userEmail = $resetLogExistsData['data'][0]['email'];
    	
    	//Update the reset log record
    	$updateResetLogSQL = 'UPDATE fl_user_password_reset_log SET date_used = now() WHERE user_password_reset_id = '.$userPasswordResetId;
    	$updateResetLogResult = jan_stmtQuery2($updateResetLogSQL );
    	
    	if($updateResetLogResult === false){
    		$this->error = 'USER: '.__LINE__. ' Unable to update password reset log.';
    		return false;
    	}
    	
    	//Update the user record
    	$updateUserSQL = "UPDATE fl_users set password = '".$password."' WHERE user_id = ".$userId;
    	$updateUserResult = jan_stmtQuery2($updateUserSQL);
    	 
    	if($updateUserResult === false){
    		$this->error = 'USER: '.__LINE__. ' Unable to update password reset log.';
    		return false;
    	}
    	
    	//Send success email
    	//Send the confirm email
    	$emailData = $this->getEmailByName('Password Reset Success');
    	
    	if($emailData === false){
    		return false;
    	}
    	 
    	$emailData = $emailData[0];
    	
    	//Send the email
    	$emailSubject = $emailData['email_subject'];
    	$emailBodyHtml = $emailData['email_body_html'];
    	
    	$sendEmailResult = $this->emailDelivery->sendEmail($userEmail, null,$emailSubject,$emailBodyHtml,null,null);
    	
    	if($sendEmailResult === false){
    		$this->error = 'USER '.__LINE__.': Password reset email could not be sent. : '.$this->emailDelivery->error;;
    		return false;
    	}
    	 
    	$this->success = 'USER '.__LINE__.': Good News Everyone!  Password reset success email sent successfully';
    	return true;
    	
    }
    
    private function fl_send_email_confirm_email($_user_id = null){
    	
    	//If we were not passed a user id check post data
    	if(is_null($_user_id)){
    		//Generate the resource request
    		$resourceRequest = jan_generateResourceRequest();
    	 
    		if($resourceRequest === false){
    			$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
    			return false;
    		}
    	 
    		//Retreive post data
    		$data = $resourceRequest->postData;
    	
    		$data = json_decode($data, true);
    	
    		if(is_null($data)){
    			$this->error = 'USER: '.__LINE__. ' Posted data was not found or malformed.';
    			return false;
    		}
    		
    		//Make sure we have a user id
    		if(isset($data['user_id']) === false){
    			$this->error = 'USER: '.__LINE__. ' No user id was provided.';
    		}
    		
    		$_user_id = $data['user_id'];
    	}
    	
    	$userId = $_user_id;
    	 	
    	$userDataSQL = 'SELECT first_name, last_name, email from fl_users WHERE user_id = ? AND email_confirmed = false';	
    	$userDataResult = jan_stmtQuery2($userDataSQL, array($userId));
    	
    	if($userDataResult === false){
    		$this->error = 'USER: '.__LINE__. ' A database error occured while retrieving user data.';
    		return false;
    	}
    	
    	if(sizeof($userDataResult) === 0){
    		$this->error = 'USER: '.__LINE__. ' No un-validated user record could be found.';
    		return false;
    	}
    	    	
    	//TODO don't make it an array of array's when only one object.
    	$userDataResult = $userDataResult['data'][0];
    	
    	//Store name and email data to be used in the confirmation email later.
    	$email = $userDataResult['email'];
    	$firstName = $userDataResult['first_name'];
    	$lastName = $userDataResult['last_name'];
    	
    	$emailData = $this->getEmailByName('User Validation');
    	
    	if($emailData === false){
    		return false;
    	}
    	
    	$emailData = $emailData[0];//TODO Zoidberg
    	
    	//Send the email
    	$emailSubject = $emailData['email_subject'];
    	$emailBodyHtml = $emailData['email_body_html'];
    	
    	//Generate and post the validation token
    	$token = md5($email).jan_generateRandomString(15);
    	    	
     	$updateTokenSQL = 'UPDATE fl_users set email_confirmation_token = "'.$token.'" WHERE user_id = ?';
     	$updateTokenResult = jan_stmtQuery2($updateTokenSQL, array($userId));
    	
     	if($updateTokenResult === false){
     		$this->error = 'USER: '.__LINE__. ' A database error occured while generating update token.';
     		return false;
     	}
     	
    	//Generate the link.
    	$explodedDIR = explode('/', __DIR__);
    	$directoryName = $explodedDIR[array_search('finglonger', $explodedDIR) - 1];
    	$explodedRequestURI = explode('/', $_SERVER['REQUEST_URI']);
    	$indexOfDirectory = array_search($directoryName, $explodedRequestURI);
    	array_splice($explodedRequestURI, $indexOfDirectory);
    	$link = implode('/', $explodedRequestURI);
    	$link = 'http://'.$_SERVER['HTTP_HOST'].$link.'/setup/email-confirmation.php?token='.$token;
    	
    	//Swap out the placeholder tags
    	$emailBodyHtml = str_replace('[Link]', $link ,$emailBodyHtml);
    	$userEmail = $firstName." ".$lastName." <".$email.">";
    	
    	$sendEmailResult = $this->emailDelivery->sendEmail($userEmail, null,$emailSubject,$emailBodyHtml,null,null);
    	
    	if($sendEmailResult === false){
    		$this->error = 'USER '.__LINE__.': Validation to user could not be set. :'.$this->emailDelivery->error;
    		return false;
    	}

    	return true;
    	
    }
    
    public function fl_confirm_email(){
    	
    	//Generate the resource request
    	$resourceRequest = jan_generateResourceRequest();
    	
    	if($resourceRequest === false){
    		$this->error = 'USER: '.__LINE__. ' Could not generate the resource request.';
    		return false;
    	}
    	
    	//Retreive post data
    	$data = $resourceRequest->postData;
    	 
    	$data = json_decode($data, true);
    	 
    	if(is_null($data)){
    		$this->error = 'USER: '.__LINE__. ' Posted data was not found or malformed.';
    		return false;
    	}
    	
    	$confirmationToken = $data['email_confirmation_token'];
    	//$email = $data['email'];
    	
    	//Check to see if there is a match using email and validation token
    	$userDataSQL = 'SELECT email_confirmed from fl_users WHERE email_confirmation_token = ?';
    	
    	$userDataResult = jan_stmtQuery2($userDataSQL, array($confirmationToken));
    	
    	if($userDataResult === false){
    		$this->error = 'USER: '.__LINE__. ' An error occured querying the user record.';
    		return false;
    	}
    	
    	if(sizeof($userDataResult['data']) == 0){
    		$this->error = 'USER: '.__LINE__. ' No user matched by posted data.';
    		return false;
    	}
    	
    	if(sizeof($userDataResult['data']) > 1){
    		$this->error = 'USER: '.__LINE__. ' An unknown error has occured multiple user records matched by posted data.';
    		return false;
    	}
    	
    	//TODO - ZOIDBERG
    	if($userDataResult['data'][0]['email_confirmed'] == 'true'){
    		
    		//User already validated
    		$this->success = 'User exists but has already confirmed email';
			return array();
    	
    	}
    	
    	$validateUserSQL = "UPDATE fl_users SET email_confirmed = 'true' WHERE email_confirmation_token = ?";
    	$validateUserResult = jan_stmtQuery2($validateUserSQL, array($confirmationToken));
    	
   		 if($validateUserResult === false){
    		$this->error = 'USER: '.__LINE__. ' Unable to complete update of email confirmation.';
    		return false;
    	}
    	
    	return array('rows_affected'=>$validateUserResult['affected_rows']);
    
    }
    
    /**
     * Return user information for client processing.
     *
     * @return array : Restricted view of user information
     */
    public function getUser(){
    	
    	if($this->loggedIn === false){
    		$this->error = 'USER '.__LINE__.': No user is currently logged in.';
    		return false;
    	}
    		
    	return array(
    			'first_name' => $this->firstName,
    			'last_name' => $this->lastName,
    			'user_type_id' => $this->userTypeId,
    			'user_name' => $this->userName,
    			'user_id' => $this->userId
    	);
    		
    }
      
    /**
     * Attempts to  a user.
     *
     * @param Integer $_user_type_id.  Default const BASE_USER_TYPE.
     * @return array containing user information on success.
     * @return boolean false on error
     */
    public function generateAccessibleResources(){
    	
    	//If the current user is a super user.  Denoted by a parent_id with a value of 0.
    	//Then we don't need to check descendants the user has access to all resources.
    	$isSuperUser = false;
    	
    	//Empty array for user resources
    	$userResources = array();
    	
    	//Check to see if we are looking at a Super (MOM) user
    	if($this->userTypeId == self::SUPER_USER_TYPE){
    		$isSuperUser = true;
    	}
    	
    	//The current user is a Super user and has access to all resources.
    	if($isSuperUser == true){
				
    		$business = new Business();
    		$business->init();
    		$allBusinessResources = $business->getAllResources();
    		
    		$allShipResources = $this->ship->getAllResources();
    		$allUserResources = $this->getAllResources();
    		
    		$finglonger = new Finglonger();
    		$finglonger->generateClassMethods();
    		$allFinglongerResources = $finglonger->getAllResources();
    		
    		$allResources = array_merge($allBusinessResources, $allShipResources, $allUserResources, $allFinglongerResources);
    				
    		$sizeOfAllResources = sizeof($allResources);
    		for($i = 0; $i < $sizeOfAllResources; $i++){
    			$userResources[$allResources[$i]] = 'get,post,delete';	
    		}
    		
    	}else{ //If the current user is not Super user determine descendants and query for available resources.
    	
    		//Get all descendants of this user type
    		$userTypeDescendants = $this->getUserTypeDescendants($this->userTypeId);
    	
    		if($userTypeDescendants===false){
    			return false;
    		}
    	
    		$userTypeIdString = $this->userTypeId;
    		
    		//Merge the user type ids in to a String to insert into query.
    		if(sizeof($userTypeDescendants) > 0){
    			$userTypeIdString .= ','.implode(',',$userTypeDescendants);
    		}
    		
    		//Append the base user type
    		if(strlen($userTypeIdString) > 0){
    			$userTypeIdString .= ','.self::BASE_USER_TYPE;
    		}else{
    			$userTypeIdString .= self::BASE_USER_TYPE;
    		}
 
    		//Query all available resources for this user and descendants
    		$userResourcesSQL = 'SELECT resource_name, get_allowed, post_allowed, delete_allowed from fl_resources LEFT JOIN fl_user_type_resource_access ON fl_user_type_resource_access.resource_id = fl_resources.resource_id WHERE fl_user_type_resource_access.user_type_id in ('.$userTypeIdString.')';
    		    		
    		$userResourcesResult = $this->con->query($userResourcesSQL);

    		
	    	if($userResourcesResult === false){
	    		$this->error = 'USER '.__LINE__.': Failed to generate user resources. '.$this->con->error;
	    		return false;
	    	}
    		$method = '';
	    	while($userResourceRow = $userResourcesResult->fetch_array()){			    		
	    		
	    		if($isSuperUser === false){
	    			if($userResourceRow['get_allowed'] == 'true'){
	    				$method .='get,';

	    			}
	    		
	    			if($userResourceRow['post_allowed'] == 'true'){
	    				$method .='post,';
	    			}
	    		
	    			if($userResourceRow['delete_allowed'] == 'true'){
	    				$method .='delete,';
	    			}
	    			
	    			$method = rtrim($method, ',');
	    		}else if($isSuperUser == true){
	    			$method = 'get,post,delete';
	    		}else{
	    			//Skip the resource
	    			continue;
	    		}
	    		
	    		$userResources[$userResourceRow['resource_name']] = $method;
	    		
	    	}
    	
    	}
    	return $userResources;
    }
    
    /**
     * Find all the descendants of a given user type.
     *
     * @param Integer $_user_type_id User Type to find descendants.
     * @return Boolean false on error. 
     * @return Array all user_type_id descendants.
     */
 	private function getUserTypeDescendants($_user_type_id){
 		
 		$this->con = jan_getConnection();
 		
 		//Empty array of user type descendants
 		$userTypeDescendants = array();
 		
 		$userTypeDescendantSQL = 'SELECT user_type_id from fl_user_types WHERE parent_id = '.$_user_type_id;
 		$userTypeDescendantResult = $this->con->query($userTypeDescendantSQL);
 		
 		if($userTypeDescendantResult === false){
 			$this->error = 'USER '.__LINE__.' : '.$this->con->error;
 			return false;
 		}
 		
 		if($userTypeDescendantResult->num_rows == 0){
 			return $userTypeDescendants;
 		}
 		
 		while($userTypeDescendantRow = mysqli_fetch_array($userTypeDescendantResult)){
 			$userTypeDescendants =  array_merge($this->getUserTypeDescendants($userTypeDescendantRow['user_type_id']));
 			return array($userTypeDescendantRow['user_type_id']);
 		}
 		
 	}
    
 	/**
 	 * Determine if the user has access to a resource.
 	 *
 	 * @param String $_resource resource name
 	 * @param String $_method method being used (GET, POST, DELETE)
 	 * @return Boolean false if the user does not have access.
 	 */
 	public function doesHaveResourceAccess(){
 		
 		$resourceRequest = jan_generateResourceRequest();

		$this->generateAccessibleResources();
 		
 		//Find the resource
 		if(isset($this->resources[$resourceRequest->fullResource]) === false){
 			$this->error = 'USER '.__LINE__.': User is not permitted to access resource or the requested resource does not exist. '.$resourceRequest->fullResource;
 			return false;
 		}
 		
 		//If a user key exists check it against the current user
 		if(isset($resourceRequest->keys['user'])){
 			
 			//Make sure a user is logged in.
 			if($this->loggedIn === false){
 				$this->error = 'USER '.__LINE__.': Logged in user is required.';
 				return false;
 			}
 			
 			if($this->userId != $resourceRequest->keys['user']){
 				$this->error = 'USER '.__LINE__.': Attempting to accesss another users data.';
 				return false;
 			}
 			
 		}
 		
 		//Find the method

 		$availableMethods = explode(',', $this->resources[$resourceRequest->fullResource]);

 		if(in_array(strtolower($resourceRequest->method), $availableMethods) === false){
 			$this->error = 'USER '.__LINE__.': Requested method is not permitted. '.$temp;
 			return false;
 		}
 		
 		return true;
 		
 	}
 	
 	private function getEmailByName($_email_name){
 		
 		$emailDataSQL = 'SELECT * from fl_emails WHERE email_name = ?';
 		$emailDataResult = jan_stmtQuery2($emailDataSQL, array($_email_name));
 		
 		if($emailDataResult === false){
 			$this->error = 'USER '.__LINE__.': Email database query failed.';
 			return false;
 		}
 		
 		if(sizeof($emailDataResult) === 0){
 			$this->error = 'USER '.__LINE__.': Email data could not be found.';
 			return false;
 		}
 		
 		if(!(isset($emailDataResult['data']))){
 			$this->error = 'USER '.__LINE__.': Email data could not be found.';
 			return false;
 		}
 		
 		return $emailDataResult['data'];
 	
 	}
}
?>