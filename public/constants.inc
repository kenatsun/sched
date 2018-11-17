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

// -------- sqlite tables --------- 
// settings tables

// tables from the work survey (read-only)
define('SURVEY_JOB_TABLE', 'jobs'); 
define('OFFERS_TABLE', 'offers'); 
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
define('ASSIGNMENT_STATES_TABLE', 'assignment_states'); 
define('SCHEDULER_RUNS_TABLE', 'scheduler_runs');
define('CHANGE_SETS_TABLE', 'change_sets');
define('CHANGES_TABLE', 'changes');
define('MEALS_TABLE', 'meals');

// config tables
define('SHIFT_PREF_NAMES_TABLE', 'shift_pref_names');

?>
