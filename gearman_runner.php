#!/usr/bin/php
<?php

/**
 * Ensure that people can't access this from a web-server
 */
if(isset($_SERVER['HTTP_HOST'])) {
	echo "cli-script.php can't be run from a web request, you have to run it on the command-line.";
	die();
}

/**
 * Identify the cli-script.php file and change to its container directory, so that require_once() works
 */
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
chdir(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/framework');

/**
 * Process arguments and load them into the $_GET and $_REQUEST arrays
 * For example,
 * sake my/url somearg otherarg key=val --otherkey=val third=val&fourth=val
 *
 * Will result int he following get data:
 *   args => array('somearg', 'otherarg'),
 *   key => val
 *   otherkey => val
 *   third => val
 *   fourth => val
 */
if(isset($_SERVER['argv'][2])) {
    $args = array_slice($_SERVER['argv'],2);
    if(!isset($_GET)) $_GET = array();
    if(!isset($_REQUEST)) $_REQUEST = array();
    foreach($args as $arg) {
       if(strpos($arg,'=') == false) {
           $_GET['args'][] = $arg;
       } else {
           $newItems = array();
           parse_str( (substr($arg,0,2) == '--') ? substr($arg,2) : $arg, $newItems );
           $_GET = array_merge($_GET, $newItems);
       }
    }
  $_REQUEST = array_merge($_REQUEST, $_GET);
}

// Set 'url' GET parameter
if(isset($_SERVER['argv'][1])) {
	$_REQUEST['url'] = $_SERVER['argv'][1];
	$_GET['url'] = $_SERVER['argv'][1];
}

/**
 * Include SilverStripe's core code
 */
require_once("core/Core.php");

global $databaseConfig;

// We don't have a session in cli-script, but this prevents errors
$_SESSION = null;

// Connect to database
require_once("model/DB.php");
DB::connect($databaseConfig);

// Get the request URL from the querystring arguments

$_SERVER['REQUEST_URI'] = BASE_URL;

// Direct away - this is the "main" function, that hands control to the apporopriate controller
DataModel::set_inst(new DataModel());


$name = preg_replace("/[^\w_]/","",Director::baseFolder() .'_handle');

$function = function ($args) {
	echo date('[Y-m-d H:i:s]') . " - running gearman job with args: $args...";
	
	$args = @unserialize($args);
	$injector = Injector::inst();
	try {
		$svc = $injector->get('GearmanService');
		$svc->handleCall($args);
	} catch (Exception $ex) {
		echo "Could not run task: " . $ex->getMessage();
		print_r($ex->getTrace());
	}
	
	echo "Job complete, memory used " . memory_get_usage() . "\n";
	return;
};

$worker = new \Net\Gearman\Worker();
$worker->addServer();
$worker->addFunction('silverstripe_handler', $function);
$worker->work();
