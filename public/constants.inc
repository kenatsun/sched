<?php

if (0) deb("constants.php: start");

/*
 * Collection of constant declarations
 */

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

// -------- page links for breadcrumbs --------
define('HOME_LINK', 'Home,index.php'); 
define('SIGNUPS_LINK', ';Signups,index.php?person='); 
define('PREFS_LINK', ';Preferences,process.php?person='); 
define('SEASONS_LINK', ';Manage Seasons,seasons.php'); 
define('ASSIGNMENTS_LINK', ';Edit Assignments,dashboard.php'); 
define('CHANGE_SETS_LINK', ';Change Sets,change_sets.php');

// -------- sqlite tables --------- 

// tables from the work survey
define('SURVEY_JOB_TABLE', 'jobs'); 
define('OFFERS_TABLE', 'offers'); 
define('SEASONS_TABLE', 'seasons'); 
define('SEASON_WORKER_TABLE', 'season_workers');  

// this is altered
define('AUTH_USER_TABLE', 'workers');  

// tables for survey results
define('SCHEDULE_COMMENTS_TABLE', 'work_prefs'); 
define('SCHEDULE_COWORKER_REQUESTS_TABLE', 'coworker_requests'); 
define('SCHEDULE_PREFS_TABLE', 'shift_prefs'); 
define('SCHEDULE_SHIFTS_TABLE', 'shifts'); 

// table capturing output of the Scheduler
define('ASSIGNMENTS_TABLE', 'assignments'); 
define('ASSIGNMENT_STATES_TABLE', 'assignment_states'); 
define('SCHEDULER_RUNS_TABLE', 'scheduler_runs');
define('CHANGE_SETS_TABLE', 'change_sets');
define('CHANGES_TABLE', 'changes');
define('MEALS_TABLE', 'meals');

// config tables
define('SHIFT_PREF_NAMES_TABLE', 'shift_pref_names');

?>
