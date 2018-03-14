<?php
/**
 * Class to manage automatically generated resources
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */
class Ship{
	
	private $con;  //Database connection
	private $dbLookup;  //Data store for auto genearation database resources
	
	const DELIMITER = "#@"; // Used in intermediate steps moving between the JSON and SQL statements.
	
	/**
	 * Constructor
	 * 
	 */
	public function __construct(){
		
		//Empty Constructor
		
	}
	
	/**
	 * Iniitialize the ship object.  
	 *
	 * @return bool true on success.
	 * @return bool false on failure.
	 */
	public function init(){
		
		//Initialize messaging strings
		$this->error = '';
		$this->message = '';
		
		//Lookup object used to process resource requests
		$this->dbLookup = $this->generateDBLookup();
		
		if($this->dbLookup === false){
			$this->error = 'SHIP '.__LINE__.' '.$this->error;
			return false;
		}
	
		return true;			
	}
	
	/**
	 * Reinitializes the dbLookup object to auto-generate resource requests.
	 * Available on refresh when a user has updated the database and are currently logged into
	 * the MOM panel.
	 *
	 * @return bool true on success.
	 * @return bool false on failure.
	 */
	public function re_init(){
		
		//Lookup object used to process resource requests
		$this->dbLookup = $this->generateDBLookup();
		
		if($this->dbLookup === false){
			$this->error = 'SHIP '.__LINE__.' '.$this->error;
			return false;
		}
		
		return true;
	}
	
	/**
	 * Return the dbLookup variable.
	 *
	 * @return array
	 */
	public function getDBLookup(){

		return $this->dbLookup;
	
	}
	
	/**
	 * Generate the dbLookup data object used to auto-generate resource requests.
	 *
	 * @return array on success.
	 * @return bool false on failure.
	 */
	private function generateDBLookup(){
		
		//Set a connection
		$this->con = jan_getConnection();
		
		if ($this->con->connect_error){
			$this->error = 'SHIP '.__LINE__.': Database connection failed: '.$this->con->connect_error;
			return false;
		}
		
		if($this->con === false){
			$this->error = 'SHIP '.__LINE__.': Problem in the pre-launch checklist. Unable to find any tables in the database';
			return false;
		}

		//Generate lookup object for auto generating queries
		$tableResult = $this->con->query('SHOW TABLES');
		$dbTables = array();
				
		if($tableResult == false){
			$this->error = 'SHIP '.__LINE__.': Problem in the pre-launch checklist. Unable to find any tables in the database';
			return false;
		}
		
		$dbLookup = array();
		
		require 'finglonger/Setup/db_config.php';
		
		$children = array();
		
		while ($tableRow = $tableResult->fetch_array( MYSQLI_ASSOC)) {
						
			$table = $tableRow['Tables_in_'.$fl_database_name]; //Database name is required as a parameter to make this query.
		
			//Get the columns from the table
			$columnResult = $this->con->query("SHOW COLUMNS FROM ".$table);
		
			if($columnResult === false){
				//Handle error - We can't run here
				$this->error = 'SHIP: '.__LINE__. ' Problem in the pre-launch checklist.  Unable to find table: '.$table.' columns';
				return false;
			}
		
			$fields = array();
		
			while($columnResultRow = $columnResult->fetch_array( MYSQLI_ASSOC)){
				//Add the column
				$fields[] = $columnResultRow['Field'];
			}
				
			$dbLookup[$table]['fields'] = $fields;
				
			//Determine if this resource has lockable records
			$dbLookup[$table]['lockable'] = 'false';
				
			if(in_array('locked', $dbLookup[$table]['fields'])){
				$dbLookup[$table]['lockable']  = 'true';
			}
				
			//Get the primary key of this table
			$tablePrimaryKeyResult = $this->con->query("SHOW KEYS FROM ".$table." WHERE Key_name = 'PRIMARY'");
		
			if($tablePrimaryKeyResult == false){
				$this->error = 'SHIP: '.__LINE__. ' Problem in the pre-launch checklist.  Unable to determine primary key of '.$table.' table.';
				return false;
			}
		
			$tablePrimaryKeyRow = $tablePrimaryKeyResult->fetch_array( MYSQLI_ASSOC);
			$tablePrimaryKey = $tablePrimaryKeyRow['Column_name'];
		
			$dbLookup[$table]['primary'] = $tablePrimaryKey;
			$dbLookup[$table]['function'] = 'default';
			
			//Get Foreign Key Data
			$createTableResult = $this->con->query('SHOW CREATE TABLE '. $table);
			
			if($createTableResult == false){
				$this->error = 'SHIP: '.__LINE__. ' Problem in the pre-launch checklist.  Unable to pull data on foreign keys of '.$table.' table.';
				return false;
			}
			
			$createTableData = $createTableResult->fetch_array( MYSQLI_ASSOC );
			
			//Check for error
			$createTableString = (isset($createTableData['Create Table'])) ? $createTableData['Create Table'] : 'Nope';
			
			//Some constants for pulling the string
			$foreignKeyOffset = 0;
			$createStringOffset = 0;
			
			while($createStringOffset < strlen($createTableString)){
								
				$foreignKeyStartPos = strpos($createTableString, 'FOREIGN KEY (`', $createStringOffset) ;
				
				//If we have a foreign key
				if($foreignKeyStartPos == false){	
					break;
				}
				
				$foreignKeyStartPos += strlen('FOREIGN KEY (`');
					
				//Find the end of the column name
				$foreignKeyLength = strpos($createTableString, '`)', $foreignKeyStartPos) - $foreignKeyStartPos;	
				$columnName = substr($createTableString, $foreignKeyStartPos, $foreignKeyLength);
				
				$foreignKeyRow['TABLE_NAME'] = $table;
				$foreignKeyRow['COLUMN_NAME'] = $columnName;
				
				//Find the referenced table name
				$referencedTableStartPos =  strpos($createTableString, 'REFERENCES `', $foreignKeyStartPos) + strlen('REFERENCES `');
				$referencedTableLength = strpos($createTableString, '`', $referencedTableStartPos) - $referencedTableStartPos;
				$referencedTable = substr($createTableString, $referencedTableStartPos, $referencedTableLength);
				
				$foreignKeyRow['REFERENCED_TABLE_NAME'] = $referencedTable;
				$foreignKeyRow['REFERENCED_COLUMN_NAM'] = $columnName;
				
				//Check to see if there is an ON DELETE CASCADE command which is used to determine n to n relationship
				//Set a lenght to check for the constraint
				$constraintsStartPos = $foreignKeyStartPos + $foreignKeyLength;
				$constraintsEndPos = strpos($createTableString, 'CONSTRAINT', $constraintStartPos) != false ? strpos($createTableString, 'CONSTRAINT', $constraintStartPos) : strpos($createTableString, ')', $constraintStartPos);
				$constraintsLength = $constraintsEndPos - $constraintsStartPos;
				$constraintsString = substr($createTableString, $constraintStartPos, $constraintsLength);
				if(strpos($constraintsString, 'ON DELETE RESTRICT') === false){
					$children[] = $foreignKeyRow;
				}
					
				$dbLookup[$foreignKeyRow['TABLE_NAME']]['foreign_keys'][] = $foreignKeyRow['REFERENCED_TABLE_NAME'];
				
				$createStringOffset = $referencedTableStartPos + $referencedTableLength;
			}	
			
		}
			
		//Is this a lookup table with the purpose to connect two tables in n-n relationship.
		//The need for access to the table itself is not needed and ignored.
		foreach($dbLookup as $tableName => $tableData){
			if(isset($dbLookup[$tableName]['foreign_keys'])){
				if(sizeof($dbLookup[$tableName]['foreign_keys'])>1){
					if(strpos($tableName, 'lookup') !== false){
						//We will assume this is a lookup table denote it as such.
						$dbLookup[$tableName]['function'] = 'lookup';
					}
				}
			}
		}
			
		//Find all children. We are defining children where there is probably a 1 to n relationship.
		foreach($children as $row){
		
			//Not an option assignment
			if(in_array($row['REFERENCED_TABLE_NAME'].','.$row['TABLE_NAME'],$optionMetaAssignmentsArr)){
				continue;
			}
		
			//Not a look up table
			if($dbLookup[$row['TABLE_NAME']]['function'] == 'lookup'){
				continue;
			}
			
			$dbLookup[$row['REFERENCED_TABLE_NAME']]['children'][] = $row['TABLE_NAME'];
		}
		
		return $dbLookup;
		
	}

