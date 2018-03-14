<?PHP
/**
 * General use hashing function.  Currently using a simple md5 will be replaced with something more robust v2.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */


/**
 * Simple hash using md5.  
 *
 * @param String $_password string to be hashed.
 * @return Hashed string
 */
function create_hash($_password){

	return md5($_password); 

}

/**
 * Validate md5 hashed string.
 *
 * @param String $_password string to be hashed.
 * @param String $_correct_hash the correct hash.
 * @return Hashed string
 */
function validate_password($_password, $_correct_hash){
	
	return md5($_password) == $_correct_hash;

}

/**
 * Generate a random alphanumeric string
 *
 * @param String $_length length of string to generate.
 * @return Random alphanumeric string.
 */
function jan_generateRandomString($length = 6) {
	$validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ1234567890";
	$validCharNumber = strlen($validCharacters);

	$result = "";

	for ($i = 0; $i < $length; $i++) {
		$index = mt_rand(0, $validCharNumber - 1);
		$result .= $validCharacters[$index];
	}

	return $result;
}

?>