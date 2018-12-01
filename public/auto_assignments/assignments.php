<?php

function runScheduler($options) {
	global $relative_dir;
	$relative_dir = '';
	// $relative_dir = '../';
	// $relative_dir = '../public/';
	if (0) debt ("assignments.runScheduler(): options  =", $options);

	require_once $relative_dir . 'globals.php';
	require_once $relative_dir . 'config.php';
	require_once $relative_dir . 'classes/calendar.php';
	require_once $relative_dir . 'classes/worker.php';
	require_once $relative_dir . 'classes/roster.php';
	require_once 'schedule.php';
	require_once 'meal.php';

	global $dbh;
	global $job_key_clause;
	global $scheduler_run_id;
	$scheduler_timestamp = date("Y/m/d H:i:s");
	if (0) debt("assignments: scheduler_timestamp = $scheduler_timestamp");

	// remove special case...
	unset($all_jobs['all']);

	$job_ids_clause = get_job_ids_clause();

	$assignments = new Assignments();
	$assignments->run();

	// output to json for integration with the report
	if (array_key_exists('j', $options)) {
		$assignments->saveResults();
	}

	// output as SQL insert statements
	if (array_key_exists('i', $options)) {
		$assignments->outputSqlInserts();
	}

	// output as CSV
	if (array_key_exists('c', $options)) {
		$assignments->outputCSV();
	}

	// output as HTML table
	if (array_key_exists('h', $options)) {
		$return = $assignments->outputHTML();
	}

	if (!empty($options)) {
		$assignments->printMealTeamAutoAssignments($options);
	}

	// write assignments in ASSIGNMENTS database table
	if (array_key_exists('d', $options) || array_key_exists('D', $options)) { 
		$season_id = SEASON_ID; 
		// delete existing assignments for the current season
		if (array_key_exists('D', $options)) {
			$scheduler_run_ids = "";
			$change_set_ids = ""; 
			$scheduler_runs = sqlSelect("*", SCHEDULER_RUNS_TABLE, "season_id = {$season_id}", "", (0), "scheduler_runs");
			foreach($scheduler_runs as $r=>$scheduler_run) {
				if ($scheduler_run_ids) $scheduler_run_ids .= ', '; 
				$scheduler_run_ids .= $scheduler_run['id'];
			}
			if (0) debt("assignments.php: scheduler_run_ids = {$scheduler_run_ids}");
			$change_sets = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id in ({$scheduler_run_ids})", "", (0), "change_sets");
			if (0) debt("assignments.php: change_sets = ", $change_sets);
			foreach($change_sets as $s=>$change_set) {
				if ($change_set_ids) $change_set_ids .= ', '; 
				$change_set_ids .= $change_set['id'];
			}
			if (0) debt("assignments.php: change_set_ids = {$change_set_ids}");
			sqlDelete(ASSIGNMENT_STATES_TABLE, "season_id = {$season_id}", (0));
			sqlDelete(CHANGES_TABLE, "change_set_id in ({$change_set_ids})", (0));
			sqlDelete(CHANGE_SETS_TABLE, "id in ({$change_set_ids})", (0));
			sqlDelete(SCHEDULER_RUNS_TABLE, "season_id = {$season_id}", (0)); 
		}
		sqlInsert(SCHEDULER_RUNS_TABLE, "season_id, run_timestamp", "{$season_id}, '{$scheduler_timestamp}'", (0));
		$scheduler_run_id = sqlSelect("id", SCHEDULER_RUNS_TABLE, "run_timestamp = '{$scheduler_timestamp}'", (0))[0]['id'];
		if (0) debt("assignments: scheduler_run_id = ", $scheduler_run_id);
		$assignments->outputToDatabase(); 
	}

	$end = microtime(TRUE);
	if (array_key_exists('h', $options)) {
		// $return .= "<p>elapsed time: " . date("m", strtotime($end - $start)) . " microseconds</p>"; 
		if (0) debt("assignments.runScheduler: return = ", $return); 
	}
	else {
		echo "elapsed time: " . ($end - $start) . "\n"; 
	}
	return $return;
	
}

class Assignments {
	public $roster;
	public $schedule; 

	/**
	 * Construct a new Assignments object.
	 *
	 * @param[in] a int the hobart_factor weight.
	 * @param[in] b int avail_factor weight.
	 * @param[in] c avoids_factor weight.
	 */
	public function __construct($a=NULL, $b=NULL, $c=NULL) {
		$this->roster = new Roster();

		$this->schedule = new Schedule();
		$this->schedule->setPointFactors($a, $b, $c);
		$this->schedule->setRoster($this->roster);

		$this->roster->setSchedule($this->schedule);
	}

