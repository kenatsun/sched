<?php
define('PREFER_TO_AVOID_RATIO', .55);

define('DEFAULT_HOBART_SCORE', 7);
define('DEFAULT_AVAIL_SCORE', 5);
define('DEFAULT_AVOIDS_SCORE', 7);
define('DEFAULT_PREFERS_SCORE', 4);

require_once('../public/utils.php');

class Schedule {
	protected $meals = array();
	protected $roster;
	protected $job_id;
	protected $calendar;
	
	protected $jobs_from_db;

	protected $point_factors = array(
		'hobart' => DEFAULT_HOBART_SCORE,
		'avail' => DEFAULT_AVAIL_SCORE,
		'avoids' => DEFAULT_AVOIDS_SCORE,
		'prefers' => DEFAULT_PREFERS_SCORE,
	);

	// which shifts happen for which meals
	protected $meals_and_shifts = array();

	// the most difficult shifts to fill
	// date => job_id => counts
	protected $least_possible = array();

	// make this a member variable as a cache
	protected $meals_by_shift = array();

	public function __construct() {
		$this->calendar = new Calendar();
		$this->jobs_from_db = getJobsFromDB(SEASON_ID);
		if (0) deb("schedule.__construct(): this->jobs_from_db =", $this->jobs_from_db);
	}

	/**
	 * Assign various point-type variables.
	 * This is intended for changing the rules of the game, so that multiple
	 * runs can be processed and each one would turn out a little differently.
	 *
	 * @param[in] hobart_factor int the amount of points to assign towards the
	 *     hobart factor.
	 * @param[in] avail_factor int the amount of points to assign towards
	 *     availability.
	 * @param[in] avoids_factor int the amount of points to assign towards
	 *     avoids.
	 */
	public function setPointFactors($hobart_factor=NULL,
		$avail_factor=NULL, $avoids_factor=NULL) {

		if (!is_null($hobart_factor)) {
			$this->point_factors['hobart'] = $hobart_factor;
		}
		if (!is_null($avail_factor)) {
			$this->point_factors['avail'] = $avail_factor;
		}
		if (!is_null($avoids_factor)) {
			$this->point_factors['avoids'] = $avoids_factor;
			$this->point_factors['prefers'] =
				$this->point_factors['avoids'] * PREFER_TO_AVOID_RATIO;
		}
	}

	/**
	 * This is intended for changing the rules of the game, so that multiple
	 * runs can be processed and each one would turn out a little differently.
	 */
	public function getPointFactors() {
		return $this->point_factors;
	}

	/**
	 * Set the roster value for cross-referencing.
	 */
	public function setRoster($r) {
		$this->roster = $r;
	}

	/**
	 * Set the current job id.
	 */
	public function setJobId($job_id) {
		$this->job_id = $job_id;

		// reset the listing of availability for all meals
		$this->least_possible = [];
	}

	/**
	 * Get the job id
	 */
	public function getJobId() {
		return $this->job_id;
	}


	/**
	 * Figure out which days have meals, and which shifts are needed
	 * for those days. Create each of those meals instances with those shifts.
	 */
	public function initializeShifts() {
		$this->calendar->disableWebDisplay();
		$this->dates_and_shifts = $this->calendar->renderMealsInCalendar();
		if (0) deb("schedule.initializeShifts(): this->dates_and_shifts =", $this->dates_and_shifts);
		foreach($this->dates_and_shifts as $meal_id=>$shifts) {
			$num_meals = count($this->meals);
			$this->meals[$meal_id] = new Meal($this, $meal_id, $num_meals);
			$this->meals[$meal_id]->addShifts($shifts);
			if (0) deb("schedule.initializeShifts(): this->meals[date] =", $this->meals[$meal_id]);
		}
		if (0) deb("schedule.initializeShifts(): this->meals =", $this->meals);
		}


	/**
	 * Get the list of shifts (job IDs) and the dates listed for that type of
	 * job.
	 *
	 * @param array job_id => array( list of dates ).
	 */
	public function getDatesByShift() {
		if (empty($this->dates_by_shift)) {
			foreach($this->dates_and_shifts as $meal_id=>$shifts) {
				foreach($shifts as $index=>$job_id) {
					$this->dates_by_shift[$job_id][] = $meal_id;
				}
			}
		}

		return $this->dates_by_shift;
	}


