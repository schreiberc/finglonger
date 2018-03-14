<?php
/**
 * General use sorting functions.  There is only one right now.  There will be more.  The world is pretty messy.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

/**
 * Sorts JSON Data such that nested objects are below literals.  
 *
 * @param string &$_data JSON string containing data
 */
function sortJSONByWeight(&$_data){

	foreach ($_data as $key => $value){

		if(is_object($value)){

			sortJSONByWeight($_data[$key]);

		}elseif(is_array($value)){

			sortJSONByWeight($_data[$key]);
			array_multisort($_data[$key]);

		}else{

			//Do Nothing

		}

	}
}