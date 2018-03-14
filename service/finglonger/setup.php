<?php
/**
 * Class to manage Finglonger install.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */
class Setup{
	
	private $con;
	private $ship;
	private $dbCreds;
	private $initialized;
	
	const SERVICE_FUNCITON_PREFIX = 'fl_';
	
	/**
	 * Empty Constructor
	 */
	public function __construct(){
		
	    $this->initialized = false;
	    
	}
	
	/**
	 * Initialize the Setup object.
	 * 
	 * @return void
	 */
	public function init(){
		
		//Initialize messaging strings
		$this->error = '';
		$this->message = '';
		
		$this->initialized = true;
		
		$this->dbCreds = array('hostname'=>'','user_name'=>'', 'password'=>'', 'db_name'=>'');
		
		return true;
	}
	
	/**
	 * Return boolean if Finglonger has been initialized or not.
	 *
	 * @return Boolean 
	 */
	public function isInitialized(){
	    
	    return $this->initialized;
	    
	}
	
	
	/**
	 * Execute a setup resource
	 *
	 * @param String $_requestedResource location of business logic files
	 * @return Boolean false on error
	 */
	public function go($_requestedResource){

		//Reset messaging
		$this->success = '';
		$this->error = '';
				
		return call_user_func(array($this, self::SERVICE_FUNCITON_PREFIX.$_requestedResource));
	}
	
	 /** 
	 * Determine if a requested resource is can be executed by the Setup class
	 *
	 * @param String $_requestedResource
	 * @return Boolean true on success
	 */
	public static function isSetupResource($_requestedResource){
		
		$setupClassMethods = get_class_methods('Setup');
		$setupResources = array();
		
		$sizeOfSetupClassMethods = sizeof($setupClassMethods);
		for($i = 0; $i < $sizeOfSetupClassMethods; $i++){
		
			if(substr($setupClassMethods[$i], 0, 3) == self::SERVICE_FUNCITON_PREFIX){
				$setupResources[] = $setupClassMethods[$i];
			}
		}
		
		return in_array(self::SERVICE_FUNCITON_PREFIX.$_requestedResource, $setupResources);
	}
	
