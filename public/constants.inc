<?php
/*
 * Collection of constant declarations
 */

define('BASE_DIR', ''); //SUNWARD }
// define('BASE_DIR', '/go_meals_scheduling/public');

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
define('SEASON_YEAR', $this_year);

// define('SEASON_ID', get_season_id());
define('SEASON_ID', 2); //SUNWARD
// define('SEASON_NAME', get_season_name()); // summer, fall, winter
define('SEASON_NAME', 'SUMMER'); //SUNWARD
if (0) {deb("constants.inc: SEASON_NAME:", SEASON_NAME);}

// -------- sqlite tables --------- 
// settings tables
define('SKIP_DATES_TABLE', 'skip_dates'); //SUNWARD

// tables from the work survey (read-only)
define('SURVEY_JOB_TABLE', 'jobs'); //SUNWARD
define('ASSIGN_TABLE', 'offers'); //SUNWARD
define('SEASON_TABLE', 'seasons'); //SUNWARD
define('SEASON_WORKER_TABLE', 'season_workers'); //SUNWARD

// this is altered
define('AUTH_USER_TABLE', 'workers'); //SUNWARD

// tables for meals scheduling (read & write)
define('SCHEDULE_COMMENTS_TABLE', 'work_prefs'); //SUNWARD
define('SCHEDULE_COWORKER_REQUESTS_TABLE', 'coworker_requests'); //SUNWARD
define('SCHEDULE_PREFS_TABLE', 'shift_prefs'); //SUNWARD
define('SCHEDULE_SHIFTS_TABLE', 'shifts'); //SUNWARD

// table capturing output of the Scheduler (assignments.php)
define('ASSIGNMENTS_TABLE', 'assignments'); //SUNWARD

?>
