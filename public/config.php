<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
date_default_timezone_get('America/Detroit');

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('January 31, 2018, 8:00pm'));

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 4194);
define('MEETING_NIGHT_CLEANER', 4197);
define('SUNDAY_HEAD_COOK', 4192);
define('SUNDAY_ASST_COOK', 4193);
define('SUNDAY_CLEANER', 4196);
// define('WEEKDAY_HEAD_COOK', 4190);
// define('WEEKDAY_ASST_COOK', 4191);
// define('WEEKDAY_CLEANER', 4195);
define('WEEKDAY_HEAD_COOK', 1); //SUNWARD
define('WEEKDAY_ASST_COOK', 2); //SUNWARD
define('WEEKDAY_CLEANER', 3); //SUNWARD
define('WEEKDAY_TABLE_SETTER', 4184);

# Are Sunday meals treated separately from weeknights?
// define('ARE_SUNDAYS_UNIQUE', TRUE);
define('ARE_SUNDAYS_UNIQUE', FALSE); //SUNWARD

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]
	return [
		/*
		'example' => [
			WEEKDAY_ASST_COOK => 1,
		],
		*/
	];
}

function get_skip_dates() {
	return [];
}

// If these names change, be sure to update the is_a_*_job() functions.
// List in order of importance.
$mtg_jobs = array(
	MEETING_NIGHT_ORDERER => 'Meeting night takeout orderer',
	MEETING_NIGHT_CLEANER => 'Meeting night cleaner',
);
// list in order of importance
$sunday_jobs = array(
	// #!# note, we're looking for the string 'asst cook' in the code
	SUNDAY_HEAD_COOK => 'Sunday head cook (two meals/season)',
	SUNDAY_ASST_COOK => 'Sunday meal asst cook (two meals/season)',
	SUNDAY_CLEANER => 'Sunday Meal Cleaner',
);
// list in order of importance
$weekday_jobs = array(
	WEEKDAY_HEAD_COOK => 'head cook', //SUNWARD
	WEEKDAY_ASST_COOK => 'asst cook', //SUNWARD
	WEEKDAY_CLEANER => 'cleaner', //SUNWARD
	WEEKDAY_TABLE_SETTER => 'Weekday Table Setter',
);

/*
 * Get how many dinners are contained within the requested job.
 *
 * @param[in] job_id int the ID of the job being requested.
 * @return int the number of dinners needed for this job.
 */
function get_num_dinners_per_assignment($job_id=NULL) {
	// job_id => num dinners per season
	static $dinners = array(
		MEETING_NIGHT_CLEANER => 2,
		MEETING_NIGHT_ORDERER => 2,

		SUNDAY_HEAD_COOK => 2,
		SUNDAY_ASST_COOK => 2,
		SUNDAY_CLEANER => 4,

		// WEEKDAY_ASST_COOK => 2,
		// WEEKDAY_HEAD_COOK => 2,
		// WEEKDAY_CLEANER => 4,
		WEEKDAY_ASST_COOK => 1, //SUNWARD
		WEEKDAY_HEAD_COOK => 1, //SUNWARD
		WEEKDAY_CLEANER => 1, //SUNWARD
		WEEKDAY_TABLE_SETTER => 4,
	);

	if (is_null($job_id)) {
		return $dinners;
	}

	return array_get($dinners, $job_id, 0);
}

// job_id => array( dow => count), 1 = MON, 7 = SUN
// per job, list number of open shifts per day of week
function get_job_instances() {
	return [
		MEETING_NIGHT_CLEANER => array(1=>1, 3=>1),
		MEETING_NIGHT_ORDERER => array(1=>1, 3=>1),

		SUNDAY_HEAD_COOK => array(7=>1),
		SUNDAY_ASST_COOK => array(7=>2),
		SUNDAY_CLEANER => array(7=>3),

		// WEEKDAY_HEAD_COOK => array(1=>1, 2=>1, 3=>1, 4=>1),
		// WEEKDAY_ASST_COOK => array(1=>2, 2=>2, 3=>2, 4=>2),
		// WEEKDAY_CLEANER => array(1=>3, 2=>3, 3=>3, 4=>3),
		WEEKDAY_HEAD_COOK => array(4=>1, 7=>1), //SUNWARD
		WEEKDAY_ASST_COOK => array(4=>1, 7=>1), //SUNWARD
		WEEKDAY_CLEANER => array(4=>2, 7=>2), //SUNWARD
		WEEKDAY_TABLE_SETTER => array(1=>1, 2=>1, 3=>1, 4=>1),
	];
}

/**
 * Get the list of people preferred to do hobarting duty.
 *
 * @return array list of names.
 */
function get_hobarters() {
	return [
		'bill',
		'debbi',
		'erik',
		'hope',
		'jillian',
		'kate',
		'kathyboblitt',
		'kevink',
		'mac',
		'maryking',
		'patti',
		'rod',
		'sharon',
		'ted',
		'willie',
		'yimiau',
	];
}
?>