	/**
	 * Run the assignments
	 */
	public function run() {
		// load the dates and shifts needed
		$this->schedule->initializeShifts();
		$this->roster->loadNumShiftsAssigned();
		$this->roster->loadRequests();
		$this->loadPrefs();

		$this->makeAssignments();
	}

	/**
	 * Load the shift-based survey preferences for each worker, and add their
	 * scheduling preferences.
	 * XXX: maybe this method could be moved to Roster?
	 */
	public function loadPrefs() {
		global $dbh;

		// load worker preferences per shift / date
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$meals_table = MEALS_TABLE;
		$select = "m.date as date, 
			s.job_id, 
			a.username, 
			p.pref";
		$from = "{$auth_user_table} as a, 
			{$prefs_table} as p,
			{$shifts_table} as s, 
			{$meals_table} as m";
		$where = "p.pref > 0
			AND a.id=p.worker_id
			AND s.id = p.shift_id
			AND m.id = p.meal_id";
		$order_by = "date ASC,
			p.pref DESC,
			a.username ASC";
		$rows = sqlSelect($select, $from, $where, $order_by, (0), "assignments.loadPrefs():");
		$count = 0;
		foreach($rows as $row) {
			$u = $row['username'];
			$d = $row['date'];
			$ji = $row['job_id'];
			$p = $row['pref'];

			// only add jobs which appear in the schedule
			if ($this->schedule->addPrefs($u, $ji, $d, $p)) {
				$this->roster->addPrefs($u, $ji, $d, $p);
			}

			$count++;
		}

		$this->initNonResponerPrefs();
	}

	/**
	 * This examines the overrides for those who have not taken the survey -
	 * not just those folks who were in the database tobegin with.
	 */
	public function initNonResponerPrefs() {
		$slackers = $this->roster->getNonResponderNames();
		sort($slackers);

		$this->schedule->addNonResponderPrefs($slackers);
		$this->roster->addNonResponderPrefs($slackers);
	}


	/**
	 * Sort the dates and workers' availabilities then make assignments.
	 */
	public function makeAssignments() {
		global $all_jobs;
		if (0) debt("assignments.makeAssignments(): all_jobs:", $all_jobs);
		// For each job
		foreach(array_keys($all_jobs) as $job_id) {
			$this->schedule->setJobId($job_id);
			$this->roster->setJobId($job_id);
			$this->schedule->sortPossibleRatios();

			// keep assigning until all the meals have been assigned
			$success = TRUE;
			while (!$this->schedule->isFinished() && $success) {
				$worker_freedom = $this->roster->sortAvailable();  // get the workers available for this job
				if (0) debt("assignments.makeAssignments: worker_freedom =", $worker_freedom);
				$success = $this->schedule->fillMeal($worker_freedom);
			}
		}
	}

	/**
	 * Save the results to a json file which can be used to...
	 */
	public function saveResults() {
		$assn = $this->schedule->getAssigned();
		$json = json_encode($assn);
		global $json_assignments_file;
		file_put_contents('../public/' . $json_assignments_file, $json);
	}


	/**
	 *
	 */
	public function getNumPlaceholders() {
		return $this->schedule->getNumPlaceholders();
	}


	/**
	 * Display the results on the screen
	 */
	public function printMealTeamAutoAssignments($options) {
		$display_schedule = array_key_exists('s', $options);
		if ($display_schedule) {
			$this->schedule->printMealTeamSchedule();
		}

		$display_workers = array_key_exists('w', $options);
		if ($display_workers) {
			$only_unfilled_workers = array_key_exists('u', $options);
			$this->roster->printAssignmentsOfWorkers($only_unfilled_workers); 
		}
	}


	/**
	 * Output the schedule as a series of SQL insert statements
	 */
	public function outputSqlInserts() {
		$this->schedule->printMealTeamSchedule('sql');
	}


	/**
	 * Output the schedule as CSV
	 */
	public function outputCSV() {
		$this->schedule->printMealTeamSchedule('csv');
	}

	/**
	 * Write results to ASSIGNMENTS_TABLE
	 */
	public function outputToDatabase() {
		$this->schedule->printMealTeamSchedule('db');
	}

	/**
	 * Output the schedule as HTML
	 */
	public function outputHTML() {
		return $this->schedule->printMealTeamSchedule('html');
	}
}
?>