	/**
	 * Check for server request method, call appropriate function to retrieve, patch
	 * or delete.  
	 *
	 * @return array $result - Data set returned from request.
	 * @return bool false on failure
	 */
	public function go(){
		
		//Set a connection
		$this->con = jan_getConnection();
		
		//Get the request method
		$requestMethod = $_SERVER['REQUEST_METHOD'];
	
		//Determine which function to call based  on request
		switch($requestMethod){
			
			case 'GET':
				
				$result = $this->retrieveData();
				
			break;
			
			case 'POST':
				
				$result = $this->patchData();
					
			break;
			
			case 'DELETE':	
				
				$result = $this->deleteData();
				
			break;
			
			default:
				
				$this->error = 'SHIP '.__LINE__.': Unable to recognize request method.';
				return false;
				
			break;
		
		}
		
		if($result === false){
			return false;
		}
			
		return $result;
	}
	
	/**
	 * Determine if a given resource is accessible by the ship object.
	 * The ship object can only access resources auto generated from the database structure.
	 * Essentially pulling tables and any children or lookups.
	 *
	 * @return void
	 */
	public function isShipResource($_resource_name){		
				
		return array_key_exists(str_replace('-', '_', $_resource_name), $this->dbLookup);
	
	}
	
	/**
	 * Return all available auto generated database resources as defined in the dbLookup
	 * 
	 * @return array
	 */
	public function getAllResources(){
		
		$resources = array_keys($this->dbLookup);
		$formattedResources = array();
		
		//TODO get rid of replacing '_' for '-' its pointless.
		$sizeOfResources = sizeof($resources);
		for($i = 0; $i < $sizeOfResources; $i++){	
			$formattedResources[] = str_replace('_', '-', $resources[$i]);
		}
		
		return $formattedResources;
	}
	
	/**
	 * Return all system defined auto generated database resourdes
	 *
	 * @return array
	 */
	public function getSystemResources(){
		
		//Check to see if any system tables exist.
		try{
			require ('setup/db_setup.php');
		}catch (Exception $ex){
			return array();
		}
		
		$formattedSystemTableNames = array();
		$sizeOfSystemTableNames = sizeof($fl_systemTableNames);
		
		for($i = 0; $i < $sizeOfSystemTableNames; $i++){
			$formattedSystemTableNames[] = str_replace('_', '-', $fl_systemTableNames[$i]);
		}
		
		return $formattedSystemTableNames;
		
	}
	
