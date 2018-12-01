<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "utils.php";
require_once "constants.inc";
require_once "globals.php";
require_once "git_ignored.php";

if (0) deb("config.php: _POST =", $_POST); 

define('BASE_DIR', '');
// define('BASE_DIR', getRootPath()); 
define('PUBLIC_DIR', BASE_DIR);
// define('SCHEDULER_DIR', BASE_DIR . '/auto_assignments');
define('DB_DIR', BASE_DIR . '../db');

// Open the database
create_sqlite_connection();

date_default_timezone_get('America/Detroit');
define('COMMUNITY', 'Sunward');

// Configure the season
setSeasonConstants();

// Define jobs and categories of jobs
defineJobCategories();

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

// MOVED TO GLOBALS.PHP 2018-11-30
// // If these names change, be sure to update the is_a_*_job() functions.
// // List in order of importance.
// global $mtg_jobs;
// $mtg_jobs = array(
	// // MEETING_NIGHT_ORDERER => 'Meeting night takeout orderer',
	// // MEETING_NIGHT_CLEANER => 'Meeting night cleaner',
// );
// // list in order of importance
// global $sunday_jobs;
// $sunday_jobs = array(
	// // #!# note, we're looking for the string 'asst cook' in the code
	// // SUNDAY_HEAD_COOK => 'Sunday head cook (two meals/season)',
	// // SUNDAY_ASST_COOK => 'Sunday meal asst cook (two meals/season)',
	// // SUNDAY_CLEANER => 'Sunday Meal Cleaner',
// );
// // list in order of importance
// global $weekday_jobs;
// $weekday_jobs = array(
	// WEEKDAY_HEAD_COOK => 'head cook', 
	// WEEKDAY_ASST_COOK => 'asst cook', 
	// WEEKDAY_CLEANER => 'cleaner', 
	// // WEEKDAY_TABLE_SETTER => 'Weekday Table Setter',
// );
// if (0) deb("config.php: weekday_jobs (just after setting) = ", $weekday_jobs);


//////////////////////////////////////////////////////// FUNCTIONS

function setSeasonConstants() {

	// Reset current season in database if there's a POST request to do so
	if (array_key_exists('current_season_id', $_POST) && ($_POST['current_season_id'] != $season['id'])) {
		sqlUpdate(SEASONS_TABLE, "current_season = NULL", "", "", (0));
		sqlUpdate(SEASONS_TABLE, "current_season = 1", "id = {$_POST['current_season_id']}", "", (0), "config.setSeasonConstants(): set new current season");		
	}

	// Get current season from database
	$season = sqlSelect("*", SEASONS_TABLE, "current_season = 1", "", (0), "config.setSeasonConstants(): ")[0];
	if (0) deb("config.setSeasonConstants(): season = ", $season);
		
	// Assign this season's attributes to constants.

	define('SEASON_ID', $season['id']);
	if (0) deb("config.setSeasonConstants(): SEASON_ID = " . SEASON_ID); 

	define('SEASON_TYPE', $season['season_type']);
	if (0) deb("config.setSeasonConstants(): SEASON_TYPE = " . SEASON_TYPE);

	if (0) deb("config.setSeasonConstants(): season['start_date'] = " . $season['start_date']);
	if ($season['start_date']) {
		define('SEASON_START_YEAR', DateTime::createFromFormat("Y-m-d", $season['start_date'])->format("Y"));
		if (0) deb("config.setSeasonConstants(): SEASON_START_YEAR = " . SEASON_START_YEAR);
	}

	if ($season['end_date']) {
			define('SEASON_END_YEAR', DateTime::createFromFormat("Y-m-d", $season['end_date'])->format("Y"));
			if (0) deb("config.setSeasonConstants(): SEASON_END_YEAR = " . SEASON_END_YEAR);
	}
	
	if ($season['end_date']) {
		define('DEADLINE', strtotime($season['survey_end_date'] . " 23:59:59"));
		if (0) deb("config.setSeasonConstants(): DEADLINE = " . DEADLINE);
	}
	
	// Assign this season's job ids to constants
	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_HEAD_COOK'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "setSeasonConstants")[0];
	if (0) deb("config.setSeasonConstants(): season_job = ", $season_job);
	define('WEEKDAY_HEAD_COOK', "{$season_job['id']}");
	if (0) deb("config.setSeasonConstants(): WEEKDAY_HEAD_COOK = ", WEEKDAY_HEAD_COOK);

	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_ASST_COOK'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "setSeasonConstants")[0];
	if (0) deb("config.setSeasonConstants(): season_job = ", $season_job);
	define('WEEKDAY_ASST_COOK', "{$season_job['id']}");
	if (0) deb("config.setSeasonConstants(): WEEKDAY_ASST_COOK = ", WEEKDAY_ASST_COOK);

	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_CLEANER'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "setSeasonConstants")[0];
	if (0) deb("config.setSeasonConstants(): season_job = ", $season_job);
	define('WEEKDAY_CLEANER', "{$season_job['id']}");
	if (0) deb("config.setSeasonConstants(): WEEKDAY_CLEANER = ", WEEKDAY_CLEANER); 
}

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


?>