	/**
	 * Add the list of possible workers for each shift and their preference
	 * value on a per-job-date basis.
	 *
	 * @param[in] username string the username.
	 * @param[in] job_id int the ID of the job.
	 * @param[in] meal_id string the id of the meal.
	 * @param[in] pref num the numeric value preference score.
	 */
	public function addPrefs($username, $job_id, $meal_id, $pref) {
		if (0) deb("schedule.addPrefs(): meal_id =", $meal_id);
		if (0) deb("schedule.addPrefs(): this->meals[0][id] =", $this->meals[0][$meal_id]);
		if (0) deb("schedule.addPrefs(): this->meals =", $this->meals);
		if (0) deb("schedule.addPrefs(): job_id = $job_id, meal_id = $meal_id, pref = $pref");
		
		// only add preferences for scheduled approved meals
		if (!isset($this->meals[$meal_id])) {
			return FALSE;
		}

		$this->meals[$meal_id]->addWorkerPref($username, $job_id, $pref);
		return TRUE;
	}


	/**
	 * Add a set of default preferences for the slackers who didn't respond to
	 * the work survey.
	 * XXX: maybe this should be moved to Roster.
	 *
	 * @param[in] slackers array list of usernames of people who didn't take
	 * their survey.
	 */
	public function addNonResponderPrefs($slackers) {
		$meals_by_shift = $this->getDatesByShift();
		if (0) deb("schedule.addNonResponderPrefs(): this->meals[meal_id] = ", $this->meals[$meal_id]);

		foreach($slackers as $username) {
			$w = $this->getWorker($username);
			if (is_null($w)) {
				echo "worker $username does not exist\n";
				exit;
			}
			$shifts_assigned = $w->getAssignedShifts();
			$pref = NON_RESPONSE_PREF;

			foreach($shifts_assigned as $job_id) {
				// figure out which dates and shifts to assign
				$d_by_s = $meals_by_shift;
				foreach ($d_by_s[$job_id] as $meal_id) {
					if (!isset($this->meals[$meal_id])) {
						echo "meal for date:{$meal_id} doesn't exist\n";
						exit;
					}

					$m = $this->meals[$meal_id];
					$m->addWorkerPref($username, $job_id, $pref);
				}
			}
		}
	}


