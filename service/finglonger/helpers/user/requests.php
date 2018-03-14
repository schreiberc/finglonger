<?php
/**
 * General use functions for service requests.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

/**
 * Generate a stdClass object containing information about a resource request in the following form.
 * 
 * (example has been json encoded for ease of reading)
 * 
    {
    "uri": "fl-users\/1",
    "keys": {
        "fl_users": "1"
    },
    "parameters": {
        "children": true
    },
    	"fullResource": "fl-users",
    	"parentResource": "fl_users",
    	"postData": null,
   	 	"method": "GET"
	}
 *
 * @return stdClass 
 * 
 */
function jan_generateResourceRequest(){
	
	//Extract the resource name, methods and ids from request
	$serviceBase = $_SESSION['finglonger']->serviceBase;
	$uri = substr($_SERVER['REQUEST_URI'], strlen($serviceBase)); 
	
	$resourceRequest = new stdClass();
	$resourceRequest->uri = $uri;
	$resourceRequest->keys = array();

	//Remove any parameters from the resource.  Store for later access.
	$requestedResourceParamters = array();
	$requestedResourceParamters['children'] = true;

	if(strpos($uri, '?')){
		$allParameterString = substr($uri, strpos($uri, '?') + 1);

		//Seperate parameters into array
		$allParameterArray = explode('&', $allParameterString);
		$sizeOfAllParameterArray = sizeof($allParameterArray);

		for($i=0; $i < $sizeOfAllParameterArray; $i++){

			$parameterArray = explode('=', $allParameterArray[$i]);
			$requestedResourceParamters[$parameterArray[0]] = urldecode($parameterArray[1]);

		}

		$uri = substr($uri, 0, strpos($uri, '?'));
	}

	$resourceRequest->parameters = $requestedResourceParamters;

	//Trim the final / off the uri if present
	$uri = rtrim($uri, '/');
	$uriArr = explode('/', $uri);
	$fullResource = '';
		
	//Loop through the requested resource and format any id from the form user/1/files to user/id/files
	$sizeOfUriArr = sizeof($uriArr);
	for($i=0; $i<$sizeOfUriArr; $i++){
		if(is_numeric($uriArr[$i])){
			$resourceRequest->keys[str_replace('-', '_', $uriArr[$i-1])] = $uriArr[$i];
			$fullResource .= 'id/';
		}else{
			$parentResource = $uriArr[$i];
			$fullResource .= $uriArr[$i].'/';
		}
	}
	
	//Trim the trailing id we don't need it and then adding secondary id resources is not needed	
	$resourceRequest->fullResource = rtrim(str_replace('/id', '', $fullResource), '/');
	$resourceRequest->parentResource = str_replace('-', '_', $parentResource);
	$resourceRequest->postData = null; //This will be filled with post data;
	$resourceRequest->method = $_SERVER['REQUEST_METHOD'];

	//Pull any post data that may exist
	if(isset($_POST['data'])){
		$resourceRequest->postData = $_POST['data'];	
	}
	
	return $resourceRequest;

}
