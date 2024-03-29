<?php

/*
 * Collection of constant declarations
 */

define('RESULTS_FILE', 'schedule.txt');
date_default_timezone_set('America/Detroit');
define('BASE_DIR', '');
define('PUBLIC_DIR', BASE_DIR);
define('DB_DIR', BASE_DIR . '../db');
define("REQUEST_QUERY_STRING", $_SERVER['QUERY_STRING']);
define("SCRIPT_URL", $_SERVER['SCRIPT_URL']);
define("SESSION_ID", $_REQUEST['PHPSESSID']);
define('COMMUNITY', 'Sunward');
define("ADMIN_PASSWORD", "a");
define('MEALS_EXPORT_FILE', 'docs/meals.csv');
define('TEAMS_EXPORT_FILE', 'docs/teams.csv');


// -------- display-related constants ----------
define('UNCHANGED_BACKGROUND_COLOR', 'white');	// Background for unchanged unsaved field
define('CHANGED_BACKGROUND_COLOR', 'yellow');	// Background for changed unsaved field
define('REQUIRED_MARKER', '<span style="color:red;"><strong>!</strong></span>');
define('HEADER_COLOR', '#e6e6e6');		// Background color for table headers 
define('ADDED_COLOR', '');		// Format for worker added to shift
define('ADDED_DECORATION', ' text-decoration:underline; ');		// Format for worker added to shift
define('ADDED_ICON', '<img src="/display/images/plusIcon.png" alt="plusIcon" width="18" height="18">');		// Format for worker added to shift
define('REMOVED_COLOR', ' background:White; ');	// Format for worker removed from shift
define('REMOVED_DECORATION', ' text-decoration:line-through; ');	// Format for worker removed from shift
define('REMOVED_ICON', '<img src="/display/images/minusIcon.png" width="18" height="18">');		// Format for worker removed from shift


// -------- sqlite tables --------- 

// tables from the work survey
define('SURVEY_JOB_TABLE', 'jobs'); 
define('OFFERS_TABLE', 'offers'); 
define('SEASONS_TABLE', 'seasons'); 
define('SEASON_WORKERS_TABLE', 'season_workers');  
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
define('WORKERS_TABLE_COLUMNS', 'workers_table_columns');
define('ADMIN_PROCESS_TYPES_TABLE', 'admin_process_types'); 
define('ADMIN_PROCESS_STATUSES_TABLE', 'admin_process_statuses');
define('ADMIN_PROCESSES_TABLE', 'admin_processes');
define('JOB_TYPES_TABLE', 'job_types');
define('PAGES_TABLE', 'pages');
define('CRUMBS_TABLE', 'crumbs');
define('BREADCRUMBS_TABLE', 'breadcrumbs');
define('SESSIONS_TABLE', 'sessions');
define('MEAL_TIMES_TABLE', 'meal_times');
define('COMMUNITIES_TABLE', 'communities');
define('COMMUNITIES_INVITED_TO_MEALS_TABLE', 'communities_invited_to_meals');


// liaison tables
define('SEASON_LIAISONS_TABLE', 'season_liaisons');
define('LIAISON_REPORTS_TABLE', 'liaison_reports');
define('LIAISON_ACTIONS_LOV', 'liaison_actions_lov');


// -------- admin process ids ---------
define('SET_UP_SEASON_ID', 1);
define('CONDUCT_SURVEY_ID', 2);
define('ASSIGN_ID', 3);
define('EDIT_SEASON_ID', 4);
define('EDIT_MEALS_CALENDAR_ID', 5);
define('IMPORT_WORKERS_ID', 6);
define('EDIT_WORKERS_ID', 7); 
define('SET_SURVEY_DATES_ID', 8);
define('VIEW_SURVEY_RESULTS_ID', 9);
define('TAKE_SURVEY_ID', 10);
define('RUN_SCHEDULER_PREVIEW_ID', 11);
define('ANNOUNCE_SURVEY_ID', 12);
define('MONITOR_MANAGE_SURVEY_ID', 13);
define('RUN_SCHEDULER_ID', 15);
define('REFINE_ASSIGNMENTS_ID', 16);
define('EDIT_LIAISONS_ID', 17);
define('EXPORT_MEALS_ID', 19);
define('EXPORT_TEAMS_ID', 20);


?>