	/**
	 * Check to see if the assignments for this job have been completed
	 */
	public function isFinished() {
		foreach($this->meals as $meal_id=>$meal) {
			if ($meal->hasOpenShifts($this->job_id)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	public function getPossibleRatios() {
		return $this->least_possible;
	}

	/**
	 * Sort the various meals to find the one which will be the most difficult
	 * to fill.
	 */
	public function sortPossibleRatios() {
		// don't re-generate the list
		if (!empty($this->least_possible)) {
			return;
		}

		$j = $this->job_id;

		$prev = empty($this->least_possible) ? 
			array_keys($this->meals) :
			array_keys($this->least_possible);
		$this->least_possible = [];

		foreach($prev as $meal_id) {
			$m = $this->meals[$meal_id];

			// skip dates which don't need workers
			if (!$m->hasOpenShifts($j)) {
				continue;
			}

			// get number of possible workers for this date/shift
			$poss = $m->getNumPossibleWorkerRatio($j);
			// shift filled - move along
			if (($poss == 0) || is_null($poss)) {
				continue;
			}

			// uh oh - not enough workers!
			if ($poss < 1) {
				$job_name = get_job_name($j);
				echo <<<EOTXT
D:{$meal_id}, job:{$j} {$job_name} may not have enough workers: {$poss}

EOTXT;
				continue;
			}

			// record the possibility ratio
			$this->least_possible[$meal_id] = $poss;
		}

		if (empty($this->least_possible)) {
			return FALSE;
		}

		asort($this->least_possible);
		return TRUE;
	}


	/**
	 * Of the available workers for this shift, choose one and assign it.
	 * Note, this isn't solely based on availability, but also proximity of
	 * other assignments and user requests, etc.
	 *
	 * @param[in] worker_freedom the array of workers and their difficulty to
	 *     assign ratios.
	 * @return boolean. If TRUE, then the meal was filled successfully.
	 */
	public function fillMeal($worker_freedom) {
		$job_id = $this->job_id;

		$meal_id = get_first_associative_key($this->least_possible);
		if ($meal_id == '') {
			echo "EMPTY MEAL ID\n";
			return FALSE;
		}
		$meal = $this->meals[$meal_id];
		$username = $meal->fill($job_id, $worker_freedom);
		if (is_null($username)) {
			echo "null user\n";
			return FALSE;
		}

		// update the current meal's possibility ratio
		$poss = $meal->getNumPossibleWorkerRatio($job_id);
		if ($poss == 0) {
			unset($this->least_possible[$meal_id]);
		}
		else {
			$this->least_possible[$meal_id] = $poss;
			asort($this->least_possible);
		}

		$worker = $this->getWorker($username);
		// if this wasn't able to be filled, don't do the rest of the steps
		if ($username == PLACEHOLDER) {
			return TRUE;
		}

		// update this worker's availability
		if (!($worker->setAssignedShift($job_id, $meal_id))) {
			echo "unable to set assigned shift!\n";
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Get the worker object by username from the roster.
	 * @return Worker object.
	 */
	public function getWorker($username) {
		return $this->roster->getWorker($username);
	}


	/**
	 * Find out how many placeholders have been assigned.
	 * @return int number of placeholders for the schedule.
	 */
	public function getNumPlaceholders() {
		$count = 0;
		foreach($this->meals as $m) {
			$count += $m->getNumPlaceholders();
		}

		return $count;
	}


	/**
	 * Display the schedule
	 * @param[in] format string the chosen output format: How the
	 *     output should be displayed.
	 */
	public function printMealTeamSchedule($format='txt') {	
		if ($format === 'txt') {
			$this->printTabbedHeaders();
			$count = $this->getNumMeals();
			echo "MISSED HOBARTERS: {$missed_hobarters} of {$count} " . 
				round($missed_hobarters / $count, 2) . "\n";
		}
		elseif ($format === 'html') {
			$schedule_table = $this->renderHTMLHeaders();
			foreach($this->meals as $meal_id=>$meal) {
				$schedule_table .= $meal->printMealTeam($format);
			}
			$schedule_table .= $this->renderHTMLFooter();
			return $schedule_table;
		}
		else {
			$missed_hobarters = 0;
			foreach($this->meals as $meal_id=>$meal) {
				if (!$meal->printMealTeam($format)) {
					$missed_hobarters++;
				}
			}
		}
	}

	/**
	 * Display table headers
	 * XXX Unforunately, these are hard-coded for now.
	 */
	public function printTabbedHeaders() {
		echo "date\thead_cook\tasst1\tasst2\tcleaner1\tcleaner2\tcleaner3\ttable_setter\n";
	}

	public function renderHTMLHeaders() {
		$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . SEASON_ID, "display_order asc", (0), "schedule.renderHTMLHeaders()");
		$column_heads = "<th>date</th>";
		foreach($jobs as $job) {
			$column_heads .= "<th>" . $job['description'] . "</th>";
		}
		$table_header = "";
		$table_header .= '<table style="width:50%; font-size:11pt; border-collapse: collapse;"><tr>' . $column_heads . '</tr>';
		return $table_header;
	}

	public function renderHTMLFooter() {
		return "</table>";
	}

	/**
	 * Count the number of meals in the schedule
	 * @return int number of meals
	 */
	public function getNumMeals() {
		$count = 0;
		foreach($this->meals as $meal_id=>$meal) {
			if (is_object($meal)) {
				$count++;
			}
		}
		return $count;
	}


	/**
	 * Get the assignments array.
	 * @return array indexed by date, referring to the list of workers assigned
	 *     to jobs for the meal on that date.
	 */
	public function getAssigned() {
		$assignments = array();
		foreach($this->meals as $meal_id=>$meal) {
			$assignments[$meal_id] = $meal->getAssigned();
		}
		return $assignments;
	}
}
?>
