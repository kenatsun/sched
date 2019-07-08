<?php

define ('SHOW_IDS', showIds());  // set to 1 to display object ids for debugging

// -------- some page formatting constants ---------
define('HEADER_COLOR', '#e6e6e6');		// Background color for table headers 

date_default_timezone_set('America/Detroit');
if (0) deb("globals.php: datetime = " . date("Y-m-d H:i:s") );

require_once('utils.php');
require_once('config.php');

define('NON_RESPONSE_PREF', .5);
define('PLACEHOLDER', 'XXXXXXXX');
define('DOMAIN', '@gocoho.org');
define('HAS_CONFLICT', -1);


$dows = days_of_week();
foreach($dows as $i=>$dow) {
	define($dow['full_name_uppercase'], (int)$dow['number']);
}

if (0) deb("globals.php: SUNDAY = " . SUNDAY);
if (0) deb("globals.php: THURSDAY = " . THURSDAY);
if (0) deb("globals.php: SATURDAY = " . SATURDAY);

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


///////////////////////////////////////////////////////// FUNCTIONS

/**
 * Get the short names of the days of the week.
 */
function get_days_of_week() {
	$dows = days_of_week("", "short_name");
	if (0) deb("globals.get_days_of_week(): dows short_names = ", $dows);
	return $dows;
}

function defineJobCategories() {
	// If these names change, be sure to update the is_a_*_job() functions.
	// List in order of importance.
	global $mtg_jobs;
	$mtg_jobs = array(
		// MEETING_NIGHT_ORDERER => 'Meeting night takeout orderer',
		// MEETING_NIGHT_CLEANER => 'Meeting night cleaner',
	);
	// list in order of importance
	global $sunday_jobs;
	$sunday_jobs = array(
		// #!# note, we're looking for the string 'asst cook' in the code
		// SUNDAY_HEAD_COOK => 'Sunday head cook (two meals/season)',
		// SUNDAY_ASST_COOK => 'Sunday meal asst cook (two meals/season)',
		// SUNDAY_CLEANER => 'Sunday Meal Cleaner',
	);
	// list in order of importance
	global $weekday_jobs;
	$weekday_jobs = array(
		// #!# note, we're looking for the string 'asst cook' in the code
		WEEKDAY_HEAD_COOK => 'head cook', 
		WEEKDAY_ASST_COOK => 'asst cook', 
		WEEKDAY_CLEANER => 'cleaner', 
		// WEEKDAY_TABLE_SETTER => 'Weekday Table Setter',
	);
	if (0) deb("globals.php: weekday_jobs (just after setting) = ", $weekday_jobs);

	global $all_jobs;
	$all_jobs = array();
	$all_jobs['all'] = 'all';
	if (0) {deb('globals.php: mtg_jobs =', $mtg_jobs);}
	if (0) {deb('globals.php: sunday_jobs =', $sunday_jobs);}
	if (0) {deb('globals.php: weekday_jobs =', $weekday_jobs);}
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
}

/**
 * Get the list of the weekdays where meals are served.
 */
function get_weekday_meal_days() {
	$wmds = days_of_week("4 0", "number");
	if (0) deb("globals.get_weekday_meal_days(): wmds =", $wmds);
	return $wmds; 
	// return [THURSDAY, SUNDAY];
}
if (0) get_weekday_meal_days(); 

global $mtg_nights;
// key = day of week, value = ordinal occurrence of day/week
$mtg_nights = array(
	WEDNESDAY => 1,
	MONDAY => 3,
);


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
