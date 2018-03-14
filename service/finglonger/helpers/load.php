<?php
/**
 * Simple file that includes all helper files found in this directory.
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

//System helper includes
require_once 'system/hash.php';
require_once 'system/operation_timer.php';
require_once 'system/sort.php';
require_once 'system/stack.php';
require_once 'system/version.php';

//User helper inclues
require_once 'user/database_operations.php';
require_once 'user/email.php';
require_once 'user/requests.php';
require_once 'user/response.php';
require_once 'user/session_duration.php';


?>