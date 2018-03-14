<?php
/**
 * Class to execute functions defined in the 'Central-Bureaucracy' as web service calls.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

class Business{
	
	const SERVICE_FUNCITON_PREFIX = 'fl_'; //Don't change this
	//Don't change this unless you want to change where Finglonger is going to look for your user
	//created business functions
	const BUSINESS_FILES_DIRECTORY = 'central-bureaucracy'; 
	
	/**
	 * Constructor
	 *
	 */
	public function __construct(){

		//Empty Constructor
		
	}
	
	/**
	 * Initialize the business object.
	 *
	 * @return Boolean true on success
	 * @return Boolean false on error
	 */
	public function init(){
		
		//Initialize messaging strings
		$this->error = '';
		$this->message = '';
		
		
		
	
        if(scandir(__DIR__.'/'.self::BUSINESS_FILES_DIRECTORY) === false){
        	$this->message = 'BUSINESS '.__LINE__.': Business Directory could not be found';
        }
   
        $this->getBureaucracyFiles();
        
        //Store all the business services in local variable for comparison purposes
        $this->currentResources = $this->getAllResources();
        
        return true;
	}
	
	
	/**
	 * Include user created custom functionality.
	 *
	 * @return void
	 */
	public function getBureaucracyFiles(){
		
		$businessFiles = array_diff(scandir(__DIR__.'/'.self::BUSINESS_FILES_DIRECTORY), ['..','/','.']);
		
		//Include all business files - Move this out of here
		foreach ($businessFiles as $businessFile){
			 
			//Ensure we are including a php file.
			if(strtolower(substr($businessFile, -3)) == 'php'){
				require_once __DIR__.'/'.self::BUSINESS_FILES_DIRECTORY.'/'.$businessFile;
			}
		}
		
	}
	
	/**
	 * Reset available business resources.
	 *
	 * @return void
	 */
	public function resetCurrentResources(){
		
		$this->currentResources = $this->getAllResources();
		
	}
	
	/**
	 * Return an array of all business resource names.
	 *
	 * @return Array of all business resource names.
	 */
	public function getCurrentResources(){
		
		return $this->currentResources;
		
	}
	
	/**
	 * Execute a business resource.
	 *
	 * @param String $_requestedResource the name of the resource to be called
	 * @return Variable data from routed function in the Business Factory
	 * @return Boolean false on error
	 */
	public function go($_requestedResource){
		
		//Reset messaging
		$this->error = '';
		$this->message = '';
		
		//Generate the resource request
		$resourceRequest = jan_generateResourceRequest();
		
		if($resourceRequest === false){
			$this->error = 'BUSINESS: '.__LINE__. ' Could not generate the resource request: '.htmlspecialchars($this->con->error);
			return false;
		}
		
		//Attempt to call the function
		$result = call_user_func(self::SERVICE_FUNCITON_PREFIX.str_replace('-', '_', $_requestedResource));
		
		if($result === false){
			$this->error = 'BUSINESS '.__LINE__.': FUNCTION :'.self::SERVICE_FUNCITON_PREFIX.str_replace('-', '_', $_requestedResource);
			return false;
		}
			
		return $result;
	
	}
	
	
	/**
	* Determine if a requested resource is can be executed by the User class.
	*
	* @param String $_requestedResource
	* 
	* @return Boolean true on success
	* @return Boolean false on failure
	*/
	public function isBusinessResource($_requestedResource){
		
		//Check to see if it is callable.
		return function_exists(self::SERVICE_FUNCITON_PREFIX.str_replace('-', '_', $_requestedResource)); 
		
	} 
	
	
	/**
	 * Get all of the web service defined function names from within the Business Factory. 
	 * Used to determine if a resource request should be processed by the Business object.
	 *
	 * @return Array of function name
	 */
	public function getAllResources(){
		
		$definedFunctions = get_defined_functions();
		$userDefinedFunctions = $definedFunctions['user'];
		$sizeOfUserDefinedFunctions = sizeof($userDefinedFunctions);
		$businessDefinedFunctions = array();
		
		for($i = 0; $i < $sizeOfUserDefinedFunctions; $i++){
			
			if(substr($userDefinedFunctions[$i], 0, 2) === 'fl'){
				$businessDefinedFunctions[] = str_replace('_', '-', str_replace(self::SERVICE_FUNCITON_PREFIX, '', $userDefinedFunctions[$i]));
			}
			
		}
		
		return $businessDefinedFunctions;
		
	}
	
	
	
}