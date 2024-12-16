<?php

if (0) deb("config.php: start"); 

// // Open the database
// create_sqlite_connection();

// Configure the session
setSessionConstants();

// Determine if user is admin or guest 
changeUserStatus();

// Define jobs and categories of jobs
defineJobCategories();

// Set path to assignments file.
global $json_assignments_file; 
$json_assignments_file = 'results/' . SEASON_ID . '.json';

// check on the time zone
if (0) deb ("config.php: date_default_timezone_get(): ", date_default_timezone_get());



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


if (isset($_REQUEST['worker']) || isset($_REQUEST['person'])) {
	$survey_scripts = '
	<script src="js/utils.js"></script>
	<script src="js/survey_library.js"></script>
	';
}

$body = '
<body>
	<input type="hidden" id="script_url" value="' . SCRIPT_URL . '">
	<input type="hidden" id="changed_background_color" value="' . CHANGED_BACKGROUND_COLOR . '">
	<input type="hidden" id="unchanged_background_color" value="' . UNCHANGED_BACKGROUND_COLOR . '">
	<script src="js/utils2.js"></script>
' 		. $survey_scripts 
;

print $body;

//////////////////////////////////////////////////////// FUNCTIONS

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


function setSessionConstants() {
	
	if (0) deb("config.setSessionConstants(): _REQUEST = ", $_REQUEST);

	$global_season = sqlSelect("*", SEASONS_TABLE, "current_season = 1", "", (0), "config.setSessionConstants(): ")[0];

	// Create (if necessary) and get the current session
	if (!sqlSelect("*", SESSIONS_TABLE, "session_id = '" . SESSION_ID . "'", "", (0))[0]) {
		$columns = "session_id, season_id, when_created";
		$values = "'" . SESSION_ID . "', " . $global_season['id'] . ", '" . date("Y-m-d H:i:s") . "'";
		sqlInsert(SESSIONS_TABLE, $columns, $values, (0));
	}
	sqlUpdate(SESSIONS_TABLE, "when_last_active = '" . date("Y-m-d H:i:s") . "'", "session_id = '" . SESSION_ID . "'");
	$session = sqlSelect("*", SESSIONS_TABLE, "session_id = '" . SESSION_ID . "'", "", (0))[0];
	
	// Reset global season in database if there's a _REQUEST to do so
	if (array_key_exists('global_season_id', $_REQUEST)) {
		sqlUpdate(SEASONS_TABLE, "current_season = NULL", "", "", (0));
		sqlUpdate(SEASONS_TABLE, "current_season = 1", "id = {$_REQUEST['global_season_id']}", "", (0), "config.setSessionConstants(): set new global season");		
	}

	// Set GLOBAL_SEASON constant
	$global_season = sqlSelect("*", SEASONS_TABLE, "current_season = 1", "", (0), "config.setSessionConstants(): ")[0];
	define("GLOBAL_SEASON_ID", $global_season['id']);
	if (0) deb("config.setSessionConstants(): GLOBAL_SEASON_ID = " . GLOBAL_SEASON_ID);

	// Reset session season in database if there's a _REQUEST request to do so
	if (array_key_exists('session_season_id', $_REQUEST)) {
		sqlUpdate(SESSIONS_TABLE, "season_id = {$_REQUEST['session_season_id']}", "session_id = '" . SESSION_ID . "'", (0), "config.setSessionConstants(): set new session season");		
	}

	// If user is not an admin this session doesn't have a session season, set this session season to global season
	// XXX 12/8/2019 - SETTING SESSION SEASON UNCONDITIONALLY TO GLOBAL SEASON TILL A BUG IS FIXED
	// if (!userIsAdmin() || !$session['season_id']) {
		$global_season = sqlSelect("*", SEASONS_TABLE, "current_season = 1", "", (0), "config.setSessionConstants(): ")[0];
		if (0) deb("config.setSessionConstants(): global_season = ", $global_season);
		sqlUpdate(SESSIONS_TABLE, "season_id = " . $global_season['id'], "session_id = '" . SESSION_ID . "'", (0));
	// }
	
	// Get season for this session
	$session = sqlSelect("*", SESSIONS_TABLE, "session_id = '" . SESSION_ID . "'", "", (0))[0]; 
	$season = sqlSelect("*", SEASONS_TABLE, "id = " . $session['season_id'], "", (0), "config.setSessionConstants(): ")[0];
	if (0) deb("config.setSessionConstants(): season = ", $season);
		
	// Assign this season's attributes to constants.

	define('SEASON_ID', $season['id']);
	if (0) deb("config.setSessionConstants(): SEASON_ID = " . SEASON_ID); 

	// define('SEASON_TYPE', $season['season_type']);
	// if (0) deb("config.setSessionConstants(): SEASON_TYPE = " . SEASON_TYPE);

	if (0) deb("config.setSessionConstants(): season['start_date'] = " . $season['start_date']);
	if ($season['start_date']) {
		define('SEASON_START_YEAR', DateTime::createFromFormat("Y-m-d", $season['start_date'])->format("Y"));
		if (0) deb("config.setSessionConstants(): SEASON_START_YEAR = " . SEASON_START_YEAR);
	}

	if ($season['end_date']) {
			define('SEASON_END_YEAR', DateTime::createFromFormat("Y-m-d", $season['end_date'])->format("Y"));
			if (0) deb("config.setSessionConstants(): SEASON_END_YEAR = " . SEASON_END_YEAR);
	}
	
	if ($season['end_date']) {
		define('DEADLINE', strtotime($season['survey_closing_date'] . " 23:59:59"));
		if (0) deb("config.setSessionConstants(): DEADLINE = " . DEADLINE);
	}
	
	// Assign this season's job ids to constants
	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_HEAD_COOK'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "setSessionConstants")[0];
	if (0) deb("config.setSessionConstants(): season_job = ", $season_job);
	define('WEEKDAY_HEAD_COOK', $season_job['id']);
	if (0) deb("config.setSessionConstants(): WEEKDAY_HEAD_COOK = ", WEEKDAY_HEAD_COOK);

	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_ASST_COOK'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "setSessionConstants")[0];
	if (0) deb("config.setSessionConstants(): season_job = ", $season_job);
	define('WEEKDAY_ASST_COOK', $season_job['id']);
	if (0) deb("config.setSessionConstants(): WEEKDAY_ASST_COOK = ", WEEKDAY_ASST_COOK);

	$where = "season_id = {$season['id']} and constant_name = 'WEEKDAY_CLEANER'";
	$season_job = sqlSelect("*", SURVEY_JOB_TABLE, $where, "id", (0), "setSessionConstants")[0];
	if (0) deb("config.setSessionConstants(): season_job = ", $season_job);
	define('WEEKDAY_CLEANER', $season_job['id']);
	if (0) deb("config.setSessionConstants(): WEEKDAY_CLEANER = ", WEEKDAY_CLEANER); 
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
