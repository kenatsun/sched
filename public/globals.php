<?php
require_once('utils.php');
require_once('config.php');

global $json_assignments_file;
$json_assignments_file = 'results/' . SEASON_ID . '.json';

define('NON_RESPONSE_PREF', .5);
define('PLACEHOLDER', 'XXXXXXXX');
define('DOMAIN', '@gocoho.org');
define('HAS_CONFLICT', -1);

date_default_timezone_set('America/Detroit');

global $days_of_week;
$days_of_week = array(
	'Sun',
	'Mon',
	'Tue',
	'Wed',
	'Thu',
	'Fri',
	'Sat',
);

global $pref_names;
$pref_names = array(
	0 => 'avoid',
	1 => 'OK',
	2 => 'prefer',
);

create_sqlite_connection();

global $all_jobs;
$all_jobs = array();
$all_jobs['all'] = 'all';
$all_jobs += $mtg_jobs + $sunday_jobs + $weekday_jobs;

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

define('MONDAY', 1);
define('TUESDAY', 2);
define('WEDNESDAY', 3);
function get_meal_days() {
	return array(MONDAY, TUESDAY, WEDNESDAY);
}

global $mtg_nights;
// key = day of week, value = ordinal occurence of day/week
$mtg_nights = array(
	WEDNESDAY => 1,
	MONDAY => 3,
);

global $current_season;
switch(SEASON_NAME) {
	case 'winter':
		$current_season = array(
			1=>'January',
			2=>'February',
			3=>'March',
			4=>'April'
		);
		break;

	case 'spring':
	case 'summer':
		$current_season = array(
			5=>'May',
			6=>'June',
			7=>'July',
			8=>'August'
		);
		break;

	case 'fall':
		$current_season = array(
			9=>'September',
			10=>'October',
			11=>'November',
			12=>'December'
		);
		break;
}

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

		$db_fullpath = "{$relative_dir}sqlite_data/work_allocation.db";
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
	global $hobarters;
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
