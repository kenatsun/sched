<?php
define('AVOID_PERSON', -2);
define('PREFER_PERSON', 1);

class Meal {
	protected $schedule;
	protected $date;
	protected $day_of_week;

	// ratio threshold below which 'ok' should trump 'prefer'
	protected $prefer_threshold = 1.5;

	// array of username => pref
	protected $possible_workers = array();

	// username string
	protected $assigned = array();

	// unique meal ID
	protected $meal_num;


	/**
	 * Initialize a meal.
	 * @param[in] schedule Schedule object.
	 * @param[in] date string a date string which looks like '5/6/2013'
	 * @param[in] meal_num int a unique number for this meal
	 */
	public function __construct($schedule, $date, $meal_num) {
		$this->schedule = $schedule;
		$this->setDate($date);
		$this->meal_num = $meal_num;
	}

	public function setDate($d) {
		$this->date = $d;
		$this->day_of_week = date('N', strtotime($d));
	}

	public function getDate() {
		return $this->date;
	}

	public function getDayOfWeek() {
		return $this->day_of_week;
	}

	/**
	 * Add an empty slot for each shift to be filled for this job type.
	 * Example: weekday asst cooks should get 2 empty slots to fill.
	 */
	public function addShifts($job_id_list) {
		$job_instances = get_job_instances();

		foreach(array_values($job_id_list) as $job_id) {
			if (empty($job_instances[$job_id])) {
				continue;
			}

			// fill in the number of open shifts
			$num = $job_instances[$job_id][$this->day_of_week];
			for($i=0; $i<$num; $i++) {
				$this->assigned[$job_id][] = NULL;
			}
		}
	}


	/**
	 * Add a name to the list of possible workers for a given job, with their
	 * preference number.
	 */
	public function addWorkerPref($username, $job_id, $pref) {
		// only add prefs for shifts which are defined on this date.
		if (!isset($this->assigned[$job_id])) {
			global $all_jobs;
			if (!isset($all_jobs[$job_id])) {
				echo "Could not find JOB ID: {$job_id}\n";
				exit;
			}

			$all_jobs_out = print_r($all_jobs, TRUE);
			$assn_out = print_r($this->assigned, TRUE);
			echo <<<EOTXT
The job "{$all_jobs[$job_id]}" isn't scheduled for this date: {$this->date}
U:{$username} P:{$pref}
all jobs: {$all_jobs_out}
assigned: {$assn_out}

EOTXT;
			exit;
		}

		$this->possible_workers[$job_id][$username] = $pref;
	}


	/**
	 * Find out how many slots are empty for a given job id.
	 */
	public function getNumOpenSpacesForShift($job_id) {
		if (empty($this->assigned[$job_id])) {
			echo "no jobs assigned for this meal / job: D:{$this->date}, J:{$job_id}\n";
			exit;
		}

		$count = 0;
		foreach($this->assigned[$job_id] as $worker) {
			if (is_null($worker)) {
				$count++;
			}
		}
		return $count;
	}


	/**
	 * Find the popularity vs open spaces ratio for this meal's job.
	 * Indicator of how difficult it will be to fill this meal.
	 *
	 * @param[in] job_id int ID of the meal's job
	 * @return float the popularity / open spot ratio
	 */
	public function getNumPossibleWorkerRatio($job_id) {
		$job_instances = get_job_instances();

		// check to see if this is the wrong date for this job
		if (!isset($job_instances[$job_id][$this->day_of_week]) || 
			($job_instances[$job_id][$this->day_of_week] == 0)) {
			return 0;
		}

		$open_spaces = $this->getNumOpenSpacesForShift($job_id);
		if ($open_spaces == 0) {
			return 0;
		}

		// check that workers are defined
		if (empty($this->possible_workers[$job_id])) {
			$job_name = get_job_name($job_id);
			echo <<<EOTXT
no possible workers defined for job {$job_id}, {$job_name}, {$this->date}

EOTXT;
			return 0;
		}

		return count($this->possible_workers[$job_id]) / $open_spaces;
	}


