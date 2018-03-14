<?php 
/**
 * Checks for existing Finglonger session.  Instatiates one if it does not exist.
 *
 * @author Colin Sharp
 * @version 1.0.0
 */

require_once('finglonger/finglonger.php');

session_start();

//Constants - TODO consider moving this to another file.  Don't consider we should do it.
define('ABS_PATH', __DIR__);

//Are we setting up Finglonger?
if(isset($_GET['setup']) && $_GET['setup']==true){
    
    if(!isset($_SESSION['finglonger'])){
        //Run finglonger in Setup Mode.
        $finglonger = new Finglonger();
        $_SESSION['finglonger'] = $finglonger;
        $_SESSION['finglonger']->initSetup();
        $_SESSION['finglonger']->setup();
        
    }else if($_SESSION['finglonger']->isSetupInitialized() == false){
        
        $_SESSION['finglonger']->initSetup();
        $_SESSION['finglonger']->setup();
        
    }else{
        
        $_SESSION['finglonger']->setup();
        
    }
 
    exit;
}

//Are we just running
if(!isset($_SESSION['finglonger'])){
	
	$finglonger = new Finglonger();
	$_SESSION['finglonger'] = $finglonger;
	$_SESSION['finglonger']->init();
	$_SESSION['finglonger']->go();
	
}else if($_SESSION['finglonger']->isInitialized() === false){
	
	$_SESSION['finglonger']->init();
	$_SESSION['finglonger']->go();

}else{
	
	$_SESSION['finglonger']->go();

}
?>