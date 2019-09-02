<?php
// if (0) deb("start.php");
	
session_start();

require_once 'git_ignored.php';
require_once 'utils.php';
require_once 'display/includes/header.php';  // Has to go before constants, globals, & config or its <head> stuff ends up in <body>
require_once 'constants.inc';
require_once 'globals.php';
require_once 'config.php'; 
require_once 'breadcrumbs.php';
require_once 'headline.php'; 

// /* 
// Print debug data to the console
// */

// function deb($label, $data=NULL) {
	// $print_data = $data ? "\n" . print_r($data, TRUE) : "";
	// console_log("*****\n" . $label . $print_data . "\n");
// }

// function console_log($output, $with_script_tags = true) {
	// // From https://stackify.com/how-to-log-to-console-in-php/
  // $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
	// if ($with_script_tags) {
		// $js_code = '<script>' . $js_code . '</script>';
	// }
	// echo $js_code; 
// }
		


?>