<?php

/**
 * Class to setup and check session durations against max allowed
 * 
 * @author Cody Schreiber
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

class SessionDuration{

	//Default expiry
	const DEFAULT_SESSION_EXPIRY = 1440;

	private $sessionExpiry;

	public function __construct(){


	}
	/**
	 * Get the session expiry from the database. If doesn't exist use default
	 *
	 * @return 
	 */
	public function init(){

		$sessionExpirySQL = 'SELECT meta_name, meta_value FROM fl_setting_meta LEFT JOIN fl_settings
							ON fl_setting_meta.setting_id = fl_settings.setting_id
							WHERE fl_settings.setting_name = "Session Expiry"';
		
		$sessionExpiryResult = jan_stmtQuery2($sessionExpirySQL);
				
		if($sessionExpiryResult === false){
			
			$this->sessionExpiry = self::DEFAULT_SESSION_EXPIRY;			
			
		}else{
			
			if(isset($sessionExpiryData[0])){
				$sessionExpiryData = $sessionExpiryResult['data'];
				$sessionExpiryReturnData  = sizeof($sessionExpiryData);
				
				$this->sessionExpiry = $sessionExpiryData[0]['meta_value'];
			}else{
				$this->sessionExpiry = self::DEFAULT_SESSION_EXPIRY;			
			}
						
		}

	}

	/**
	 * Check against store default 
	 *
	 * @return 
	 */
	public function checkAgainstDefault($userSessionTimeStamp){

		if(($this->sessionExpiry+$userSessionTimeStamp) > time()){

			return true;

		}else{
			return false;
		}

	}
}