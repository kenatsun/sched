<?php
/*
 * Check that we have the proper level of assignments.
 * How many meals-shifts do we have and how many assigned worker-shifts do we
 * have?
 * $Id: levels_check.php,v 1.3 2014/04/07 00:51:54 willn Exp $
 */

global $relative_dir;
$relative_dir = '../public/';

require_once $relative_dir . 'globals.php';
require_once $relative_dir . 'classes/calendar.php';
require_once $relative_dir . 'classes/worker.php';
require_once $relative_dir . 'classes/roster.php';
require_once 'schedule.php';
require_once 'meal.php';

global $dbh;
global $job_key_clause;

// remove special case...
unset($all_jobs['all']);

$job_ids_clause = get_job_ids_clause();

$assignments = new CheckAssignments();
$assignments->run();
/*
if (!empty($options)) {
	$assignments->printResults($options);
}
*/

class CheckAssignments {
	public $roster;
	public $schedule;

	public function __construct($a=NULL, $b=NULL, $c=NULL) {
		$this->roster = new Roster();

		$this->schedule = new Schedule($a, $b, $c);
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

		print "--------\nschedule:\n";
		var_dump($this->schedule);
		print "--------\nroster:\n";
		var_dump($this->roster);
	}

	/**
	 * Load the shift-based survey preferences for each worker, and add their
	 * scheduling preferences.
	 */
	public function loadPrefs() {
		global $dbh;

		// load worker preferences per shift / date
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT s.string as date, s.job_id, a.username, p.pref
				FROM {$auth_user_table} as a, {$prefs_table} as p,
					{$shifts_table} as s
				WHERE p.pref>0
					AND a.id=p.worker_id
					AND s.id = p.date_id
				ORDER BY date ASC,
					p.pref DESC,
					a.username ASC;
EOSQL;

		$count = 0;
		foreach($dbh->query($sql) as $row) {
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

		foreach(array_keys($all_jobs) as $j) {
			$this->schedule->setJobId($j);
			$this->roster->setJobId($j);
			$this->schedule->sortPossibleRatios();

			// keep assigning until all the meals have been assigned
			$success = TRUE;
			while (!$this->schedule->isFinished() && $success) {
				$worker_freedom = $this->roster->sortAvailable();
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
	public function printResults($options) {
		$display_schedule = array_key_exists('s', $options);
		if ($display_schedule) {
			$this->schedule->printResults();
		}

		$display_workers = array_key_exists('w', $options);
		if ($display_workers) {
			$only_unfilled_workers = array_key_exists('u', $options);
			$this->roster->printResults($only_unfilled_workers);
		}
	}


	/**
	 * Output the schedule as a series of SQL insert statements
	 */
	public function outputSqlInserts() {
		$this->schedule->printResults('sql');
	}


	/**
	 * Output the schedule as a series of SQL insert statements
	 */
	public function outputCSV() {
		$this->schedule->printResults('csv');
	}
}
?>