	/**
	 * Find if this meal has shifts yet to be filled.
	 * (Meal)
	 */
	public function hasOpenShifts($job_id) {
		$job_instances = get_job_instances();

		// if this day of week isn't defined. For example, a sunday shift on a
		// weekday...
		if (!isset($job_instances[$job_id][$this->day_of_week])) {
			return FALSE;
		}

		// if this day of week has no shifts to fill
		$num_to_fill = $job_instances[$job_id][$this->day_of_week];
		if ($num_to_fill == 0) {
			return FALSE;
		}

		// if there are no shift slots for this job
		// e.g. meeting nights are only on mon & wed, but not EVERY mon & wed
		if (empty($this->assigned[$job_id])) {
			return FALSE;
		}

		// if the number of assignments is full - all assigned
		$count = 0;
		foreach($this->assigned[$job_id] as $worker) {
			if (!is_null($worker)) {
				$count++;
			}
		}
		if ($count == $num_to_fill) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Get list of workers who should be avoided for this date based on anyone
	 * who is already assigned to this meal.
	 *
	 * @param[in] job_id int the number of the job to get preferences for.
	 * @return array key-value pairs, one for 'avoid', another for 'prefer'.
	 */
	protected function getAvoidAndPreferWorkerList($job_id,
		$assigned_worker_names) {
		if (empty($assigned_worker_names)) {
			return array();
		}

		$assigned_worker_objects = $this->getAssignedWorkerObjectsByJobId(
			$job_id, $assigned_worker_names);

		$avoid_list = array();
		$prefer_list = array();
		foreach($assigned_worker_objects as $worker) {
			// get list of names worker does not want to work with
			// Array ( [0] => aaron, [1] => nancy)
			$av_list = $worker->getAvoids();
			if (!empty($av_list)) {
				$avoid_list = array_merge($avoid_list, $av_list);
			}

			// get list of names worker wants to work with
			$pr_list = $worker->getPrefers();
			if (!empty($pr_list)) {
				$prefer_list = array_merge($prefer_list, $pr_list);
			}
		}

		$avoids = array();
		if (!empty($avoid_list)) {
			// flip from a list to an associative array of name => AVOID_PERSON
			// AVOIDS: Array ( [aaron] => -2, [nancy] => -2 )
			$avoids = array_combine(array_values($avoid_list),
				array_fill(0, count($avoid_list), AVOID_PERSON));
		}

		$prefers = array();
		if (!empty($prefer_list)) {
			// flip from a list to an associative array of name => PREFER_PERSON
			$prefers = array_combine(array_values($prefer_list),
				array_fill(0, count($prefer_list), PREFER_PERSON));
		}

		// look for contention of preferences. Resolve by combining the two.
		if (!empty($avoids) && !empty($prefers)) {
			foreach($avoids as $name=>$value) {
				if (isset($prefers[$name])) {
					unset($prefers[$name]);
					$avoids[$name] = AVOID_PERSON + PREFER_PERSON;
				}
			}
		}

		return array(
			'avoids' => $avoids,
			'prefers' => $prefers,
		);
	}

	/**
	 * Run through each eligible worker for this job, and pick one based on
	 * various points, characteristics, etc.
	 *
	 * @param[in] job_id int the number of the current job to fill
	 */
	protected function pickWorker($job_id, $worker_freedom) {
		$worker_points = [];

		$assigned_worker_names = $this->getAssignedWorkerNamesByJobId($job_id);
		$list = $this->getAvoidAndPreferWorkerList($job_id,
			$assigned_worker_names);

		// find the value of each characteristic (globally set per instance)
		$point_factors = $this->schedule->getPointFactors();

		// if the person has marked a lot of people to avoid or prefer to work
		// with, then that will carry less weight than if they only mark 1
		$avoid_point_factor = empty($list['avoids']) ? 1 :
			($point_factors['avoids'] / count($list['avoids']));
		$prefer_point_factor = empty($list['prefers']) ? 1 :
			($point_factors['prefers'] / count($list['prefers']));

		/*
		 * Walk down the list of people's availability, and find out who is
		 * able to work. If they are, then assign points and ultimately sort on
		 * those points to find the best worker for this slot.
		 */
		foreach($worker_freedom as $username=>$avail_pref) {
			// initialize
			$drawbacks = $promotes = 0;

			// skip if this worker can't work on this day
			if (!isset($this->possible_workers[$job_id][$username])) {
				continue;
			}

			$worker = $this->schedule->getWorker($username);

			// skip if this worker is fully assigned
			if ($worker->isFullyAssigned($this->date, $job_id)) {
				continue;
			}

			$today = $worker->getDateScore($this->date, $job_id);
			// skip if there's a date conflict
			if ($today == HAS_CONFLICT) {
				continue;
			}
			$promotes += $today;

			// #!# unfortunately, bundling doesn't seem to work because we're
			// only examining each worker once...

			// if a worker has an availability rating of 1 or less, then they
			// must get this assignment, otherwise they'll end up with fewer
			// assignments than necessary.
			if ($avail_pref <= 1) {
				return $username;
			}

			// try to promote one hobarter per shift, only look at group
			// cleaning shifts (i.e. not meeting nights)
			if (is_a_group_clean_job($job_id) && is_a_hobarter($username)) {
				// spread out hobarters
				if ($this->isHobarterAssignedToShift($job_id)) {
					$drawbacks += $point_factors['hobart'];
				}
				else {
					$promotes += $point_factors['hobart'];
				}
			}

			// check to see if others already assigned to this meal have marked
			// prefer or avoid for the current person
			if (isset($list['avoids'][$username])) {
				$drawbacks += $avoid_point_factor;
			}
			else if (isset($list['prefers'][$username])) {
				$promotes += $prefer_point_factor;
			}

			// check to see if the current person wants to avoid anyone who
			// is currently assigned...
			$worker_avoids = $worker->getAvoids();
			if (!empty($worker_avoids)) {
				foreach($worker_avoids as $avoid_person) {
					if (isset($assigned_worker_names[$avoid_person])) {
						$drawbacks += $point_factors['avoids'];
					}
				}
			}

			// look at workers who marked 'prefer'
			$promotes += $this->possible_workers[$job_id][$username];

			// conjure up a worker point rating
			$adjacent = $worker->getAdjancencyScore($this->date);
			$denominator = ($drawbacks + $adjacent) * $avail_pref;
			$worker_points[$username] = ($denominator == 0) ?
				$promotes : ($promotes / $denominator);
		}

		// a higher score is better
		arsort($worker_points);
		$username = get_first_associative_key($worker_points);

		// may need to insert a placeholder for later manual correction
		return is_null($username) ? PLACEHOLDER : $username;
	}

	/**
	 * Find a worker who can take a shift for this job.
	 *
	 * @param[in] job_id int the number of the shift we're trying to
	 *     assign
	 * @param[in] worker_freedom array of username => num possible
	 *     shifts ratio
	 *
	 * @return string username or NULL if assignment failed
	 */
	public function fill($job_id, $worker_freedom) {
		// don't add anymore workers, this meal is fully assigned
		if (!$this->hasOpenShifts($job_id)) {
			echo "this meal {$this->date} $job_id is filled\n";
			sleep(1 );
			return NULL;
		}

		// get the name of the worker to fill this slot with
		$username = $this->pickWorker($job_id, $worker_freedom);

		// assign to the first available shift slot
		$is_available = FALSE;
		foreach($this->assigned[$job_id] as $key=>$w) {
			if (!is_null($w)) {
				// slot is taken already
				continue;
			}

			$is_available = TRUE;
			break;
		}

		if (!$is_available) {
			echo "all slots are full\n";
			return PLACEHOLDER;
		}

		$this->assigned[$job_id][$key] = $username;
		if ($username == PLACEHOLDER) {
			return $username;
		}

		$worker = $this->schedule->getWorker($username);

		// remove from this meal's pool if not bundling
		if (!$worker->wantsBundling()) {
			unset($this->possible_workers[$job_id][$username]);
		}
// /* #!# disabled for now... not sure this works properly
		// // if the first assignment, consider bundling
		// else if ($key == 0) {
			// $num_needed = count($this->assigned[$job_id]) - ($key + 1);
			// // first, skip if this job is fully staffed already
			// if ($num_needed < 1) {
				// return $username;
			// }

			// // Make sure the worker needs enough shifts to fulfill the bundle
			// $to_fill = $worker->getNumShiftsOpen($job_id);
			// if ($to_fill < $num_needed) {
				// return $username;
			// }

// #!# update remaining shifts left
			// // do the bundling
			// foreach($this->assigned[$job_id] as $num=>$w) {
				// if ($num == $key) {
					// continue;
				// }

				// $this->assigned[$job_id][$num] = $username;

				// // #!# this breaks shit... but without it, people get
				// // over-assigned.
				// // $worker->setAssignedShift($job_id, $this->date);
			// }
		// }
// */

		return $username;
	}


	/**
	 * Is a worker a hobarter?
	 * @return boolean if true, then a hobarter is already assigned to the
	 *     shift.
	 */
	public function isHobarterAssignedToShift($job_id) {
		foreach($this->assigned[$job_id] as $worker) {
			// don't count un-assigned shifts
			if (is_null($worker) || ($worker == PLACEHOLDER)) {
				continue;
			}

			if (is_a_hobarter($worker)) {
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * Find out how many placeholders have been assigned to this meal.
	 * @return int number of placeholders for this meal.
	 */
	public function getNumPlaceholders() {
		$count = 0;
		foreach($this->assigned as $job_id=>$assignments) {
			foreach($assignments as $assn) {
				if ($assn == PLACEHOLDER) {
					$count++;
				}
			}
		}

		return $count;
	}


	/**
	 * Display the assigned workers for this meal.
	 *
	 * @param[in] format string the chosen output format (txt, sql,
	 *     or csv). How the output should be displayed.
	 * @return boolean, if false then a hobart shift was needed and not filled
	 *     with a hobarter. TRUE either means it was filled or not needed.
	 */
	public function printResults($format='txt') { 
		if (empty($this->assigned)) {
			return;
		}

		$hobarters = get_hobarters();

		// testing flags:
		$only_cleaners = FALSE;
		$has_clean_job = FALSE;
		$hobarter_found = FALSE;

		$is_mtg_night_job = FALSE;
		$out_jobs = array();
		global $all_jobs;

		// check to make sure that all of the required instances are filled
		// $all_jobs_from_db = getJobsFromDB(SEASON_ID);
		foreach($this->assigned as $job_id=>$assignments) {
			// check for un-assigned names
			foreach($assignments as $shift_num=>$name) {
				if (is_null($name)) {
					$assignments[$shift_num] = PLACEHOLDER;
				}
			}

			if (is_a_mtg_night_job($job_id)) {
				$is_mtg_night_job = TRUE;
				/*
				 * Pad the assignments array for output since meeting
				 * nights are missing some shifts.
				 */
				$assignments[] = '';
				$assignments[] = '';
			}

			$order = NULL;
			if (is_a_head_cook_job($job_id)) {
				$order = 0;
			}
			else if (is_a_cook_job($job_id)) {
				$order = 1;
			}
			else if (is_a_clean_job($job_id)) {
				$order = 2;
				if (!$is_mtg_night_job) {
					foreach($assignments as $shift_num=>$name) {
						if (is_a_hobarter($name)) {
							$hobarter_found = TRUE;
							break;
						}
					}
					$has_clean_job = TRUE;
				}
			}
			else {
				$order = 4;
			}

			if (($only_cleaners) && ($order != 2)) {
				continue;
			}

			switch($format) {
			case 'txt':
				$line = implode("\t", $assignments);
				$out_jobs[$order] = $line;
				break;
			case 'sql':
			case 'csv':
				$out_jobs = array_merge($out_jobs, $assignments);
				break;			
			}
		}
		ksort($out_jobs);

		switch($format) {
		case 'txt':
			print "$this->date\t" . implode("\t", $out_jobs) . "\n";
			break;

		case 'sql':
			$cols = ($is_mtg_night_job) ? '(meal_date, cook, cleaner1)' :
				'(meal_date, cook, asst1, asst2, cleaner1, cleaner2, cleaner3)';
			$workers = array();
			foreach($out_jobs as $j) {
				$workers[] = "'{$j}'";
			}
			$names = implode(', ', $workers);

			print "insert into go_meal {$cols} values ('{$this->date}', {$names});\n";
			break;

		case 'csv':
			print "$this->date," . implode(',', $out_jobs) . "\n";
			break;
			
		case 'db':
			$this->insertAssignmentIntoDB();			
		}
	
		// did a hobart shift go unfilled?
		return (!$has_clean_job || $hobarter_found);
	}


	/**
	 * For testing, return the list of assigned workers for this meal.
	 */
	public function getAssigned() {
		return $this->assigned;
	}


	/**
	 * Get the list of workers who are assigned to this (or related)
	 * shift(s).
	 *
	 * @param[in] job_id int the number of the current job being requested.
	 * @return array list of string / usernames of people currently assigned
	 *     for this meal and this type of job.
	 */
	public function getAssignedWorkerNamesByJobId($job_id) {
		$is_cleaning = is_a_clean_job($job_id);

		$names = array();
		foreach($this->assigned as $jid=>$job) {
			$j_clean = is_a_clean_job($jid);
			if ($is_cleaning !== $j_clean) {
				continue;
			}

			foreach($job as $shift_num=>$username) {
				if (is_null($username)) {
					continue;
				}
				$names[$username] = 1;
			}
		}
		return $names;
	}

	/**
	 * Get the list of worker objects who are assigned to the same job type. If
	 * the list of names is supplied, then don't look up the list of usernames.
	 *
	 * @param[in] job_id int the number of the current job being requested.
	 * @return array list of worker objects currently assigned
	 *     for this meal and this type of job.
	 */
	public function getAssignedWorkerObjectsByJobId($job_id, $names=array()) {
		if (empty($names)) {
			$names = $this->getAssignedWorkerNamesByJobId($job_id);
		}

		$workers = array();
		foreach ($names as $n=>$unused) {
			$w = $this->schedule->getWorker($n);
			if (!is_null($w)) {
				$workers[] = $w;
			}
		}
		return $workers;
	}
	
	public function insertAssignmentIntoDB() {
		global $all_jobs;
		global $scheduler_timestamp;
		global $scheduler_run_id;
		$season_id = SEASON_ID;
		if (0) debt("meal.insertAssignmentIntoDB(): this->assigned =", $this->assigned);
		// for each job
		foreach($this->assigned as $job_id=>$assignments) {
			$job_description = $all_jobs[$job_id];
			if (0) debt("meal.insertAssignmentIntoDB(): job_id =", $job_id);
			if (0) debt("meal.insertAssignmentIntoDB(): job_description =", $job_description);
			if (0) debt("meal.insertAssignmentIntoDB(): assignments =", $assignments);
			// Get id of job from database based on description
			$db_job_id = sqlSelect("id", "jobs", "description = '{$job_description}' and season_id = {$season_id}", "")[0]['id'];
			if (!db_job_id) debt("meal.insertAssignmentIntoDB(): ERROR no job id found for job named '{$job_description}'.");
			// Get id of the shift 
			$db_shift_id = sqlSelect("id", "shifts", "job_id = '{$db_job_id}' and string = '{$this->date}'", "")[0]['id'];
			if (!db_shift_id) debt("meal.insertAssignmentIntoDB(): ERROR no shift id found for shift with job_id = '{$db_job_id}' and string = '{$this->date}'.");
			// for each assignment to that job
			foreach($assignments as $assignment_key=>$person) {
				// $db_shift_id = sqlSelect("id", "shifts", "job_id = '{$db_job_id}' and string = '{$this->date}'", "")[0]['id'];
				// if (!db_shift_id) debt("meal.insertAssignmentIntoDB(): ERROR no shift id found for shift with job_id = '{$db_job_id}' and string = '{$this->date}'.");
				// Get id of worker from database based on username
				$db_worker_id = sqlSelect("id", "workers", "username = '{$person}'", "")[0]['id'];	
				if (!db_worker_id) debt("meal.insertAssignmentIntoDB(): ERROR no worker id found for worker usernamed '{$person}'.");
			$table = ASSIGNMENTS_TABLE;
			$columns = "shift_id, worker_id, scheduler_timestamp, season_id, scheduler_run_id";
			$values = "{$db_shift_id}, {$db_worker_id}, '{$scheduler_timestamp}', {$season_id}, {$scheduler_run_id}";
			$rows_affected = sqlReplace($table, $columns, $values, (0));
				if (0) debt("meal.insertAssignmentIntoDB(): this->date = {$this->date}");
				if (0) debt("meal.insertAssignmentIntoDB(): assignment_key = $assignment_key");
				if (0) debt("meal.insertAssignmentIntoDB(): job_id = $job_id");
				if (0) debt("meal.insertAssignmentIntoDB(): person = $person");
				if (0) debt("meal.insertAssignmentIntoDB(): db_job_id = $db_job_id");
				if (0) debt("meal.insertAssignmentIntoDB(): season_id = $season_id");
				if (0) debt("meal.insertAssignmentIntoDB(): db_shift_id = $db_shift_id");
				if (0) debt("meal.insertAssignmentIntoDB(): db_worker_id = $db_worker_id");
				if (0) debt("meal.insertAssignmentIntoDB(): rows_affected = $rows_affected");
			}
		}
		if (0) debt("meal.insertAssignmentIntoDB(): rows in table =", sqlSelect("count(*)", "assignments", "", "")[0]['count(*)']);
	}
}

?>
