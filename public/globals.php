<?php
#ini_set('error_log', '/home/gocoho/error_log');

date_default_timezone_set('America/Detroit');

require_once('utils.php');
require_once('config.php');
require_once('git_ignored.php');

define('NON_RESPONSE_PREF', .5);
define('PLACEHOLDER', 'XXXXXXXX');
define('DOMAIN', '@gocoho.org');
define('HAS_CONFLICT', -1);

define('SUNDAY', 0);
define('MONDAY', 1);
define('TUESDAY', 2);
define('WEDNESDAY', 3);
define('THURSDAY', 4);
define('FRIDAY', 5);
define('SATURDAY', 6);

create_sqlite_connection();

// Identify the season this session will be working with.
if(isset($_COOKIE["season id"])) {			// if this user has selected a season to work on 
	$season_id = $_COOKIE["season id"];		// set season_id to that
} else {									// if not, get season_id from the current_season table (which only has one row)
	$season_id = sqlSelect("season_id", "current_season", "", "", (0), "current_season")[0]['season_id'];
	if (0) deb("globals.php: season_id = ", $season_id);
}
// global $season;
$season = sqlSelect("*", "seasons", "id = {$season_id}", "")[0];
if (0) deb("globals.php: season = ", $season);

// Assign season's attributes to constants.
// global $season_name;
$season_name = $season['name'];
if (0) deb("globals.php: season_name = {$season_name}");

if (0) deb("globals.php: season start_date = ", $season['start_date']);
if (0) deb("globals.php: year of season = ", date('Y', $season['start_date']));

define('SEASON_ID', $season_id);
if (0) deb("globals.php: SEASON_ID = " . SEASON_ID);

define('SEASON_TYPE', $season['season_type']);
if (0) deb("globals.php: SEASON_TYPE = " . SEASON_TYPE);

define('SEASON_START_YEAR', DateTime::createFromFormat("Y-m-d", $season['start_date'])->format("Y"));
if (0) deb("globals.php: SEASON_START_YEAR = " . SEASON_START_YEAR);

define('SEASON_END_YEAR', DateTime::createFromFormat("Y-m-d", $season['end_date'])->format("Y"));
if (0) deb("globals.php: SEASON_END_YEAR = " . SEASON_END_YEAR);

// Set path to assignments file.
global $json_assignments_file;
$json_assignments_file = 'results/' . SEASON_ID . '.json';

/**
 * Get the names of the days of the week.
 */
function get_days_of_week() {
	return [
		'Sun',
		'Mon',
		'Tue',
		'Wed',
		'Thu',
		'Fri',
		'Sat',
	];
}

global $pref_names;
$pref_names = array(
	0 => 'avoid',
	1 => 'ok',
	2 => 'prefer',
);

global $tester;
$tester = 0;
foreach ($pref_names as $k=>$prefname) {
	$tester++;
}

global $all_jobs;
$all_jobs = array();
$all_jobs['all'] = 'all';
$all_jobs += $mtg_jobs + $sunday_jobs + $weekday_jobs;
if (0) {deb('globals.php: all_jobs =', $all_jobs);}

global $all_cook_jobs;
global $all_clean_jobs;
foreach($all_jobs as $jid=>$name) {
	if ((stripos($name, 'cook') !== FALSE) ||
		(stripos($name, 'takeout orderer') !== FALSE)) {
		$all_cook_jobs[] = $jid;
	}
	if ((stripos($name, 'clean') !== FALSE) ||
		(stripos($name, 'Meeting night cleaner') !== FALSE)) {
		$all_clean_jobs[] = $jid;
	}
}

/**
 * Get the list of the weekdays where meals are served.
 */
function get_weekday_meal_days() {
	return [THURSDAY, SUNDAY]; 
}

global $mtg_nights;
// key = day of week, value = ordinal occurrence of day/week
$mtg_nights = array(
	WEDNESDAY => 1,
	MONDAY => 3,
);


// -------- function declarations here ------

function create_sqlite_connection() {
	global $dbh;
	global $db_is_writable;
	$db_is_writable = FALSE;

	// connect to SQLite database
	try {
		global $relative_dir;
		if (!isset($relative_dir)) { 
			$relative_dir = '';
		}
		else {
			$relative_dir .= '/';
		}

		$db_fullpath = getDatabaseFullpath();  // This function is in git_ignored.php because production & development use different databases
		$db_is_writable = is_writable($db_fullpath);
		$db_file = "sqlite:{$db_fullpath}";
		$dbh = new PDO($db_file);
		$timeout = 5; // in seconds
		$dbh->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
	}
	catch(PDOException $e) {
		echo "problem loading sqlite file [$db_fullpath]: {$e->getMessage()}\n";
		exit;
	}
}

// create the job IDs 'OR' clause
function get_job_ids_clause($prefix='') {
	global $all_jobs;

	if ($prefix != '') {
		$len = strlen($prefix);
		if (strrpos($prefix, '.') != ($len - 1)) {
			$prefix .= '.';
		}
	}

	$job_ids = array();
	foreach(array_keys($all_jobs) as $id) {
		if ($id == 'all') {
			continue;
		}

		$job_ids[] = "{$prefix}job_id={$id}";
	}
	return implode(' OR ', $job_ids);
}

function is_a_mtg_night_job($job_id) {
	global $mtg_jobs;
	return isset($mtg_jobs[$job_id]);
}

function is_a_sunday_job($job_id) {
	global $sunday_jobs;
	return isset($sunday_jobs[$job_id]);
}

function is_a_cook_job($job_id) {
	global $all_cook_jobs;
	return in_array($job_id, $all_cook_jobs);
}

function is_a_clean_job($job_id) {
	global $all_clean_jobs;
	return in_array($job_id, $all_clean_jobs);
}

function is_a_head_cook_job($job_id) {
	global $weekday_jobs;
	if (isset($weekday_jobs[$job_id]) &&
		strstr($weekday_jobs[$job_id], 'head cook')) {
		return TRUE;
	}

	global $sunday_jobs;
	if (isset($sunday_jobs[$job_id]) &&
		strstr($sunday_jobs[$job_id], 'head cook')) {
		return TRUE;
	}

	global $mtg_jobs;
	if (isset($mtg_jobs[$job_id]) &&
		strstr($mtg_jobs[$job_id], 'takeout orderer')) {
		return TRUE;
	}

	return FALSE;
}

function is_a_hobarter($worker) {
	$hobarters = get_hobarters();
	return in_array($worker, $hobarters);
}

function get_job_name($job_id) {
	global $weekday_jobs;
	if (isset($weekday_jobs[$job_id])) {
		return $weekday_jobs[$job_id];
	}

	global $sunday_jobs;
	if (isset($sunday_jobs[$job_id])) {
		return $sunday_jobs[$job_id];
	}

	global $mtg_jobs;
	if (isset($mtg_jobs[$job_id])) {
		return $mtg_jobs[$job_id];
	}

	return '';
}

function is_a_group_clean_job($job_id) {
	return is_a_clean_job($job_id) && !is_a_mtg_night_job($job_id);
}

?>
