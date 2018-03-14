<?php 
/**
 * General use functions for database access.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */


/**
 * Connect to a mysql database.
 *
 * @return mysqli connection
 * @return Boolean false on failure.
 */
function jan_getConnection(){
	
	try{
		//Include the connection creds
		require 'finglonger/setup/db_config.php';
	}
	catch (Exception $ex){
		return false;
	}
		
    //Validate all required fields are set
    if(!isset($fl_database_name) || !isset($fl_database_password) || !isset($fl_database_host) || !isset($fl_database_user)){
    	   return false;
    }
	
	$con = new mysqli($fl_database_host,$fl_database_user,$fl_database_password,$fl_database_name);
	
	return $con;
}


/**
 * Query a mysql database.
 * Gernerates a prepared statement from sql string and array of parameters.
 *
 * @param $_sql String sql statment
 * @param $_param Array statement parameters
 * @return mysqli connection
 * @return Boolean false on failure.
 */
function jan_stmtQuery2($_sql, $_param = null){

	$con = jan_getConnection();
	$sql = $_sql;
	$param = $_param;

	//Prepare the statment
	$stmt = $con->prepare($sql);

	if($stmt === false) {
		return false;
	}

	//Bind any parametes as passed in.
	if(!is_null($param)){

		$bindConditionalsDataTypes = '';
		$bindConditionalValues = array();

		try{
				
			foreach($param as $value){

				//Append the datatypes
				$bindConditionalsDataTypes .= jan_getVariableTypeStmtBind($value);
				$bindConditionalValues[] = $value;

			}
				
			$bindConditionals[] = $bindConditionalsDataTypes;
			$sizeOfBindConditionalValues = sizeof($bindConditionalValues);
			for($i=0; $i<$sizeOfBindConditionalValues; $i++){
				$bindConditionals[] = &$bindConditionalValues[$i];
			}
				
			call_user_func_array(array($stmt,'bind_param'), $bindConditionals);
		}
		catch (Exception $ex){
			return false;
		}

	}

	$result = $stmt->execute();

	if($result === false){
		return false;
	}

	//If the SQL Statement was an INSERT return the created key and we are done.
	if(substr(strtolower($_sql), 0, 6) == 'insert'){
		
		$result = array();
		$result['affected_rows'] = $stmt->affected_rows;
		$result['data'] = $stmt->insert_id;
		
		return $result;
	
	}

	//Process result return as an associative array
	$stmt->store_result();

	//If the result set is empty return an empty object.  The query was successful the request was just empty
	if($stmt->num_rows < 1){
		
		$result = array();
		$result['affected_rows'] = $stmt->affected_rows;
		$result['data'] = array();
		
		return $result;
	}

	//Pull the meta data to grab column names.  These are used as JSON labels.
	//The table associated with each field is used to determine when to move to a new depth within the JSON
	$meta = $stmt->result_metadata();

	$i = 0;

	while ($field = $meta->fetch_field()) {

		$var[] = $field->name;
		$fields[] = &$var[$i];

		$queryFieldInfoName[]=$field->name;
		$queryFieldInfoTable[]=$field->table;

		if(!isset($curFieldTable) || $curFieldTable != $field->table){
			$jsonArray[$field->table] = array();
			$curFieldTable = $field->table;
		}

		$i++;
	}

	try{
		call_user_func_array(array($stmt,'bind_result'),$fields);
	}
	catch (Exception $ex){
		return false;
	}

	$row = array();
	$data = array();
	
	while ($stmt->fetch()) {

		foreach($fields as $key=>$value){
			$row[$queryFieldInfoName[$key]] = $value;
		}

		$data[] = $row;
	}
	
	$result = array();
	$result['affected_rows'] = $stmt->affected_rows;
	$result['data'] = $data;
	
	return $result;

}

/**
 * Determine the data type of of passed in parameter for use in binding to prepared statements
 *
 * @param mixed $_var - variable to be typed.
 * @return string $bind - string representation of data type to be used to bind to a prepared statement.
 */
function jan_getVariableTypeStmtBind($_var){

	$type = gettype($_var);
	
	switch($type){

		case 'integer':
			$bind = 'i';
			break;

		case 'string':
			$bind = 's';
			break;

		case 'double':
			$bind = 'd';
			break;

		default:
			$bind = 's';
			break;

	}

	return $bind;
}
?>