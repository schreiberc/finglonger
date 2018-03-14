<?php
/**
 * General use functions for service responses.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

/**
 * Print basic JSON response
 *
 * @param string $_status 
 * @param array $_optionalArgs - used to clarify status of return data
 * @param bool $_exit Boolean - true to terminate execution with response
 * @return void
 */
function jan_generateResponse($_status, array $_optionalArgs, $_exit = true){

	$response = new StdClass();
	$response->status = $_status;
	
	foreach($_optionalArgs as $key=>$value){
		$response->$key = $value;
	}

	print_r(str_replace('\\/', '/', json_encode($response)));

	if($_exit === true) {
		exit;
	}
	
}
