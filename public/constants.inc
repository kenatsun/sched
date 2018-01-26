<?php
/*
 * Collection of constant declarations
 */
define('BASE_DIR', ''); //SUNWARD
# define('BASE_DIR', '/meals_scheduling');

define('SKIP_EMAIL', FALSE); // set to true for debugging
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

# define('SEASON_ID', get_season_id());
define('SEASON_ID', 1); //SUNWARD
define('SEASON_NAME', get_season_name()); // summer, fall, winter

# -------- list of sqlite tables --------- 
# tables from the work survey (read-only)
define('SURVEY_JOB_TABLE', 'jobs'); //SUNWARD
define('ASSIGN_TABLE', 'offers'); //SUNWARD

# this is altered
define('AUTH_USER_TABLE', 'workers'); //SUNWARD

# tables for meals scheduling (read & write)
define('SCHEDULE_COMMENTS_TABLE', 'work_prefs'); //SUNWARD
define('SCHEDULE_PREFS_TABLE', 'shift_prefs'); //SUNWARD
define('SCHEDULE_SHIFTS_TABLE', 'shifts'); //SUNWARD

?>