	/**
	 * Peform a GET operation
	 *
	 * @param stdClass $_resource_request
	 * @param stdClass $_parameters
	 * 
	 * @return bool false on failure
	 * @return array result set
	 */
	public function retrieveData($_parameters = null, $_resource_request = null){
		
		$this->con = jan_getConnection();
		
		//Generate the resource request or use one that has been passed in
		if(is_null($_resource_request)){
			$resourceRequest = jan_generateResourceRequest();
			
			if($resourceRequest === false){
				$this->error = 'SHIP: '.__LINE__. ' Could not generate the resource request: '.htmlspecialchars($this->con->error);
				return false;
			}
			
		}else{
			
			$resourceRequest = $_resource_request;	
		}
			
		//To facilitate retrieval of newly created records parameters can be injected into the resource request
		if(!is_null($_parameters)){		
			foreach($_parameters as $table => $id){
				$resourceRequest->keys[$table] = $id;
			}	
		}
		
		$resourceFunction = $this->dbLookup[$resourceRequest->parentResource]['function'];
		
		//Run the lookup resource check to generate SQL
		switch($resourceFunction){
		
			/*case 'lookup';
		
				$foreignTable1 = $this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][0];
				$foreignTable2 = $this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][1];
				$foreignTable1Primary = $this->dbLookup[$foreignTable1]['primary'];
				$foreignTable2Primary = $this->dbLookup[$foreignTable2]['primary'];
		
				//Generate the SQL from the foreign keys - Need to inject children if requested
				$sql = 'SELECT * FROM '.$foreignTable1.' LEFT JOIN '.$resourceRequest->parentResource.' ON '.$foreignTable1.'.'.$foreignTable1Primary.' = '.$resourceRequest->parentResource.'.'.$foreignTable1Primary.' LEFT JOIN '.$foreignTable2.' ON '.$foreignTable2.'.'.$foreignTable2Primary.' = '.$resourceRequest->parentResource.'.'.$foreignTable2Primary;
	
			break;*/
			 
			default:
		
				//Create the SQL statement if columns have been defined use those for the select otherwise use all.  Primary keys must always be pulled for recursive pull of child tables.
				$sql  ='SELECT * FROM '.$resourceRequest->parentResource;
				
				//Join any children tables
				if($resourceRequest->parameters['children'] == 'true'){	
				
					//Loop the children table if it exists
					if(isset($this->dbLookup[$resourceRequest->parentResource]['children'])){
						$sizeOfChildren = sizeof($this->dbLookup[$resourceRequest->parentResource]['children']);
						for($i = 0; $i < $sizeOfChildren; $i++){
							$sql.= ' LEFT JOIN '.$this->dbLookup[$resourceRequest->parentResource]['children'][$i];
							$sql.= ' ON '.$resourceRequest->parentResource.'.'.$this->dbLookup[$resourceRequest->parentResource]['primary'];
							$sql.= ' = '.$this->dbLookup[$resourceRequest->parentResource]['children'][$i].'.'.$this->dbLookup[$resourceRequest->parentResource]['primary'];
						}
					}
				}
								
				//Join any children tables
				if($resourceRequest->parameters['foreign-keys'] == 'true'){
				   
				    //Loop the children table if it exists
				    if(isset($this->dbLookup[$resourceRequest->parentResource]['foreign_keys'])){
				        $sizeOfForeignKeys = sizeof($this->dbLookup[$resourceRequest->parentResource]['foreign_keys']);
				        for($i = 0; $i < $sizeOfForeignKeys; $i++){			            				             
				            $sql.= ' LEFT JOIN '.$this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][$i];
				            $sql.= ' ON '.$resourceRequest->parentResource.'.'.$this->dbLookup[$this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][$i]]['primary'];
				            $sql.= ' = '.$this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][$i].'.'.$this->dbLookup[$this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][$i]]['primary'];
				        }
				    }
				}
		
			break;
		
		}
				
		//Remove children from the resource object after processing
		unset($resourceRequest->parameters['children']);
		unset($resourceRequest->parameters['foreign-keys']);
		
		$where = '';
		$bindConditionals = array();
		$bindConditionalsDataTypes = '';
		$bindConditionalValues = array();
		
		//Append any additional conditionals from parameters.  Exclude protected paramters
		foreach($resourceRequest->parameters as $columnName => $value){
			
			if(strlen($where) == 0){
				$where .= ' WHERE '.$columnName.' = ?';
			}else{
				$where .= ' AND '.$columnName.' = ?';
			}
			
			//Store the data type and value to bind to statment later.
			$bindConditionalsDataTypes .= jan_getVariableTypeStmtBind($value);
			$bindConditionalValues[] = $value;
			
		}
		
		if(sizeof($resourceRequest->keys) > 0 && strlen($where) == 0){
			$where = ' WHERE ';
		}else if(sizeof($resourceRequest->keys) > 0 && strlen($where) != 0){
		    $where .= ' AND';
		}
		
		//Append and ids to prepared statement
		foreach($resourceRequest->keys as $table => $id){
			
			if($table != 'users'){
				$where.= ' '.$table.'.'.$this->dbLookup[$table]['primary'].' = ? AND';
			}else{
				$where.= ' '.$resourceRequest->parentResource.'.'.$this->dbLookup['fl_users']['primary'].' = ? AND';	
			}
			
			//If its a number in string format convert it to an integer.
			if(is_numeric($id)){
				$id = intval($id);
			}
				
			$bindConditionalsDataTypes .= jan_getVariableTypeStmtBind($id);
			$bindConditionalValues[] = $id;
		}
		
		//Clean up the end of the string if we left a trailing AND from above		
		$where = rtrim($where, " AND");
		
		//Append the generated where statement
		$sql.=$where;
		
		//Prepare the statment
		$stmt = $this->con->prepare($sql);
		
		if(false===$stmt) {
			$this->error = 'SHIP: '.__LINE__. ' Retrieve Data SQL statement prepare failed: '.htmlspecialchars($this->con->error);
			return false;
		}
		
		//Set up the array to bind pass to user function to bind parameters.  
		//We know conditionals exist if either the bindConditionalsDataTypes is not empty or bindConditionalValues has size greater than 0
		if(sizeof($bindConditionalValues) > 0){
			
			$bindConditionals[] = $bindConditionalsDataTypes;
			$sizeOfBindConditionalValues = sizeof($bindConditionalValues);
			for($i=0; $i<$sizeOfBindConditionalValues; $i++){
				$bindConditionals[] = &$bindConditionalValues[$i];
			}
			
			
			
			try{
				call_user_func_array(array($stmt,'bind_param'), $bindConditionals);
			}
			catch (Exception $ex){
				$this->error = 'SHIP: '.__LINE__. ' Bind Conditionals Failed: '.$ex->getMessage();
				return false;
			}
			
			
		}
		
		//Execute
		$result = $stmt->execute();
		
		if(false===$result){
			$this->error = 'SHIP: '.__LINE__. ' Execute SQL statement prepare failed: '.htmlspecialchars($this->con->error);
			return false;
		}
		
		//Process result to JSON
		$stmt->store_result();
		 
		//If the result set is empty return an empty object.  The query was successful the request was just empty
		if($stmt->num_rows < 1){
			return array();
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
        	   $this->error = 'SHIP: '.__LINE__. ' Binding result to column names failed : '.$ex->getMessage();
            return false;
        }
        
        while ($stmt->fetch()) {
            	foreach($fields as $key=>$value){
        
                if(!isset($curTable) || $curTable == $queryFieldInfoTable[$key]){
        
                    $rowArray[$queryFieldInfoName[$key]]=$value;
        
                }else{
        
                    //Dump the created row into the JSON Precursor we will purge duplicates later.
                    array_push($jsonArray[$curTable], $rowArray);
        
                    //Create a new array to be storing stuff into
                    unset($rowArray);
                    $rowArray = array();
                    $rowArray[$queryFieldInfoName[$key]]=$value;
                }
                
                //Update the table we looked at
                $curTable = $queryFieldInfoTable[$key];
            	}
        	 
            	array_push($jsonArray[$curTable], $rowArray);
        }
        
        //The above created array will have duplicates in joins.  For example Table A with a 1-n relationship to Table B
        //may result in duplicates of the data stored in Table A array.  These are purged.
        foreach($jsonArray as $key=>$value){
            	$jsonArray[$key] = array_map("unserialize", array_unique(array_map("serialize", $jsonArray[$key])));
            	$jsonArray[$key] = array_values($jsonArray[$key]);
        }
        
        //The keys of the array are now all the tables/views in the current retrieve operation.
        $tables = array_keys($jsonArray);
         
        $jsonReadyArray = array();
        $jsonReadyArray[$tables[0]] = array();
        
        if(sizeof($jsonArray)==1){
        
        	   $jsonReadyArray[$tables[0]] = $jsonArray[$tables[0]];
        	 
        }else{
        
        	for($i=sizeof($jsonArray)-2; $i>-1; $i--){
        
        		$insertArray = array();
        
        		if(array_key_exists(0,$jsonArray[$tables[$i]])){
        			$fields = array_keys($jsonArray[$tables[$i]][0]);
        		}
        
        		$primary = $this->dbLookup[$tables[$i]]['primary'];
        
        		foreach($jsonArray[$tables[$i]] as  &$parentArray){
        			 
        			//Loop through each child table if it exists
        			for($j = $i+1; $j<sizeof($jsonArray); $j++){
        
        				foreach($jsonArray[$tables[$j]] as $childArray){
        
        					reset($childArray);
        					$childPrimary = key($childArray);
        
        					//Need to check on primary key
        					if(array_key_exists($primary, $childArray)){
        						if($childArray[$primary]==$parentArray[$primary]){
        							array_push($insertArray,$childArray);
        						}
        					}elseif(array_key_exists($childPrimary, $parentArray)){
        						if($childArray[$childPrimary]==$parentArray[$childPrimary ]){
        							array_push($insertArray,$childArray);
        						}
        					}
        				}
        
        				//Add in the created array to this level
        				if(sizeof($insertArray)>0){
        					$parentArray[$tables[$j]] = $insertArray;
        				}
        
        				unset($insertArray);
        				$insertArray = array();
        
        			}
        
        			if($i==0){
        				array_push($jsonReadyArray[$tables[0]], $parentArray);
        			}
        		}
        	   }
        }
        
        /*$sizeOfJSONReadyArray = sizeof($jsonReadyArray[$resourceRequest->parentResource]);
        for($i = 0; $i < $sizeOfJSONReadyArray; $i++){
            
            $object = $jsonReadyArray[$resourceRequest->parentResource][$i];
            
            foreach($object as $key => $value){
                
                if(is_array($value) && sizeof($value) == 1){
                    
                    $sizeOfValue = sizeof($value);
                    for($j = 0; $j < $sizeOfValue; $j++){
                   
                        foreach($value[$j] as $key2 => $value2){

                            $jsonReadyArray[$resourceRequest->parentResource][$i][$key2] = $value2;
                        }
                    
                    }
                    
                    unset($jsonReadyArray[$resourceRequest->parentResource][$i][$key]);
                    
                }
                
                
            }
            
            
        }*/
        
        //Additional function-based processing
        /*if($resourceFunction == 'lookup'){
        	   
            	$sizeOfTopLevelParent = sizeof($jsonReadyArray[$foreignTable1]);
            	for($i = 0; $i<$sizeOfTopLevelParent; $i++){
            		
            		if(isset($jsonReadyArray[$foreignTable1][$i][$resourceRequest->parentResource])){
            			
            			$lookupData = $jsonReadyArray[$foreignTable1][$i][$resourceRequest->parentResource];
            			$jsonReadyArray[$foreignTable1][$i][$foreignTable2] = array();
            
            			for($j = 0; $j<sizeof($lookupData); $j++){
            				
            				//Search the object
            				foreach($lookupData[$j] as $key => $data){
            					
            					if($key != $this->dbLookup[$resourceRequest->parentResource]['primary'] && $key != $this->dbLookup[$foreignTable1]['primary'] && $key != $this->dbLookup[$foreignTable2]['primary'] && $key != $foreignTable2){
            						$lookupData[$j][$foreignTable2][0][$key] = $data;
            				
            					}
            				}
            				 
            				$jsonReadyArray[$foreignTable1][$i][$foreignTable2][] = $lookupData[$j][$foreignTable2][0];
            			}
            			 
            			unset($jsonReadyArray[$foreignTable1][$i][$resourceRequest->parentResource]);
            		}
            	}
        }*/
        
        //If the primary key was in the conditionals we are going to return just a single element not in an array.  
        if(array_key_exists($resourceRequest->parentResource, $resourceRequest->keys)){
            return $jsonReadyArray[$resourceRequest->parentResource][0];	
        }
        
        //If we are dealing with a lookup resource return just a single element.
        //if($resourceFunction == 'lookup'){
        	//   return $jsonReadyArray[$this->dbLookup[$resourceRequest->parentResource]['foreign_keys'][0]][0];
        //}
        
        return $jsonReadyArray[$resourceRequest->parentResource];
	}
	
	/**
	 * Perform a delete operation
	 *
	 * @return void
	 */
	public function deleteData($_resource_request = null){
		
		$this->con = jan_getConnection();
		
		if(is_null($_resource_request)){
			$resourceRequest = jan_generateResourceRequest();
			
			if($resourceRequest === false){
				$this->error = 'SHIP: '.__LINE__. ' Could not generate the resource request';
				return false;
			}
			
		}else{
			$resourceRequest = $_resource_request;
		}
		
		//Construct the delete query
		$sql = 'DELETE FROM '.$resourceRequest->parentResource;
		
		//Make sure we have at one key
		if(sizeof($resourceRequest->keys) < 1){
			$this->error = 'SHIP: '.__LINE__. ' No keys were found.';
			return false;
		}
		
		$where = ' WHERE ';
	
		//Store for statemet data
		$bindConditionalsDataTypes = '';
		$bindConditionalValues = array();
	
		//Loop through the resource keys to generate the delete
		foreach($resourceRequest->keys as $table => $value){
			$where.= $this->dbLookup[$table]['primary'].' = ? AND';
	
			//Store the data type and value to bind to statment later.
			$bindConditionalsDataTypes .= jan_getVariableTypeStmtBind($value);
			$bindConditionalValues[] = $value;
		}
	
		//Trim the final AND
		$where = substr($where, 0, -4);
		
		//Ignore locked records 
		if($this->dbLookup[$resourceRequest->parentResource]['lockable'] == 'true'){
			$where .= " AND locked =  'false'";
		}
		
		//Append to sql
		$sql.=$where;
		
		//Prepare the statment
		$stmt = $this->con->prepare($sql);
		
		if($stmt == false) {
			$this->error = 'SHIP: '.__LINE__. ' Delete Data SQL statement prepare failed: '.$stmt->error;
			return false;
		}
		
		if(sizeof($bindConditionalValues) > 0){
						
			$bindConditionals[] = $bindConditionalsDataTypes;
			$sizeOfBindConditionalValues = sizeof($bindConditionalValues);
			for($i=0; $i<$sizeOfBindConditionalValues; $i++){
				$bindConditionals[] = &$bindConditionalValues[$i];
			}
		
			
	
			try{
				call_user_func_array(array($stmt,'bind_param'), $bindConditionals);
			}
			catch (Exception $ex){
				$this->error = 'SHIP: '.__LINE__. ' Bind Conditionals Failed: '.$ex->getMessage();
				return false;
			}
	
			
		}
	
		
		//Execute
		$result = $stmt->execute();
		
		if($result === false){
			$this->error = 'SHIP: '.__LINE__. ' Delete Data SQL statement execution failed: '.htmlspecialchars($this->con->error);
			return false;
		}
	
		return array('records_deleted'=>$stmt->affected_rows);
	}
	
	/**
	 * Perform a delete operation
	 *
	 * @return void
	 */
	public function patchData($_resource_request = null){
		
		//Generate the resource request or process one that was passed in
		if(is_null($_resource_request)){
			$resourceRequest = jan_generateResourceRequest();
		}else{
			$resourceRequest = $_resource_request;
		}
		
		//Make sure we have some posted data
		if(!isset($resourceRequest->postData)){
			$this->error  = 'SHIP '.__LINE__.' : No data was posted to resource';
			return false;
		}
		
		$preProcessData = $resourceRequest->postData;
		
		//Decoded the JSON data post for processing
		$preProcessData = json_decode($preProcessData, true);
		
		if(is_null($preProcessData)){
			$this->error  = 'SHIP '.__LINE__.' : Posted data not correctly formatted.';
			return false;
		}
				
		//Sort the data object.
		sortJSONByWeight($preProcessData);
		
		$resource = $resourceRequest->parentResource;
		
		//Append the resource name into object for processing down the line.
		switch($this->dbLookup[$resource]['function']){
				
			case 'lookup':
	
				$data[$this->dbLookup[$resource]['foreign_keys'][1]][] = $preProcessData;
					
				$lookupTablePost = array();
				$childLookupTable = $this->dbLookup[$resource]['foreign_keys'][0];
				$parentLookupTable = $this->dbLookup[$resource]['foreign_keys'][1];
					
				break;
					
			case 'default':
	
				$data[$resource][] = $preProcessData;
	
				break;
					
			default:
	
				$this->error = 'SHIP: '.__LINE__.' Resource function not available.';
				return false;
	
				break;
					
		}
		
		
		//Get the keys (if any are available) from  the uri
		$uri = $resourceRequest->uri;
	
		$sqlStack = new Stack();
	
		//Construct individual SQL actions recursively picks apart JSON string into a number of inserts each stored as a Std Class
		
		$sqlStack->push($this->generateInsertData(json_encode($data), '', $sqlStack));
		
		//If the stack is empty or every value within the stack is null we don't have a valid insert.  JSON could be of valid format but empty.
		if(!$sqlStack->isValid()){
			$this->error = 'SHIP '.__LINE__.' : Invalid stack';
			return false;
		}
		
		//Used to rollback any executed inserts if we fail along the way.  Records will be deleted if entire object can not be inserted into the database.
        $rollBackInsert = array();
		$queryParams = array();
		
		//=====================================================================
		//DELETE
		//=====================================================================
		
		//Find the primary key of the parent resource
		if(isset($data[$resource][0][$this->dbLookup[$resource]['primary']])){
				
			//$data[$resource][0][$this->dbLookup[$resource]['primary']]
				
			$parentKey = $data[$resource][0][$this->dbLookup[$resource]['primary']];
				
			//Construct resource request
			$currentRecordResourceRequest = new stdClass();
			$currentRecordResourceRequest->uri = array($resource);
			$currentRecordResourceRequest->keys = array($resource=>$parentKey);
			$currentRecordResourceRequest->parameters = array('children'=>TRUE);
			$currentRecordResourceRequest->fullResource = $resource;
			$currentRecordResourceRequest->parentResource = $resource;
			$currentRecordResourceRequest->postData = null;
			$currentRecordResourceRequest->method = 'GET';
			
			$currentRecordStateResult = $this->retrieveData(null,$currentRecordResourceRequest);	
			
			$currentRecordStateResultArr = array();
			
			$currentRecordStateResultArr[$resource][] = $currentRecordStateResult;
			
			$currentRecordState = json_encode($currentRecordStateResultArr, TRUE);
			
			$dbSqlStack = new Stack();
			$dbSqlStack->push($this->generateInsertData($currentRecordState , '', $dbSqlStack));
				
			while (!$dbSqlStack->isEmpty()){
				
				$curRecord = $dbSqlStack->pop();
				$postedStack = clone $sqlStack;
				
				if(!is_null($curRecord)){
					
					$delete = true;
					
					$v1 = $curRecord->primaryKeyName;
					$v2 = $curRecord->primaryKeyValue;
					
					while (!$postedStack->isEmpty()){
						
						$postedRecord = $postedStack->pop();
						
						if(!is_null($postedRecord)){
							
							if ($v1 == $postedRecord->primaryKeyName && $v2 == $postedRecord->primaryKeyValue) {
								$delete = false;
								break;
							}
						}
					}
					
					if($delete){
						
						//Prep resource request
						$deleteRecordResourceRequest = new stdClass();
						$deleteRecordResourceRequest->uri = array(str_replace('_', '-', $curRecord->table));
						$deleteRecordResourceRequest->keys = array($curRecord->table=>$curRecord->primaryKeyValue);
						$deleteRecordResourceRequest->parameters = array('children'=>TRUE);
						$deleteRecordResourceRequest->fullResource = str_replace('_', '-', $curRecord->table);
						$deleteRecordResourceRequest->parentResource = $curRecord->table;
						$deleteRecordResourceRequest->postData = null;
						$deleteRecordResourceRequest->method = 'DELETE';
						
						if($this->deleteData($deleteRecordResourceRequest) === false){
							$this->error.= 'SHIP '.__LINE__.' : ';
							return false;
						}
						
					}
					
					
				}
				
			
			}
		}
		
		//=====================================================================
		//DELETE
		//=====================================================================

		while (!$sqlStack->isEmpty()){
	
			unset($queryParams);
			$queryParams= array();
	
			$curInsert = $sqlStack->pop();
	
			//If we have been passed a key add it to the createdKeys array - lets rename this array
			if(!is_null($curInsert)){
	 
				//Append an ID to the insert object if a relationship exists this key is either passed in as part of the resource
				//or is generated on a previous insert
				if(isset($this->dbLookup[$curInsert->table]['foreign_keys'])){
				
					$sizeOfCurTableForeignKeys = sizeof($this->dbLookup[$curInsert->table]['foreign_keys']);
					
					for($i = 0; $i<$sizeOfCurTableForeignKeys; $i++){
					
						$foreignKeyTable = $this->dbLookup[$curInsert->table]['foreign_keys'][$i];
						$foreignKeyColumn = $this->dbLookup[$foreignKeyTable]['primary'];
						$foreignKey = array($foreignKeyTable,$foreignKeyColumn,'i');
		
						//If no key has been created but one is required it should have been passed in the uri.
						//Need to loop through the foreign keys.
						if(isset($createdKeys[$foreignKey[0]])){
							$curInsert->values.=self::DELIMITER.$createdKeys[$foreignKey[0]];
							$curInsert->columns.=",".$foreignKey[1];
							$curInsert->dataTypes.=$foreignKey[2];
						}
					}
					
	
				}
	
				$values = explode(self::DELIMITER, $curInsert->values);
				$numberCols = sizeof(explode(",", $curInsert->columns));
				$paramHolders = "";
	
				for($i = 0; $i<$numberCols; $i++){
					$paramHolders .= "?,";
				}
	
				//Trim the trailing comma from the generated param holder string
				$paramHolders = rtrim($paramHolders, ",");
	
				//OR UPDATE IF A PRIMARY KEY EXISTS IN THE CONSTRUCT
				$columns = explode(',', $curInsert->columns);
	
				$isUpdate = false;
				
				//Determine if we are inserting or updating the record.  If a primary key is present in the construct its an update and we move the primary key to the where
				if(in_array($this->dbLookup[$curInsert->table]['primary'], $columns)){
					$isUpdate = true;
						 
					//This is an update create the sql
					$sql = "UPDATE ".$curInsert->table.' SET ';
						 
					for($m=0; $m<sizeof($columns); $m++){
	
						if($columns[$m] != $this->dbLookup[$curInsert->table]['primary']){
							$sql.= $columns[$m].'=?,';
						}else{
							$primaryIndex = $m;
						}
					}
		 
					//Remove trailing comma
					$sql = rtrim($sql, ',');
		 
					//Append conditional
					$sql .= ' WHERE '.$this->dbLookup[$curInsert->table]['primary'].'=?';
		 			
					//Determine if this is a locked record.
					if($this->dbLookup[$curInsert->table]['lockable'] === 'true'){
						$sql.= " AND locked = 'false'";
					}
					
					//Adjust the value and data type information for an update
					$dataTypesArr = str_split($curInsert->dataTypes);
					$dataTypesUpdateOrder = '';
		 
					$valuesOrderUpdate = array();
		 
					for($k=0; $k<sizeof($dataTypesArr); $k++){
						if($k != $primaryIndex){
							$dataTypesUpdateOrder.=$dataTypesArr[$k];
							$valuesOrderUpdate[] = $values[$k];	
						}	
					}
	 
					$valuesOrderUpdate[] = $values[$primaryIndex];
					$dataTypesUpdateOrder.=$dataTypesArr[$primaryIndex];
	 
					$values = $valuesOrderUpdate;
					$curInsert->dataTypes = $dataTypesUpdateOrder;
	
				}else{
					$sql = "INSERT INTO ".$curInsert->table."(".$curInsert->columns.") VALUES(".$paramHolders.")";
				}
				
				$this->con = jan_getConnection();
				
				//Prepare the sql statment
				$stmt = $this->con->prepare($sql);
				
				//Ensure prepare succeeded
				if ($stmt === false) {
	
					//Error out.
					$this->error = 'SHIP: '.__LINE__.' '.$this->con->error.' '.$sql;
					return false;
				}
	
				//Set up parameters to bind.
				$queryParams[] = $curInsert->dataTypes;
	
				foreach ($values as $key => $value){
					$queryParams[] = &$values[$key];
				}
	
				//Bind parameters to statement
				
	
				try{
					call_user_func_array(array($stmt,'bind_param'), $queryParams);
				}
				catch (Exception $ex){
					//Error out.
					$this->error = 'SHIP: '.__LINE__.' '.$ex->getMessage();
					//return false;		 
				}
	
				
	
				$result = $stmt->execute();
	
				if ($result === false) {
		
					//Error out.
					$this->error = 'SHIP: '.__LINE__.' '.$sql." ".$this->con->error;
					return false;
	
				}
	
				//Store previous keys - This is used for appending an id to a child record and unwinding a failed insert.  Key was either creted
				$createdKeys[$curInsert->table] = $this->con->insert_id;
	
				if($isUpdate){
					$createdKeys[$curInsert->table] = $values[sizeof($values) - 1];
				}
	
				if(isset($lookupTablePost)){
					$parentTableIndex = array_search($parentLookupTable, $uri);
	
					//If the parent object id exists in the URI add it to the lookupPost
					if(is_numeric($parentTableIndex)){
						$lookupTablePost[$parentLookupTable] = $uri[$parentTableIndex + 1];	
					}
	
					//Handle nesting of multiple parent level inserts look up is based of a 1-n relationship with a single
					//parent possibly having numerous children defined in the lookup table.
				
					if($isUpdate){
						$primaryKeyValue = $values[sizeof($values)-1];	 
					}else{
						$primaryKeyValue = $this->con->insert_id;
					}
	
					if(isset($lookupTablePost[$parentLookupTable]) && $curInsert->table == $parentLookupTable){
						$allLookupTablePosts[] = $lookupTablePost;
						$lookupTablePost = array();
					}else if(isset($lookupTablePost[$curInsert->table])){
						$lookupTablePost[$curInsert->table] .=  ",".$primaryKeyValue;
					}else{
						$lookupTablePost[$curInsert->table] = $primaryKeyValue;
					}
				}
	
				//Store created keys and table pairs used to unwind inserts if we crap out part way through
				$table = $curInsert->table;
				$column = $this->dbLookup[$curInsert->table]['primary'];
				$value = $this->con->insert_id;
	
				$rollBackInsert[] = array("table"=>"$table", "column"=>"$column", "value"=>$value);
	
				$stmt->close();
			}
		}
	
		if(isset($lookupTablePost)){
			if(sizeof($lookupTablePost > 1)){
				$allLookupTablePosts[] = $lookupTablePost;
				$this->insertLookupData($allLookupTablePosts,$uri[0]);
			}
		}
	
		$keyColumnNames = array();
		//Pull the indecies from the created keys
		foreach($createdKeys as $key=>$value){
		$keyColumnNames[] = $key;
	}
		//Need to append an id of the object just created
		return $this->retrieveData(array($keyColumnNames[0] => reset($createdKeys)), $resourceRequest);
	}
	
	
	/**
	* Recursively breaks down JSON object into a series of StdClass objects storing required
	* information for each insert.  Columns, Values, DataTypes and Tables.  Helper function for
	* patchData().
	*
	* @param JSON $_data JSON string to parse
	* @param string $_table current table being inserted into
	* @param Stack $_stack current stack all insert objects are being pushed into
	* @return StdClass object containing information to run a single insert or null if no object was created
	*/
	private function generateInsertData($data=null, $table = null, Stack $stack, $_foreignKeyName = null, $_foreignKeyValue = null, $_lookUpKeyName = null, $_lookUpKeyValue = null){
	 
		$newTable = $table;
		$foreignKeyName = $_foreignKeyName;
		$foreignKeyValue = $_foreignKeyValue;
		$lookUpKeyName = $_lookUpKeyName;
		$lookUpKeyValue = $_lookUpKeyValue;
	
		if(!is_array($data)){
			$obj = json_decode($data, true);
		}else{
			$obj=$data; 
		};
		
		$sqlObject = new StdClass();
		$sqlObject->columns = "";
		$sqlObject->values = "";
		$sqlObject->dataTypes = "";
		
		//Loop through the JSON object to create object for SQL statement creation.
		foreach($obj as $key=>$element){
		
			if(!is_array($element)){
		
				//Ignore primary keys
				$sqlObject->dataTypes .= jan_getVariableTypeStmtBind($element);
				$sqlObject->columns .= $key.",";
		
				if($element == ""){
					$sqlObject->values  .= " ".self::DELIMITER;
				}else{
					$sqlObject->values  .= $element.self::DELIMITER;
				}
		
			//Pass down the primary key as foreign key to the object below.  If the previous object had no foreign key
			//that object has not been inserted and it must be grabbed after insert
			 
			//Add in some error checking if we are looking for a primary key that does not exist.
			if($this->dbLookup[$newTable]['primary'] == $key){
				$foreignKeyName = $key;
				$foreignKeyValue = $element;
				$primaryKeyName = $key;
				$primaryKeyValue = $element;
		
			}
		
			}else{ //We have hit an array
		
				if((strlen($key)>1 && gettype($key)=="string") || ($key != $newTable && (strlen($key)>1 && gettype($key)=="string"))){$newTable = $key;}
					$stack->push($this->generateInsertData($element, $newTable, $stack, $foreignKeyName, $foreignKeyValue, $lookUpKeyName, $lookUpKeyValue));
				}
		
		}
		
		if ($sqlObject->values!=""){
		
			$sqlObject->table = $table;
			$sqlObject->columns = rtrim($sqlObject->columns, ',');
			$sqlObject->values = rtrim($sqlObject->values, self::DELIMITER);
			$sqlObject->foreignKeyName = $foreignKeyName;
			$sqlObject->foreignKeyValue = $foreignKeyValue;
			$sqlObject->primaryKeyName = $foreignKeyName;
			$sqlObject->primaryKeyValue = $foreignKeyValue;
			$sqlObject->lookUpKeyName = $lookUpKeyName;
			$sqlObject->lookUpKeyValue = $lookUpKeyValue;
		
			//Sort into alphabetical order before returning needed for comparison purposes
			$indexRef = explode(",",$sqlObject->columns);
			$columnSort = $indexRef;
			sort($columnSort);
		
			$dataTypesRef = str_split($sqlObject->dataTypes);
			$dataTypesSort = array_fill(0, sizeof($indexRef), "null");
		
			$valuesRef = explode(self::DELIMITER, $sqlObject->values);
			$valuesSort = array_fill(0, sizeof($indexRef), "null");
		
			//Match the values and data types by shifting position in their arrays.
			try{
				for($i = 0; $i<sizeof($indexRef); $i++){
		
					$curColumn = $indexRef[$i];
					$newIndex = -1;
		
					//Find the new position
					for($j = 0; $j<sizeof($indexRef); $j++){
		
						if($indexRef[$i]==$columnSort[$j]){
							$newIndex = $j;
						}
		
					}
		
					//Assign value to the new index
					$dataTypesSort[$newIndex] = $dataTypesRef[$i];
					$valuesSort[$newIndex] = $valuesRef[$i];
				}
		
				//Reset the values that have been reordered
				$sqlObject->columns = implode(",", $columnSort);
				$sqlObject->values = implode(self::DELIMITER, $valuesSort);
				$sqlObject->dataTypes = implode($dataTypesSort);
			}catch (Exception $ex){
 				$this->error = 'SHIP: '.__LINE__.' '.$ex->getMessage();
				return false;
			}
		
			return $sqlObject;
		
		}else{
			
			return null;
		
		}
	}
	
	/**
	* Posts look up data required by a givens post.
	*
	* @param $_data 
	* @param $resource
	* @return bool false on failure
	* @return bool true on success
	*/
	private function insertLookupData($_data, $_resource){

		$data = $_data;
	
		$lookupTable = $_resource;
		$parentLookupTable = $this->dbLookup[$_resource]['foreign_keys'][1];
		$parentLookupColumn = $this->dbLookup[$parentLookupTable]['primary'];
		$childLookupTable = $this->dbLookup[$_resource]['foreign_keys'][0];
		$childLookupColumn = $this->dbLookup[$childLookupTable]['primary'];
	 
		for($i = 0; $i < sizeof($data); $i++){

			$thisLookupPost = $data[$i];
			$parentColValue = $thisLookupPost[$parentLookupTable];

			//Check if there is a child that requires the lookup entry to be created.
			//We are checking here to accommodate a mixed data set where some parents have children and some don't
			if(isset($thisLookupPost[$childLookupTable])){
		
				$childColValuesArr = explode(",", $thisLookupPost[$childLookupTable]);

				for($j = 0; $j < sizeof($childColValuesArr); $j++){

					$sql = "INSERT INTO ".$lookupTable."(".$parentLookupColumn .",".$childLookupColumn.") VALUES(?,?)";
					$stmt = $this->con->prepare($sql);

					//Ensure prepare succeeded
					if ($stmt === false) {
						$error = htmlspecialchars($this->con->error);
						$this->error = 'SHIP: '.__LINE__.' Lookup Post Statement prepare failed: '.$error. " SQL: ".$sql;
						return false;
					}

					//Set up parameters to bind.
					$childColValue = $childColValuesArr[$j];

					if(is_numeric($childColValue)){
						$childColValue = intval($childColValue);
					}

					$queryParams[] = $this->jan_getVariableTypeStmtBind($parentColValue).$this->jan_getVariableTypeStmtBind($childColValue);
					$queryParams[] = &$parentColValue;//Only one value in the parent
					$queryParams[] = &$childColValuesArr[$j];

					//Bind parameters to statement
					

					try{
						call_user_func_array(array($stmt,'bind_param'), $queryParams);
					}catch (Exception $ex){
						//Error out.
						$this->error = 'SHIP: '.__LINE__.' Lookup Post Statement prepare failed: '.$ex->getMessage(). " SQL: ".$sql;
						return false;
					}

					
	 
					$result = $stmt->execute();

					if ($result === false) {
						$this->error = 'SHIP: '.__LINE__.' Lookup Post Statement prepare failed: '.$this->con->error. " SQL: ".$sql;
						return false;
					}

					unset($queryParams);

				}

			}
		}
		
		return true;	
	}
}
?>