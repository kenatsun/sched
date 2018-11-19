<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
// require_once "{$relative_dir}/globals.php";
require_once('git_ignored.php');
date_default_timezone_get('America/Detroit');

define('COMMUNITY', 'Sunward');

create_sqlite_connection();

define ('SHOW_IDS', showIds());  // set to 1 to display object ids for debugging

/* -------- seasonal config --------- */
/* ----------- job ids --------------- */
set_season_constants();


// Set path to assignments file.
global $json_assignments_file;
$json_assignments_file = 'results/' . SEASON_ID . '.json';


/* ----------- meals on holidays? -------------- */
if (COMMUNITY == 'Sunward') {
	define('MEALS_ON_HOLIDAYS', TRUE);
} else {
	define('MEALS_ON_HOLIDAYS', FALSE);
}


# Are Sunday meals treated separately from weeknights?
if (COMMUNITY == 'Sunward') {
	define('ARE_SUNDAYS_UNIQUE', FALSE);
} else {
	define('ARE_SUNDAYS_UNIQUE', TRUE);
}

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

// If these names change, be sure to update the is_a_*_job() functions.
// List in order of importance.
$mtg_jobs = array(
	// MEETING_NIGHT_ORDERER => 'Meeting night takeout orderer',
	// MEETING_NIGHT_CLEANER => 'Meeting night cleaner',
);
// list in order of importance
$sunday_jobs = array(
	// #!# note, we're looking for the string 'asst cook' in the code
	// SUNDAY_HEAD_COOK => 'Sunday head cook (two meals/season)',
	// SUNDAY_ASST_COOK => 'Sunday meal asst cook (two meals/season)',
	// SUNDAY_CLEANER => 'Sunday Meal Cleaner',
);
// list in order of importance
$weekday_jobs = array(
	WEEKDAY_HEAD_COOK => 'head cook', 
	WEEKDAY_ASST_COOK => 'asst cook', 
	WEEKDAY_CLEANER => 'cleaner', 
	// WEEKDAY_TABLE_SETTER => 'Weekday Table Setter',
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
		// MEETING_NIGHT_CLEANER => 2,
		// MEETING_NIGHT_ORDERER => 2,

		// SUNDAY_HEAD_COOK => 2,
		// SUNDAY_ASST_COOK => 2,
		// SUNDAY_CLEANER => 4,

		// WEEKDAY_ASST_COOK => 2,
		// WEEKDAY_HEAD_COOK => 2,
		// WEEKDAY_CLEANER => 4,
		WEEKDAY_ASST_COOK => 1, 
		WEEKDAY_HEAD_COOK => 1, 
		WEEKDAY_CLEANER => 1, 
		// WEEKDAY_TABLE_SETTER => 4,
	);

	if (is_null($job_id)) {
		return $dinners;
	}

	return array_get($dinners, $job_id, 0);
}

// per job, list number of open shifts per day of week
function get_job_instances() {
	return [
		// MEETING_NIGHT_CLEANER => array(1=>1, 3=>1),
		// MEETING_NIGHT_ORDERER => array(1=>1, 3=>1),

		// SUNDAY_HEAD_COOK => array(7=>1),
		// SUNDAY_ASST_COOK => array(7=>2),
		// SUNDAY_CLEANER => array(7=>3),

		// WEEKDAY_HEAD_COOK => array(1=>1, 2=>1, 3=>1, 4=>1),
		// WEEKDAY_ASST_COOK => array(1=>2, 2=>2, 3=>2, 4=>2),
		// WEEKDAY_CLEANER => array(1=>3, 2=>3, 3=>3, 4=>3),
		WEEKDAY_HEAD_COOK => array(4=>1, 7=>1), 
		WEEKDAY_ASST_COOK => array(4=>1, 7=>1), 
		WEEKDAY_CLEANER => array(4=>2, 7=>2), 
		// WEEKDAY_TABLE_SETTER => array(1=>1, 2=>1, 3=>1, 4=>1),
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

function set_season_constants() {

	// Identify the season this session will be working with.
	if(isset($_COOKIE["season id"])) {			// if this user has selected a season to work on 
		$season_id = $_COOKIE["season id"];		// set season_id to that
	} else {									// if not, get season_id from the current_season table (which only has one row)
		$season_id = sqlSelect("season_id", "current_season", "", "", (0), "current_season")[0]['season_id'];
		if (0) deb("config.php: season_id (from database) = ", $season_id);
	}
	// global $season;
	$season = sqlSelect("*", "seasons", "id = {$season_id}", "")[0];
	if (0) deb("config.php: season = ", $season);

	// Assign this season's attributes to constants.
	// global $season_name;
	$season_name = $season['name'];
	if (0) deb("config.php: season_name = {$season_name}");

	define('SEASON_ID', $season_id);
	if (0) deb("config.php: SEASON_ID = " . SEASON_ID);

	define('SEASON_TYPE', $season['season_type']);
	if (0) deb("config.php: SEASON_TYPE = " . SEASON_TYPE);

	define('SEASON_START_YEAR', DateTime::createFromFormat("Y-m-d", $season['start_date'])->format("Y"));
	if (0) deb("config.php: SEASON_START_YEAR = " . SEASON_START_YEAR);

	define('SEASON_END_YEAR', DateTime::createFromFormat("Y-m-d", $season['end_date'])->format("Y"));
	if (0) deb("config.php: SEASON_END_YEAR = " . SEASON_END_YEAR);

	define('DEADLINE', strtotime($season['survey_end_date']));
	if (0) deb("config.php: DEADLINE = " . DEADLINE);

	// Assign this season's job ids to constants
	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_HEAD_COOK'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "set_season_constants")[0];
	if (0) deb("config.php: season_job = ", $season_job);
	define('WEEKDAY_HEAD_COOK', "{$season_job['id']}");
	if (0) deb("config.php: WEEKDAY_HEAD_COOK = ", WEEKDAY_HEAD_COOK);

	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_ASST_COOK'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "set_season_constants")[0];
	if (0) deb("config.php: season_job = ", $season_job);
	define('WEEKDAY_ASST_COOK', "{$season_job['id']}");
	if (0) deb("config.php: WEEKDAY_ASST_COOK = ", WEEKDAY_ASST_COOK);

	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_CLEANER'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "set_season_constants")[0];
	if (0) deb("config.php: season_job = ", $season_job);
	define('WEEKDAY_CLEANER', "{$season_job['id']}");
	if (0) deb("config.php: WEEKDAY_CLEANER = ", WEEKDAY_CLEANER);

}

?>
