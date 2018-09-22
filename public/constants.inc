<?php
/*
 * Collection of constant declarations
 */

define('BASE_DIR', ''); 
define('RESULTS_FILE', 'schedule.txt');

# ---------- date stuff -------------
define('WINTER', 'winter');
define('SPRING', 'spring');
define('FALL', 'fall');
define('SUMMER', 'summer');

$this_year = date('Y');
$this_month = date('n');
if (($this_month == 11) || ($this_month == 12)) {
	$this_year++;
}

// These have been replaced with database definitions in globals.php
// define('SEASON_START_YEAR', $this_year);
// define('SEASON_ID', get_season_id());
// define('SEASON_ID', 2); 
// define('SEASON_TYPE', get_season_name()); // summer, fall, winter
// define('SEASON_TYPE', 'SUMMER');  
// if (0) {deb("constants.inc: SEASON_TYPE:", SEASON_TYPE);}

// -------- sqlite tables --------- 
// settings tables
define('SKIP_DATES_TABLE', 'skip_dates');

// tables from the work survey (read-only)
define('SURVEY_JOB_TABLE', 'jobs'); 
define('ASSIGN_TABLE', 'offers'); 
define('SEASON_TABLE', 'seasons'); 
define('SEASON_WORKER_TABLE', 'season_workers'); 

// this is altered
define('AUTH_USER_TABLE', 'workers');  

// tables for meals scheduling (read & write)
define('SCHEDULE_COMMENTS_TABLE', 'work_prefs'); 
define('SCHEDULE_COWORKER_REQUESTS_TABLE', 'coworker_requests'); 
define('SCHEDULE_PREFS_TABLE', 'shift_prefs'); 
define('SCHEDULE_SHIFTS_TABLE', 'shifts'); 

// table capturing output of the Scheduler (assignments.php)
define('ASSIGNMENTS_TABLE', 'assignments'); 

?>
