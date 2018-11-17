<?php
class Worker {
	public $worker_id;
	public $username;
	public $first_name;
	public $last_name;
	public $email;
	public $avail_shifts = array();
	public $assigned = array();
	public $num_shifts_to_fill = array();
	public $requests = array();
	public $adjacency_limit = 8;
	public $avoids = array();
	public $prefers = array();
	public $tasks;
	public $comments;
	public $dbh;
	public $is_placeholder = FALSE;

	/**
	 *
	 */
	public function __construct($username) {
		$this->username = $username;

		global $dbh;
		$this->dbh = $dbh;
	}

	public function isPlaceholder() {
		return $this->is_placeholder;
	}

	public function debugLogSummary() {
		$nsf = print_r($this->num_shifts_to_fill, true);

		error_log('debugLogSummary: ' . __CLASS__ . ' ' . __FUNCTION__ . ' ' .
			__LINE__ . "
			Username:{$this->username},
			id:{$this->worker_id}
			NSF:{$nsf}");
	}

	public function setNames($first, $last) {
		$this->first_name = $first;
		$this->last_name = $last;
	}

	/**
	 * Get the name of the worker - either their full name, or their username
	 * if their full name is not set.
	 */
	public function getName() {
		return ($this->first_name != '') ?
			"{$this->first_name} {$this->last_name}" :
			$this->username;
	}

	public function getFirstName() {
		return ($this->first_name != '') ?
			$this->first_name : $this->username;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * Set the list of people to avoid assignments with.
	 * @param[in] avoids array listing usernames to avoid assignments.
	 */
	public function setAvoids($avoids) {
		if ($avoids == '') {
			return;
		}
		$this->avoids = $avoids;
	}

	/**
	 * Get the list of people this worker wants to avoid working with.
	 * @return array list of usernames.
	 */
	public function getAvoids() {
		return $this->avoids;
	}

	/**
	 * Set the list of people to prefer assignments with.
	 * @param[in] prefers array listing usernames to prefer assignments.
	 */
	public function setPrefers($prefers) {
		$this->prefers = $prefers;
	}

	/**
	 * Get the list of people this worker prefers working with.
	 * @return array list of usernames.
	 */
	public function getPrefers() {
		return $this->prefers;
	}

	public function getAllPreferences() {
		$all = array();
		$avoids = $this->getAvoids();
		if (!empty($avoids)) {
			$all['avoids'] = $avoids;
		}

		$prefers = $this->getPrefers();
		if (!empty($prefers)) {
			$all['prefers'] = $prefers;
		}

		if (!empty($this->requests['clean_after_self'])) {
			$all['clean_after_self'] = $this->requests['clean_after_self'];
		}

		if (!empty($this->requests['bunch_shifts'])) {
			$all['bunch_shifts'] = $this->requests['bunch_shifts'];
		}

		return empty($all) ? '' : $all;
	}

	/**
	 * Set the number of shifts assigned to a worker per job.
	 *
	 * @param[in] job_id int the unique ID number for the job / shift being
	 *     assigned.
	 * @param[in] instances int number of instances this worker
	 *     has assigned of this shift over the entire season
	 */
	public function addNumShiftsAssigned($job_id, $instances) {
		if (!isset($this->num_shifts_to_fill[$job_id])) {
			$this->num_shifts_to_fill[$job_id] = $instances;
			return;
		}

		// add additional shifts if they were already set
		$this->num_shifts_to_fill[$job_id] += $instances;
	}


	/**
	 * Get which shifts this worker has been assigned to work.
	 * @return array listing dates this worker has been assigned so far.
	 */
	public function getAssignedShifts() {
		return array_keys($this->num_shifts_to_fill);
	}

	public function getNumShiftsToFill() {
		return $this->num_shifts_to_fill;
	}


	/**
	 * Set the requests for this worker.
	 * @param[in] requests array of key => value pairs.
	 */
	public function setRequests($requests) {
		$this->requests = $requests;
	}

	/**
	 * Add an available date, tied to a shift.
	 *
	 * @param[in] job_id int the ID of the shift
	 * @param[in] date string date of availability
	 * @param[in] pref num the preference level of the worker. 
	 *     (prefer = 2, OK = 1, no response = .5, avoid = 0), see also
	 *     NON_RESPONSE_PREF.
	 */
	public function addAvailability($job_id, $date, $pref) {
		if (!isset($this->avail_shifts[$job_id])) {
			$this->avail_shifts[$job_id] = array();
		}

		$this->avail_shifts[$job_id][$date] = $pref;
	}


	/**
	 * Add default preferences for this worker who didn't respond to the
	 * survey.
	 *
	 * @param[in] dates_by_shift
	 */
	public function addNonResponsePrefs($meals_by_shift) {
		foreach($this->num_shifts_to_fill as $job_id=>$instances) {
			// sanity check
			if (!isset($meals_by_shift[$job_id])) {
				echo "shift {$job_id} doesn't have any dates!\n";
				exit;
			}

			foreach($meals_by_shift[$job_id] as $date) {
				$this->addAvailability($job_id, $date, NON_RESPONSE_PREF);
			}
		}
	}

	/**
	 * Get the worker's username
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Find out how many open slots this worker needs to fill yet for a given
	 * job ID.
	 */
	public function getNumShiftsOpen($job_id) {
		if (!isset($this->num_shifts_to_fill[$job_id])) {
			return 0;
		}

		$num_assigned = isset($this->assigned[$job_id]) ?
			count($this->assigned[$job_id]) : 0;
		return $this->num_shifts_to_fill[$job_id] - $num_assigned;
	}


	/**
	 * Get the ratio of this worker's availability / number of shifts they need
	 * to fill yet.
	 *
	 * @param[in] job_id int the number of the shift currently being assigned.
	 * @return number a ratio of this worker's availability per number of
	 *     shifts they need to fill yet.
	 */
	public function getNumAvailableShiftsRatio($job_id) {
		$open = $this->getNumShiftsOpen($job_id);
		if ($open < 1) {
			return 0;
		}

		if (!isset($this->avail_shifts[$job_id])) {
			return 0;
		}

		// number of shifts available to work / number of shifts they need filled
		return count($this->avail_shifts[$job_id]) / $open;
	}


	/**
	 * @return array list of strings / dates which have been assigned to this
	 * worker already.
	 */
	public function getDatesAssigned() {
		$dates = array();
		foreach($this->assigned as $job_id=>$d) {
			$dates = array_merge($dates, $d);
		}
		return array_unique($dates);
	}


	/**
	 * Generate an adjacency score to spread out assignments.
	 *
	 * @return adjanency score - higher when closer to an adjancent, previously
	 *     assigned date - 0 if more than the threshold away or none others
	 *     assigned yet.
	 */
	public function getAdjancencyScore($date) {
		// placeholders don't matter for adjacency
		if ($this->isPlaceholder()) {
			return 0;
		}

		$assigned = $this->getDatesAssigned();
		if (empty($assigned)) {
			return 0;
		}

		date_default_timezone_set('America/Detroit');
		$current = date('z', strtotime($date));

		$min = NULL;
		foreach($assigned as $a) {
			$ts = strtotime($a);
			$diff = abs(date('z', $ts) - $current);

			if (is_null($min)) {
				$min = $diff;
				continue;
			}

			if ($min > $diff) {
				$min = $diff;
			}
		}

		if (is_null($min) || ($min == 0)) {
			return 0;
		}

		// if far away, then return with 0, otherwise the ratio
		return ($min > $this->adjacency_limit) ? 0 :
			($this->adjacency_limit / $min);
	}


	/**
	 * Get the list of shifts that this worker is assigned for the date
	 * provided.
	 *
	 * @param[in] d string representing the date
	 * @return array of job IDs already assigned for a given date.
	 */
	public function getShiftsAssignedByDate($d) {
		$shifts = array();
		foreach($this->assigned as $job_id=>$dates) {
			if (in_array($d, $dates)) {
				$shifts[] = $job_id;
			}
		}

		return $shifts;
	}


	/*
	 * Check to see if this worker is available to work on this day.
	 * This checks for previously assigned shifts for the day, and if they want
	 * to cook and clean after themselves, as well as avoiding double-assigning
	 * someone to the same shift, i.e. bundling.
	 *
	 * @param[in] d string of the date
	 * @param[in] job_id int the job number
	 * @return int a numeric string representing any conflicts for the day.
	 *    -1 for HAS_CONFLICT
	 *     0 is OK, no conflict
	 *     3 to promote cleaning after cooking
	 *     5 to promote bundling
	 */
	public function getDateScore($d, $job_id) {
		$todays_shifts = $this->getShiftsAssignedByDate($d);

		// if not scheduled for today, then no conflict
		if (empty($todays_shifts)) {
			return 0;
		}

		// would they prefer to clean after themselves?
		// null = not set or don't care, true / false otherwise
		$clean_after_self = NULL;
		if (array_key_exists('clean_after_self', $this->requests)) {
			if ($this->requests['clean_after_self'] == 'yes') {
				$clean_after_self = TRUE;
			}
			else if ($this->requests['clean_after_self'] == 'no') {
				$clean_after_self = FALSE;
			}
		}

		$have_cooking = FALSE;
		$have_cleaning = FALSE;
		foreach($todays_shifts as $s) {
			if (is_a_cook_job($s)) {
				$have_cooking = TRUE;
			}
			if (is_a_clean_job($s)) {
				$have_cleaning = TRUE;
			}
		}

		$do_bundling = $this->wantsBundling();

		if (is_a_cook_job($job_id)) {
			// look to see if they already have a cooking job
			if ($have_cooking) {
				// if they want to bundle, then there's no conflict
				return $do_bundling ? 5 : HAS_CONFLICT;
			}

			// conflict found if they already have a cleaning shift and don't
			// want to cook before cleaning
			return (($have_cleaning) && ($clean_after_self === FALSE)) ?
				HAS_CONFLICT : 3;
		}

		// this is a cleaning job
		// look to see if they already have a cleaning job
		if ($have_cleaning) {
			// if they want to bundle, then there's no conflict
			return $do_bundling ? 5 : HAS_CONFLICT;
		}

		// conflict found if they already have a cooking shift and don't
		// want to clean up after themselves
		return (($have_cooking) && ($clean_after_self === FALSE)) ?
			HAS_CONFLICT : 3;
	}

	/**
	 * Find out whether this worker wants to bundle their shifts or not.
	 */
	public function wantsBundling() {
		return (array_get($this->requests, 'bundle_shifts') == TRUE);
	}

	/**
	 * Can this worker work on this date?
	 * Check to see if:
	 * - they have shifts available to fill for this job
	 * - they're assigned to this date already (another shift same day)
	 *
	 * @param[in] d string of the date
	 * @param[in] job_id int the job number
	 */
	public function isFullyAssigned($d, $job_id) {
		return ($this->getNumAvailableShiftsRatio($job_id) == 0);
	}


	/**
	 * Assign a shift to a worker. Note, returning false here will kill the
	 * run.
	 * @param[in] job_id int the number of the shift currently being assigned.
	 * @param[in] date string the date of the shift currently being assigned.
	 */
	public function setAssignedShift($job_id, $date) {
		if (($this->getNumShiftsOpen($job_id) < 1) &&
				($this->getUsername() != PLACEHOLDER)) {
			echo "$this->username doesn't have any more shifts to fill ($job_id, $date)!\n";
			return FALSE;
		}

		$this->assigned[$job_id][] = $date;
		unset($this->avail_shifts[$job_id][$date]);

		return TRUE;
	}

	/**
	 * Find if this worker has responded to the survey.
	 * @return boolean, If TRUE then the worker has some available shifts.
	 */
	public function hasResponded() {
		return !empty($this->avail_shifts);
	}


	/**
	 * Display the results for each worker.
	 *
	 * @param[in] only_unfilled_workers boolean if true, then only display the
	 *     workers and their jobs which have unfilled shifts.
	 * @return array list of jobs and number of shifts assigned
	 */
	public function printAssignmentsOfWorker($only_unfilled_workers=FALSE) {
		if (empty($this->assigned)) {
			return array();
		}

		$job_counts = array();

		$job_info = '';
		foreach($this->assigned as $job_id=>$dates) {
			$num_shifts = count($dates);
			natsort($dates);
			$job_counts[$job_id] = $num_shifts;
			$ratio = number_format($num_shifts /
				$this->num_shifts_to_fill[$job_id], 2);

			if (!$only_unfilled_workers || ($ratio != 1)) {
				$name = get_job_name($job_id);
				$dates_list = implode(', ', $dates);
				$to_fill = $this->num_shifts_to_fill[$job_id];
				$job_info .= <<<EOTXT
	j: [{$name}] [{$num_shifts}/{$to_fill}] ({$ratio}) {$dates_list}

EOTXT;
			}
		}

		if ($job_info != '') {
			print <<<EOTXT
name: {$this->username}
{$job_info}

EOTXT;
		}

		return $job_counts;
	}

	/**
	 * Find out which jobs this worker has been assigned.
	 */
	public function getTasks() {
		if (is_null($this->tasks)) {
			$this->loadTasks();
		}

		return $this->tasks;
	}

	/**
	 * Load the jobs this worker has been assigned from database and 
	 * overrides.
	 */
	protected function loadTasks() {
		global $all_jobs;
		$num_shift_overrides = get_num_shift_overrides();

		$each_job = array();
		foreach($all_jobs as $id=>$name) {
			$each_job[] = SURVEY_JOB_TABLE . ".id='{$id}'";
		}
		$each_job_sql = implode(' or ', $each_job);

		$sid = SEASON_ID;
		$jobs_table = SURVEY_JOB_TABLE;
		$assn_table = OFFERS_TABLE;
		$task_sql = <<<EOSQL
			select {$jobs_table}.description, {$jobs_table}.id, {$assn_table}.instances
				from {$jobs_table}, {$assn_table}
				where {$jobs_table}.id={$assn_table}.job_id and
					worker_id={$this->id} and
					{$assn_table}.type='a' and
					{$assn_table}.season_id={$sid} and
					( {$each_job_sql} );
EOSQL;

		$tasks = array();
		foreach ($this->dbh->query($task_sql) as $row) {
			$tasks[$row['id']] = $row;
		}

		$addl_jobs = array();
		if (isset($num_shift_overrides[$this->username])) {
			$additional = $num_shift_overrides[$this->username];
			foreach(array_keys($additional) as $id) {
				$addl_jobs[$id] = $all_jobs[$id];
			}
		}

		$this->tasks = $tasks + $addl_jobs;
	}

	/**
	 * Query the saved comments and other special requests.
	 */
	public function loadComments() {
		$table = SCHEDULE_COMMENTS_TABLE;
		$sql = <<<EOSQL
			SELECT *
				FROM {$table}
				WHERE worker_id={$this->getId()}
EOSQL;
		foreach ($this->dbh->query($sql) as $row) {
			$this->comments = $row;
			return;
		}

		$this->comments = array();
	}

	public function getComments() {
		if (is_null($this->comments)) {
			$this->loadComments();
		}

		return $this->comments;
	}

	public function getCommentsText() {
		$comments = $this->getComments();
		return array_key_exists('comments', $comments) ?
			stripslashes($comments['comments']) : '';
	}
}

?>