	/**
	 * Check to see if any elements of a finglonger setup have run.
	 *
	 * @return Array with two indecies (config, database) set to true if the setup has taken action
	 * on the config (db_config.php) or the database.  
	 * If unable to determine if action has taken place then the index is set to null.
	 */
	public function fl_get_setup_status(){
			    
		$response = array();
		$CONFIG_INDEX_NAME = 'config';
		$DATABASE_INDEX_NAME = 'database';
		
		//Check the setup config
		$dbCredsInput = file_get_contents("finglonger/setup/db_config.php");
		
		if($dbCredsInput === false){
			$this->message = 'SETUP '.__LINE__.': Database config file could not be opened. Unable to check status of config or database.';
			$response[$CONFIG_INDEX_NAME] = null;
			$response[$DATABASE_INDEX_NAME] = null;
		}
		
		//Trim new line character
		$dbCredsInput = str_replace('\n', '', $dbCredsInput);
		
		if($dbCredsInput == '<?PHP //Good News Everbody!!!! ?>'){
			$this->message = 'SETUP '.__LINE__.': Database config is in default state.  Unable to connect to database to determine if system tables are present.';
			$response[$CONFIG_INDEX_NAME] = false;
			$response[$DATABASE_INDEX_NAME] = null;
			return $response;
		}else{
			$response[$CONFIG_INDEX_NAME] = true;
		}
		
		//Check to see if any system tables exist.
		try{
			
			require ('setup/db_setup.php');
			
		}catch (Exception $ex){
			
			$this->message = 'SETUP '.__LINE__.': Unable to to find db_setup.php to check status of database install.';
			$response[$DATABASE_INDEX_NAME] = null;
			return $response;
		
		}
		
		//Set a connection
		//Setup will use the creds passed in by the user stored in object
		$con = $this->getConnection();
		
		if($con == false){
		    $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
		    return false;
		}
		
		$result = $con->query('SHOW TABLES');
		
		if($result === false){
			$this->error = 'SETUP '.__LINE__.': Unable to check status of database install. System table check query failed. '.$con->error;
			$response[$DATABASE_INDEX_NAME] = null;
			return false;
		}
		
		//Check to see if any system tables exist.
		try{
		    
		    require ('setup/db_config.php');
		    
		}catch (Exception $ex){
		    
		    $this->message = 'SETUP '.__LINE__.': Unable to to find db_setup.php to check status of database install.';
		    $response[$DATABASE_INDEX_NAME] = null;
		    return $response;
		    
		}
		
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
		    		    
			if(in_array($row['Tables_in_'.$fl_database_name], $fl_systemTableNames)){
				$response[$DATABASE_INDEX_NAME] = true;
			}
		}
		
		
		return $response;
		
	}
	
	/**
	 * Check to see if system tables already exist used as a check for existing install.
	 *
	 * @return Boolean true if system tables don't exist and can be created.
	 * @return Boolean false if any system tables exist or the query fails.
	 */
	public function fl_can_insert_system_tables(){
		
		//Check to see if any system tables exist
		require('setup/db_setup.php');
		
		$con = $this->getConnection();
		
		if($con == false){
		    $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
		    return false;
		}
		
		$result = $con->query('SHOW TABLES');
		
		if($result === false){
			$this->error = 'SETUP '.__LINE__.': System table check query failed. '.$con->error;
			return false;
		}
		
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
		    if(in_array($row['Tables_in_'.$this->dbCreds['db_name']], $fl_systemTableNames)){
		        $this->error = 'SETUP '.__LINE__.': '.$row['Tables_in_'.$this->dbCreds['db_name']]. ' already exists.';
				return false;
			}
		}
		
		return true;
	}
	
	
	/**
	 * Create all system tables for new Finglonger install.
	 *
	 * @return Boolean true on success.
	 * @return Boolean fals on failure.
	 */
	public function fl_setup_system_tables(){
		
		require_once('setup/db_setup.php');
		
		$con = $this->getConnection();
		
		if($con == false){
		    $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
		    return false;
		}
		
		$result = $con->multi_query($fl_systemTablesSQL);
			
		if($result === false){
			$this->error = 'SETUP '.__LINE__.': System table setup failed. '.$con->error;
			return false;
		}
		
		$con->close();
		
		usleep(500000);
		
		//Don't release this resource until we check if the tables exist.  
		for($i = 0; $i < 5; $i++){
            
		    $systemTablesFound = 0;
		    
		    $con = $this->getConnection();
		    
		    $checkSystemTablesResult = $con->query('SHOW TABLES');
		  
		    if($checkSystemTablesResult === false){
        		    $this->error = 'SETUP '.__LINE__.': System table check query failed. '.$con->error;
        		    return false;
        		}
        	         		
        		while ($row = $checkSystemTablesResult->fetch_array(MYSQLI_ASSOC)){
        		    
        		    //echo $row['Tables_in_'.$this->dbCreds['db_name']];
        		    if(in_array($row['Tables_in_'.$this->dbCreds['db_name']], $fl_systemTableNames)){
        		        $systemTablesFound = $systemTablesFound + 1;
        		    }        		    
        		    
        		}
        		
        		if($systemTablesFound == sizeof($fl_systemTableNames)){
        		    $i = 6;
        		}
        		
        		$con->close();
        		
        		usleep(500000);
        		
		}
		
		return($systemTablesFound == sizeof($fl_systemTableNames));
		
	}
	
	/**
	 * Create all base system resources for Finglonger intstall.
	 *
	 * @return Boolean true on success.
	 * @return Boolean fals on failure.
	 */
	public function fl_populate_base_resources(){
		
	    $con = $this->getConnection();
	    
	    if($con == false){
	        $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
	        return false;
	    }
	    
	    //Insert initial resource categories
	    $resourceCategorySQL = "INSERT INTO `fl_resource_categories` (`resource_category_id`, `resource_category_name`) VALUES
				(1, 'system'), (2, 'user created')";
	    
	    
	    $resourceCategoryResult = $con->query($resourceCategorySQL);
	    
	    if($resourceCategoryResult === false){
	        $this->error = 'SETUP '.__LINE__.': System user type insertion failed. '.$con->error;
	        return false;
	    }		
	    
		//Insert the base resources
		$resourceSQL = "INSERT INTO `fl_resources` (`resource_id`, `resource_name`, `resource_category_id`) VALUES 
				(1, 'login', 1), 
				(2, 'logout', 1), 
				(3, 'request-change-password', 1),
				(4, 'change-password', 1),
				(5, 'get-user', 1)";
		
		
		$resourceResult = $con->multi_query($resourceSQL);
		
		if($resourceResult === false){
			$this->error = 'SETUP '.__LINE__.': System resource insertion failed. '.$con->error;
			return false;	
		}
		
		//Insert the free user type
		$userTypeSQL = "INSERT INTO `fl_user_types` (`user_type_id`, `user_type`, `parent_id`, `locked`) VALUES
				(1, 'open', NULL, 'true')";
		
		
		$userTypeResult = $con->query($userTypeSQL);
		
		if($userTypeResult === false){
			$this->error = 'SETUP '.__LINE__.': System user type insertion failed. '.$con->error;
			return false;
		}

		//Insert the access assignments
		$userTypeAccessSQL = "INSERT INTO `fl_user_type_resource_access` (`user_type_resource_access_id`, `user_type_id`, `resource_id`, `get_allowed`, `post_allowed`, `delete_allowed`) VALUES
				(1, 1, 1, 'false', 'true', 'false'),
				(2, 1, 2, 'false', 'true', 'false'),
				(3, 1, 3, 'false', 'true', 'false'),
				(4, 1, 4, 'false', 'true', 'false'),
				(5, 1, 5, 'true', 'false', 'false')";
		
		$userTypeAccessResult = $con->multi_query($userTypeAccessSQL);
		
		if($userTypeAccessResult === false){
			$this->error = 'SETUP '.__LINE__.': System user type access insertion failed. '.$con->error;
			return false;
		}
		
		//Find a better spot to put this
		$systemEmailPopulationResult = $this->fl_populate_system_email_content();
		
		if($systemEmailPopulationResult === false){
			return false;
		}
		
		return true;
	}
	
	/**
	 * By default Mom has access to all resources so all that is needed is to create the Mom user type for Finglonger intstall.
	 *
	 * @return Boolean true on success.
	 * @return Boolean fals on failure.
	 */
	public function fl_populate_mom_resources(){
		
	    $con = $this->getConnection();
	    
	    if($con == false){
	        $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
	        return false;
	    }
	    
		//Create the admin user type
		$userTypeSQL = "INSERT INTO `fl_user_types` (`user_type_id`, `user_type`, `parent_id`, `locked`) VALUES
				(2, 'mom', 0, 'true')";
		
		$userTypeResult = $con->query($userTypeSQL);
		
		if($userTypeResult === false){
			$this->error = 'SETUP '.__LINE__.': Mom user type insertion failed. '.$con->error;
			return false;
		}
			
		return true;
	
	}
	
	/**
	 * Set the database config file (Setup/db_config.php)
	 *
	 * @return Boolean true on success.
	 * @return Boolean fals on failure.
	 */
	public function fl_set_db_creds(){
		
		//Generate the resource request
		$resourceRequest = jan_generateResourceRequest();
		 
		if($resourceRequest === false){
			$this->error = 'SETUP: '.__LINE__. ' Could not generate the resource request.';
			return false;
		}
		
		//Retreive post data
		$data = $resourceRequest->postData;
		
		$dbCredData = json_decode($data, true);
		
		if(!isset($dbCredData['hostname']) || !isset($dbCredData['db_name']) || !isset($dbCredData['user_name']) || !isset($dbCredData['password'])){
			$this->error = 'SETUP '.__LINE__.': Data posted for Database connection is incomplete.';
			return false;
		}
		
		//Catch any thrown warnings
		$this->dbCreds = $dbCredData;
			
		try{
		    $con = new mysqli($this->dbCreds['hostname'],$this->dbCreds['user_name'],$this->dbCreds['password'],$this->dbCreds['db_name']);
		}
		catch (Exception $ex){
			$this->error = 'SETUP '.__LINE__.': Could not make connection to the database.  Please check credentials. :'.$ex->getMessage();
        	return false;
		}
			
    	   //Error out if we return false
        if ($con->connect_error){       	
        	   $this->error = 'SETUP '.__LINE__.': Could not make connection to the database.  Please check credentials. :'.$con->connect_error;
        	   return false;
        }

      	//If the connection is successful then write the config file.
		$dbCredsInput = file_get_contents("finglonger/Setup/db_config.php");
		
		if($dbCredsInput === false){
			$this->error = 'SETUP '.__LINE__.': Database config file could not be opened.';
			return false;
		}
		
		//Trim new line character
		$dbCredsInput = str_replace('\n', '', $dbCredsInput);
		
		if($dbCredsInput != '<?PHP //Good News Everbody!!!! ?>'){
			$this->error = 'SETUP '.__LINE__.': Database config file has already been altered so we do not want to mess with it. '.$dbCredsInput;
			return false;
		}
		
		//Generate the new file string
		$dbCredsOutput = '<?PHP $fl_database_name = "'.$dbCredData['db_name'].'";$fl_database_user = "'.$dbCredData['user_name'].'";$fl_database_password = "'.$dbCredData['password'].'";$fl_database_host = "'.$dbCredData['hostname'].'";?>';
		
		//Store the db creds locally to use to connect to the db
		if(file_put_contents("finglonger/Setup/db_config.php", $dbCredsOutput) === false){
			$this->error = 'SETUP '.__LINE__.': Could not write to database config file (Setup/db_config.php). Double check the file permissions.';
			return false;
		}

		return true;
	}
	
	/**
	 * Create a Mom user.  Which is Super!
	 *
	 * @return Boolean true on success.
	 * @return Boolean fals on failure.
	 */
	public function fl_create_mom_user(){
		
		//Resource can only run if there are no existing MOM users.
		$momUsersSQL = 'SELECT * from fl_users WHERE user_type_id = 2';
		
		$con = $this->getConnection();
		
		if($con == false){
		    $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
		    return false;
		}
		
		$momUsersResult = $con->query($momUsersSQL);
		
		if($momUsersResult->num_rows > 0){
			$this->error = 'SETUP: '.__LINE__. ' Cannot create MOM user.  Mom already exists.';
			return false;
		}
		
		//Generate the resource request
        $resourceRequest = jan_generateResourceRequest();
    	
        if($resourceRequest === false){
            $this->error = 'SETUP: '.__LINE__. ' Could not generate the resource request.';
            return false;
        	}
    	
        //Retreive post data
        $data = $resourceRequest->postData; 
        
        $userData = json_decode($data, true);
		
        if(!isset($userData['user_name']) || !isset($userData['user_name']) || !isset($userData['user_name'])){
            	$this->error = 'SETUP '.__LINE__.': Data posted for MOM user is incomplete.';
        	   return false;
        }
        	
        $stmt = $con->prepare('INSERT INTO fl_users (user_type_id, user_name, password, email) VALUES (?,?,?,?)');
        
        if($stmt === false){
            	$this->error = 'SETUP '.__LINE__.': Mom user prepared statement failed '.$con->error;
        	   return false;
        }
        
        $userTypeId = 2;
        $userName = $userData['user_name'];
        $password = create_hash($userData['password']);
        $email = $userData['email']; 
        
        $stmt->bind_param("isss", $userTypeId, $userName, $password, $email);
        
        $result = $stmt->execute();
        
        if($result === false){
        	   $this->error = 'SETUP '.__LINE__.': Mom user statement execution failed '.$con->error;
        	   return false;
        }
        
        return true;		
	}
	
	/**
	 * Populate system emails.
	 *
	 * @return Boolean true on success.
	 * @return Boolean false on failure.
	 */
	private function fl_populate_system_email_content(){
		
	    $con = $this->getConnection();
	    
	    if($con == false){
	        $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
	        return false;
	    }
	    
		$systemEmailContentSQL = "INSERT INTO `fl_emails` (`email_id`, `email_name`, `email_subject`, `email_body_text`, `email_body_html`) VALUES
(2, 'Password Reset Success', 'Finglonger Password Was Successfully Reset', 'You have successfully changed your Finglonger password./n/nThanks for using the tool!/n/nHubert.', 'You have successfully changed your Finglonger password.<br /><br />Thanks for using the tool!<br /><br />Hubert.'),
(1, 'Request New Password', 'Finglonger Password Reset', 'Hi [First Name],/n/n You have indicated that you have forgotten your Finglonger password./n/n To reset your password click the link below and follow the instructions./n/n[[Link]]>Reset Password</a>/n/nFor security reasons, this link is only valid for 24 hours. If clicking does not work, copy and paste the following link into a Web browser./n/n [Link]/n/nThanks,/nHubert', 'Hi [First Name],<br><br> You have indicated that you have forgotten your Finglonger password.<br><br> To reset your password click the link below and follow the instructions.<br><br><a href = [Link]>Reset Password</a><br><br>For security reasons, this link is only valid for 24 hours. If clicking does not work, copy and paste the following link into a Web browser.<br><br> [Link]<br><br>Thanks,<br>Hubert'),
(3, 'User Validation', 'Welcome to Finglonger', 'Welcome to Finglonger! /n/n To get started, we need to confirm your email address, so please click this link to finish creating your account: /n/n <a href = ‘[Link]''>Confirm your email address</a> /n/nFor security reasons, this link is only valid for 24 hours. If clicking does not work, copy and paste the following link into a Web browser./n/n [Link]/n/n', 'Welcome to Finglonger! <br /><br /> To get started, we need to confirm your email address, so please click this link to finish creating your account: <br /><br /> <a href = ''[Link]''>Confirm your email address</a> <br><br>For security reasons, this link is only valid for 24 hours. If clicking does not work, copy and paste the following link into a Web browser.<br /><br /> [Link]<br><br>');";	
		$systemEmailContentResult = $con->query($systemEmailContentSQL);
		
		if($systemEmailContentResult === false){
			$this->error = 'SETUP '.__LINE__.': System email population failed. '.$con->error;
			return false;
		}
		
		return true;
	}
	
	private function fl_populate_integration_data(){
		
	    $con = $this->getConnection();
	    
	    if($con == false){
	        $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
	        return false;
	    }
	    
		$integrationDataSQL = "INSERT INTO `fl_integrations` (`integration_id`, `integration_name`, `enabled`) VALUES
							(1, 'mailgun', 'false')";
		
		$integrationDataResult = $con->query($integrationDataSQL);
		
		if($integrationDataResult === false){
			$this->error = 'SETUP '.__LINE__.': Integration data population failed. '.$con->error;
			return false;
		}
		
		$integrationMetaDataSQL = "INSERT INTO `fl_integration_meta` (`integration_meta_id`, `integration_id`, `meta_name`, `meta_value`) VALUES
								(1, 1, 'api_key', null),
								(2, 1, 'domain', null);";
		
		$integrationMetaDataResult = $con->query($integrationMetaDataSQL);
		
		if($integrationMetaDataResult === false){
			$this->error = 'SETUP '.__LINE__.': Integration meta data population failed. '.$con->error;
			return false;
		}
		
		return true;
		
	}
	
	private function fl_populate_system_settings(){
		
	    $con = $this->getConnection();
	    
	    if($con == false){
	        $this->error = 'SETUP: '.__LINE__. ' Unable to connect to database.';
	        return false;
	    }
	    
		$systemSettingsSQL = "INSERT INTO `fl_settings` (`setting_id`, `setting_name`) VALUES 
							(1, 'System Contact'),
							(2, 'Version'),
							(3, 'Session Expiry');";
		
		$systemSettingsResult = $con->query($systemSettingsSQL);
		
		if($systemSettingsResult === false){
			$this->error = 'SETUP '.__LINE__.': System settings population failed. '.$con->error;
			return false;
		}
		
		$systemSettingMetaSQL = "INSERT INTO `fl_setting_meta` (`settings_meta_id`, `setting_id`, `meta_name`, `meta_value`) VALUES
								(1, 1,  'first_name', 'Hubert'),
								(2, 1,  'last_name', 'Jay'),
								(3, 1,  'email', 'whatif@finglonger.io'),
								(4, 2,  'version_number', '1.0'),
								(5, 3,  'session_expiry', '3600');";
		
		$systemSettingMetaResult = $con->query($systemSettingMetaSQL);
		
		if($systemSettingMetaResult === false){
			$this->error = 'SETUP '.__LINE__.': System settings meta data population failed. '.$con->error;
			return false;
		}
		
		return true;
		
	}
	
	/**
	 * Create a database connection
	 *
	 * @return mysqli connection
	 */
	private function getConnection(){
	    
	    //Check using object properties first
	    $con = new mysqli($this->dbCreds['hostname'],$this->dbCreds['user_name'],$this->dbCreds['password'],$this->dbCreds['db_name']);
	    
	    if ($con->connect_error || $con == false){

	        //Check to see if we can create a connectin using
            $con = jan_getConnection();
	    }
	    
	    return $con;
	    
	}
	
	/**
	 * Destory the Finglonger session variable created to manage the setup.
	 *
	 * @return Boolean true on success.
	 */
	public function fl_destory_setup(){
		
		unset($_SESSION['finglongerSetup']);
		
		return true;
		
	}

}
?>