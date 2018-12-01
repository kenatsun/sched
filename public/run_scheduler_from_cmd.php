<?php

ini_set('display_errors', 1);

# set the include path to be the top-level of the meals scheduling project
// set_include_path('.'. PATH_SEPARATOR . '../');
// set_include_path('/../');
// set_include_path('../' . PATH_SEPARATOR . '../public/');
define('SCHEDULER_DIR', 'auto_assignments');

require_once 'utils.php';
// require_once 'config.php';
require_once SCHEDULER_DIR . '/assignments.php';
require_once SCHEDULER_DIR . '/schedule.php';
require_once SCHEDULER_DIR . '/meal.php';
// require_once '../utils.php';
// require_once 'assignments.php';
// require_once 'schedule.php';
// require_once 'meal.php';

/*
 * Automated meals scheduling assignments
 * $Id: assignments.php,v 1.38 2014/11/03 02:16:29 willn Exp $ 
 */
$start = microtime(TRUE);

$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
if (0) debt("assignments.php: url =", $url);
if (0) debt("assignments.php: _GET =", $_GET);
if (0) debt("assignments.php: _POST =", $_POST);

$options = getopt('chijdsxwquD');
if (0) debt("assignments.php: options =", $options); 
if (empty($options)) {
	echo <<<EOTXT
Usage:
	-c  output as CSV
	-d  insert assignments into database
	-D  insert assignments into database, after deleting previous ones for this season
	-h	display schedule as an HTML table
	-i  output as SQL insert statements
	-j  output to json format
	-s  display schedule
	-u  only unfulfilled workers
	-w  display workers
	-x  run many combinations looking for a full allocation
	-q  quiet mode: don't display the results (used for debugging)

EOTXT;
	exit;
} 
else {
	runScheduler($options);
}


?>