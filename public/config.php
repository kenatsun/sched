<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
date_default_timezone_get('America/Detroit');

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('april 22, 2016, 7:30pm'));

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 3206);
define('MEETING_NIGHT_CLEANER', 3209);
define('SUNDAY_HEAD_COOK', 3204);
define('SUNDAY_ASST_COOK', 3205);
define('SUNDAY_CLEANER', 3208);
define('WEEKDAY_HEAD_COOK', 3202);
define('WEEKDAY_ASST_COOK', 3203);
define('WEEKDAY_CLEANER', 3207);

// forced skip dates
global $skip_dates;
$skip_dates = array(
);


// have a meal on this date which wouldn't otherwise
global $override_dates;
$override_dates = array(
	//11 => array(26)
);

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => array(job_id => num_meals)
	return array(
		'amyh' => array(
			SUNDAY_CLEANER => 2,
		),
		'bill' => array(
			SUNDAY_CLEANER => 1,
			SUNDAY_ASST_COOK => 1,
		),
		'hope' => array(
			SUNDAY_CLEANER => 1,
		),
		'patti' => array(
			SUNDAY_CLEANER => 1,
			SUNDAY_ASST_COOK => 1,
		),
		'lindal' => array(
			SUNDAY_ASST_COOK => 2,
			WEEKDAY_ASST_COOK => -2,
		),
		'anastasia' => array(
			WEEKDAY_ASST_COOK => 2,
		),
		'brittany' => array(
			SUNDAY_ASST_COOK => -4,
			SUNDAY_CLEANER => -8,
		),
		'gail' => array(
			SUNDAY_CLEANER => 1,
		)
	);
}

/*
 * XXX unassigned:
 * - 2 sunday cleans
 */

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
	WEEKDAY_HEAD_COOK => 'Weekday head cook (two meals/season)',
	WEEKDAY_ASST_COOK => 'Weekday meal asst cook (2 meals/season)',
	WEEKDAY_CLEANER => 'Weekday Meal cleaner',
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

		WEEKDAY_ASST_COOK => 2,
		WEEKDAY_HEAD_COOK => 2,
		WEEKDAY_CLEANER => 4,
	);

	if (is_null($job_id)) {
		return $dinners;
	}

	return array_get($dinners, $job_id, 0);
}

$hours_per_job = array(
	MEETING_NIGHT_ORDERER => 1,
	MEETING_NIGHT_CLEANER => 1.5,

	SUNDAY_HEAD_COOK => 4,
	SUNDAY_ASST_COOK => 2,
	SUNDAY_CLEANER => 1.5,

	WEEKDAY_HEAD_COOK => 4,
	WEEKDAY_ASST_COOK => 2,
	WEEKDAY_CLEANER => 1.5,
);

global $job_instances;
// job_id => array( dow => count), 1 = MON, 7 = SUN
// per job, list number of open shifts per day of week
$job_instances = array(
	MEETING_NIGHT_CLEANER => array(1=>1, 3=>1),
	MEETING_NIGHT_ORDERER => array(1=>1, 3=>1),

	SUNDAY_HEAD_COOK => array(7=>1),
	SUNDAY_ASST_COOK => array(7=>2),
	SUNDAY_CLEANER => array(7=>3),

	WEEKDAY_HEAD_COOK => array(1=>1, 2=>1, 3=>1, 4=>1),
	WEEKDAY_ASST_COOK => array(1=>2, 2=>2, 3=>2, 4=>2),
	WEEKDAY_CLEANER => array(1=>3, 2=>3, 3=>3, 4=>3),
);

global $hobarters;
$hobarters = array(
	'aaron',
	'aditya',
	'amyh',
	'debbi',
	'erik',
	'hope',
	'jillian',
	'jimgraham',
	'kathyboblitt',
	'mac',
	'maryking',
	'patti',
	'rod',
	'sarah',
	'sharon',
	'ted',
	'willie',
	'vincent',
);

?>
