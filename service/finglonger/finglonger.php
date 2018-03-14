<?php

require_once 'helpers/load.php';
require_once 'integrations/load.php';
require_once 'user.php';
require_once 'ship.php';
require_once 'business.php';
require_once 'finglonger/setup.php';
require_once 'setup/db_config.php'; 

/**
 * Class to execute web service calls.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

class Finglonger {
	
	//Reporting Constants
	const SUCCESS = 'success';
	const ERROR = 'error';
	const MESSAGE = 'message';
	const DATA = 'data';
	const EXECUTION_TIME = 'execution-time';
	
	//Prefix to define functions as web service end points
	const SERVICE_FUNCITON_PREFIX = 'fl_';
	
	private $user;
	private $ship;
	private $business;
	private $setup;
	private $con;
	private $ini;
	private $operationTimer;
	private $initialized;
	private $setupInitialized;
	private $error;
	private $message;
	private $OAUTHSettings;
	private $email;
	
	/**
	 * Constructor
	 *
	 */
	public function __construct(){
		
		$this->initialized = false;
		$this->setupInitialized = false;
	}
	
	/**
	 * Initialize a setup instance of Finglonger used on install.
	 *
	 * @return void
	 */
	public function initSetup(){
	
		//Pull and store the service base url
		$this->serviceBase = str_replace('index.php', '', $_SERVER['PHP_SELF']);
		
		//Initialize an Operation Timer Object
		$this->operationTimer = new OperationTimer();
		
		//Instantiate a setup object if one doesn't exist.
	    $this->setup = new Setup();
	    $this->setupInitialized = $this->setup->init();

	}
	
	/**
	 * Initialize a full version of Finglonger.
	 *
	 * @return void
	 */
	public function init(){
		
		$this->con = jan_getConnection();
		
		if($this->con === false){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': Database connection failed'));
		}
		
		if ($this->con->connect_error){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': Database connection failed: '.$this->con->connect_error));
		}
			
		$this->serviceBase = str_replace('index.php', '', $_SERVER['PHP_SELF']);
		
		//Create an empty user
		$this->user = new User();
		
		//Initialize the user
		if($this->user->init() === false){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->user->error));
		}
		
		//Create an empty ship
		$this->ship = new Ship();
		
		//Initialize the ship
		if($this->ship->init() === false){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->ship->error));
		}
		
		//Create an empty business factory
		$this->business = new Business();
		
		//Initialize the business factory
		$businessDirectory = 'finglonger/Business-Factory/'; //TODO why is this a variable?
		
		//Initialize the business factory
		if($this->business->init($businessDirectory) === false){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->business->error));
		}
		
		$this->business->getBureaucracyFiles();
		
		//Initialize an Operation Timer Object
		$this->operationTimer = new OperationTimer();
		
		//Flag that finglonger has been initialized
		$this->initialized = true;
		
		//Empty holder for message output.
		$this->error = '';
		$this->message = '';
		
		//Find all class methods
		$this->generateClassMethods();
		
	}
	
	/**
	 * Store all class methods available as a resource.
	 *
	 * @return void
	 */
	public function generateClassMethods(){
		
		//Generate list of the finglonger class specific resources
		$finglongerClassMethods = get_class_methods($this);
		$this->finglongerResources = array();
		
		$sizeOfFinglongerClassMethods = sizeof($finglongerClassMethods);
		for($i = 0; $i < $sizeOfFinglongerClassMethods; $i++){
		
			if(substr($finglongerClassMethods[$i], 0, 2) == 'fl'){
				$this->finglongerResources[] = $finglongerClassMethods[$i];
			}
		}
	}
	
	/**
	 * Get the current database and software version of Finglonger running.
	 * 
	 * @return Boolean false on error
	 * @return Array on success
	 */
	private function fl_get_version(){
		
		//Pull the database version.
		$versionSQL = 'SELECT fl_setting_meta.meta_value AS "database_version" from fl_settings LEFT JOIN fl_setting_meta ON fl_settings.setting_id = fl_setting_meta.setting_id WHERE fl_setting_meta.meta_name = "version_number" AND fl_settings.setting_name = "Version"';
			
		$versionDataResult = jan_stmtQuery2($versionSQL);
		
		if($versionDataResult === false){
			$this->error = 'FINGLONGER: '.__LINE__. ' An error occured retrieving database version.';
			return false;
		}
		
		//There should only be 1 record
		if($versionDataResult['affected_rows'] != 1){
			$this->error = 'FINGLONGER: '.__LINE__. ' An error occured retrieving database version.';
			return false;
		}
		
		if(isset($versionDataResult['data'][0]['database_version']) == false){
			$this->error = 'FINGLONER: '.__LINE__. ' Database version value missing from database.';
			return false;
		}	

		$databaseVersion = $versionDataResult['data'][0]['database_version'];
		
		if(defined('SOFTWARE_VERSION') === false){
			$this->error = 'FINGLONER: '.__LINE__. ' Software version could not be found.';
			return false;
		}
		
		return array('software_version'=>SOFTWARE_VERSION, 'database_version'=>$databaseVersion);
		
	}
	
	/**
	 * Reinitialize the system.  Used by the MOM panel to detect changes in the DB or code when a user
	 * logged in an making changes.
	 *
	 * @return Array
	 */
	private function fl_reinitialize(){
	
		//Check to see if Finglonger has already been initialized
		if($this->initialized === false){
			$this->error = 'FINGLONGER '.__LINE__.': Unable to re-initialize Finglonger.  It has not been initialized.  How can we re-initialize somethign that has not been initialized?  We do not know.';
			return false;
		}
	
		//Re-initialize the user
		if($this->user->re_init() === false){
			$this->error = 'FINGLONGER '.__LINE__.': '.$this->user->error;
			return false;
		}
	
		//Re-initialize the ship
		if($this->ship->re_init() === false){
			$this->error = 'FINGLONGER '.__LINE__.' : '.$this->ship->error;
			return false;
		}
			
		//This probably hasn't changed but just in case.  Generate list of the finglonger class specific resources
		$finglongerClassMethods = get_class_methods($this);
		$this->finglongerResources = array();
	
		$sizeOfFinglongerClassMethods = sizeof($finglongerClassMethods);
		for($i = 0; $i < $sizeOfFinglongerClassMethods; $i++){
			if(substr($finglongerClassMethods[$i], 0, 2) == 'fl'){
				$this->finglongerResources[] = $finglongerClassMethods[$i];
			}
		}
		
		//Reinitialize the business resources
		$this->business->resetCurrentResources();		
		
		return true;
	}
	
	
	/**
	 * Process an API request.
	 *
	 * @return void
	 */
	public function setup(){
			
		$requestedResource = jan_generateResourceRequest();
			
		//Start a timer for resource efficiency analysis
		$this->operationTimer->startTimer();
		
		if($this->isSetupResource($requestedResource->parentResource) === true){
					    
		    $result = $this->setup->go($requestedResource->parentResource);
			
			if($result === false){
				jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->setup->error));
			}
			
			jan_generateResponse(self::SUCCESS, array(self::DATA=>$result, self::EXECUTION_TIME=>$this->operationTimer->getTime()));
		
		}
		
		jan_generateResponse(jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': The requested method is not a setup method.')));
	}
	
	
	/**
	 * Process an API request.
	 *
	 * @return void
	 */
	public function go(){
		
		//Establish a connection
		$this->con = jan_getConnection();
		
		if ($this->con->connect_error){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': Database connection failed: '.$this->con->connect_error));
		}
		
		// Check to see if the user has expired or not.
		// if($this->user->getUser()){

		// 	$this->sessionDuration = new SessionDuration();

		// 	$this->sessionDuration->init();

		// 	if(!$this->sessionDuration->checkAgainstDefault($this->user->sessionTimeStamp)){				
				
		// 		if(session_destroy() === false){
		//     		$this->error = 'USER '.__LINE__.': Could not destroy session.  Logout failed';
		//     		return false;
		//     	}else{
		//     		jan_generateResponse(self::ERROR, array(self::MESSAGE=>'Your session has expired.'));
		//     	}		    	
		// 	}
		// }
		
	
		$requestedResource = jan_generateResourceRequest();

		//Start a timer for resource efficiency analysis
		$this->operationTimer->startTimer();
		
		//Does the user have access to this method
		if($this->user->doesHaveResourceAccess() === false){
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->user->error, self::EXECUTION_TIME=>$this->operationTimer->getTime()));
		}
				
		//Reset the message property
		$this->message = '';
		
		//Reset the business resources
		$this->business->getBureaucracyFiles();
		
		if($this->user->isUserResource($requestedResource->parentResource) === true){
			
			$result = $this->user->go($requestedResource->parentResource);
			
			if($result === false){
				jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->user->error));
			}
			
			if(isset($this->user->success)){
				$this->message = $this->user->success;
			}
			
		}elseif($this->ship->isShipResource($requestedResource->parentResource) === true){
			
			$result = $this->ship->go($requestedResource->parentResource);
			
			if($result === false){
				jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->ship->error));
			}
			
		}elseif($this->isFinglongerResource($requestedResource->parentResource) === true){
			
			$result = $this->go_finglonger($requestedResource->parentResource);
			
			if($result === false){
				jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->error));
			}
			
		}elseif($this->business->isBusinessResource($requestedResource->parentResource) === true){
			
			$result = $this->business->go($requestedResource->parentResource);
			
			if($result === false){
				jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': '.$this->business->error));
			}
			
		}else{
			
			//This would occur if a user was given access to a resource that did not exist.
			jan_generateResponse(self::ERROR, array(self::MESSAGE=>'FINGLONGER '.__LINE__.': Requested resource could not be found.', self::EXECUTION_TIME=>$this->operationTimer->getTime()));
			
		}
		
		$optionalResponseArgs = array();
		
		jan_generateResponse(self::SUCCESS, array(self::DATA=>$result, self::MESSAGE =>$this->message, self::EXECUTION_TIME=>$this->operationTimer->getTime()));
		
	}
	
	
	/**
	 * Call a Finglonger class resource.
	 *
	 * @param String $_requestedResource
	 * @return Result of requested function
	 */
	private function go_finglonger($_requestedResource){
		
		return call_user_func(array($this, self::SERVICE_FUNCITON_PREFIX.$_requestedResource));
		
	}
	
	/**
	 * Determine if a requested resource is can be executed by the User class
	 *
	 * @param String $_requestedResource
	 * @return Boolean true on success
	 */
	private function isFinglongerResource($_requestedResource){
		
		return in_array(self::SERVICE_FUNCITON_PREFIX.$_requestedResource, $this->finglongerResources);

	}
	
	/**
	 * Return all available Finglonger Class resources
	 *
	 * @return Array 
	 */
	public function getAllResources(){

		$formattedResources = array();
		
		$sizeOfResources = sizeof($this->finglongerResources);
		for($i = 0; $i < $sizeOfResources; $i++){
			$formattedResources[] = str_replace('_', '-', str_replace(self::SERVICE_FUNCITON_PREFIX, '', $this->finglongerResources[$i]));
		}
		
		return $formattedResources;
		
	}
	
	/**
	 * Return boolean if Finglonger has been initialized or not.
	 *
	 * @return Boolean T
	 */
    public function isInitialized(){

    	   return $this->initialized;
    
    }
    
    
    /**
     * Return boolean if Finglongers setup object has been initialized or not.
     *
     * @return Boolean T
     */
    public function isSetupInitialized(){
        
        return $this->setupInitialized;
        
    }
    /**
    * Retrun all available resources (Finglonger, User, Business, Ship)
    *
    * @return Array
    */
	private function fl_all_resources(){
		
		return array_merge($this->getAllResources(), $this->business->getAllResources(), $this->ship->getAllResources(), $this->user->getAllResources());	
			
	}  
	
	/**
	 * Return all system specified available resources (Finglonger, User, Ship)
	 *
	 * @return Array
	 */
	private function fl_system_resources(){
		
		return array_merge($this->ship->getSystemResources(), $this->getAllResources(), $this->user->getAllResources());	
		
	}
	
	/**
	 * Return an associtive array with two boolean indexes.  
	 * "are_resources_added" if true indicates resources have been added via code or the database since finglonger was initialized.
	 * "are_resources_removed" if true indicates resources have been removed via code or the database since finglonger was initialized.
	 *
	 * @return Array
	 */
	private function fl_are_resources_changed(){
		
		//We are doing a check for resources added or removed.  This flag is used to signify to 
		//run the reinitialization.		
		$areNewResources = false;
		$areRemovedResources = false;
		
		//Generate lookup object for auto generating queries
		$tableResult = $this->con->query('SHOW TABLES');
		$dbTables = array();
		
		if($tableResult == false){
			$this->error = 'FINGLONGER '.__LINE__.': Problem in the pre-launch checklist. Unable to find any tables in the database';
			return false;
		}
		
		require 'finglonger/Setup/db_config.php';
				
		while ($tableRow = $tableResult->fetch_array( MYSQLI_ASSOC)) {
			$dbTables[] = $tableRow['Tables_in_'.$fl_database_name];
		}
		
		$this->ship->getDBLookup();
		
		$newDbTables = array_diff($dbTables, array_keys($this->ship->getDBLookup()));
		$newBusinessResources = array_diff($this->business->getAllResources(), $this->business->getCurrentResources());
		
		$newResources = array_merge($newDbTables, $newBusinessResources);
		
		if(sizeof($newResources) > 0){
			$areNewResources = true;
		}
		
		$removedDbTables = array_diff(array_keys($this->ship->getDBLookup()), $dbTables);
		$removedBusinessResources = array_diff($this->business->getCurrentResources(), $this->business->getAllResources());
				
		$removedResources = array_merge($removedDbTables, $removedBusinessResources);
		
		$sizeOfRemovedResources = sizeof($removedResources);
		$failedRemovedResources = '';
		
		if($sizeOfRemovedResources > 0){
			$areRemovedResources = true;
			
			for($i = 0; $i < $sizeOfRemovedResources; $i++){
				
				//Mark the resources in the fl_resources table as removed
				$updateRemovedResourcesSQL = "UPDATE fl_resources set resource_missing = 'true' where resource_name = '".$removedResources[$i]."'";
				$updateRemovedResourcesResult = $this->con->query($updateRemovedResourcesSQL);
				
				if($updateRemovedResourcesResult === false){
					//We will keep going if a single update fails store the error and return with the problematic updates in the message
					$failedRemovedResources .= $removedResources[$i].' - '.$this->con->error.' '.$updateRemovedResourcesSQL.',';
				}
			}
			
			if(strlen($failedRemovedResources) > 0){
				$this->message = 'The following resources could not be flagged as removed: '. rtrim($failedRemovedResources, ',');
			}
		}
	
		return array("are_resources_added"=>$areNewResources, "are_resources_removed"=>$areRemovedResources);
		
	}
	
	/**
	 * Return an array with the indecies denoting attributes of the current user.
	 * first_name, last_name, email, user_name, user_id
	 *
	 * @return Array
	 */
	private function fl_get_user(){
		
		$user = $this->user->getUser();
		
		if($user === false){
			$this->error = 'FINGLONGER '.__LINE__.': '.$this->user->error;
		}
		
		return $user;
		
	}

	/**
	 * Determine if a resource is a setup classified resource.
	 *
	 * @param String resource name
	 * @return True if resource passed is a setup resource.
	 * @return False if resource passed is not a setup resource.
	 */
	private function isSetupResource($_requestedResource){
		
		if(Setup::isSetupResource($_requestedResource) === false){
			return false;
		}
		
		return true;
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
		
		//If the connection is successful then write the config file.
		$dbCredsInput = file_get_contents("finglonger/Setup/db_config.php");
		
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
		
		//Check to see if any system tables exist.
		try{
				
			require ('setup/db_config.php');
				
		}catch (Exception $ex){
				
			$this->message = 'SETUP '.__LINE__.': Unable to to find db_config.php to check status of database install.';
			$response[$DATABASE_INDEX_NAME] = null;
			return $response;
		
		}
		
		
		$result = $this->con->query('SHOW TABLES');
		
		if($result === false){
			$this->error = 'SETUP '.__LINE__.': Unable to check status of database install. System table check query failed. '.$this->con->error;
			$response[$DATABASE_INDEX_NAME] = null;
			return false;
		}
		
		while ($row = $result->fetch_array(MYSQLI_ASSOC)){
			if(in_array($row['Tables_in_'.$fl_database_name], $fl_systemTableNames)){
				$response[$DATABASE_INDEX_NAME] = true;
			}
		}
		
		return $response;
		
	}
}
?>