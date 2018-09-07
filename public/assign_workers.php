<?php
// -----------------------------------
class Roster {
	protected $workers = array();
	protected $job_id;

	// job_id => username => counts
	protected $least_available = array();


	/**
	 * #!# start using this instead of so many parameters!!!
	 */
	public function setJobId($job_id) {
		$this->job_id = $job_id;
	}

	/**
	 *
	 */
	public function addWorker($username, $job_id, $date, $pref) {
		if (!array_key_exists($username, $this->workers)) {
			$this->workers[$username] = new Worker($username);
		}
		$this->workers[$username]->addAvailability($job_id, $date, $pref);
	}

	/**
	 *
	 */
	public function sortAvailable() {
		$j = $this->job_id;

		foreach($this->workers as $u=>$w) {
			$avail = $w->getNumAvailableShiftsRatio($j);
			if (is_null($avail) || ($avail == 0)) {
				continue;
			}

			if (!isset($this->least_available[$j])) {
				$this->least_available[$j] = array();
			}
			$this->least_available[$j][$u] = $avail;
		}

		asort($this->least_available[$j]);
		return $this->least_available[$j];
	}

	/**
	 *
	 */
	public function setNumberOfShifts($username, $num, $job_id) {
		$w = $this->workers[$username];
		if (!is_object($w)) {
			return;
		}
		$w->setNumberOfShifts($job_id, $num);
	}

	public function getWorker($username) {
		return $this->workers[$username];
	}

	/**
	 *
	 */
	public function printResults() {
		ksort($this->workers);
		$first = TRUE;
		foreach($this->workers as $username=>$w) {
			$w->printResults($first);
			$first = FALSE;
		}
	}
}

class Worker {
	protected $username;

	// array of job_id => date => pref
	protected $avail_shifts = array();

	// job_id => array(dates)
	protected $assigned = array();

	// job_id => count
	protected $num_shifts_to_fill = array();


	/**
	 *
	 */
	public function __construct($username) {
		$this->username = $username;
	}

	/**
	 *
	 */
	public function setNumberOfShifts($job_id, $num) {
		// multiply by the number of months in the season:
		$current_season = get_current_season_months();
		$this->num_shifts_to_fill[$job_id] = $num * 
			count($current_season);
	}

	/**
	 *
	 */
	public function addAvailability($job_id, $date, $pref) {
		if (!array_key_exists($job_id, $this->avail_shifts)) {
			$this->avail_shifts[$job_id] = array();
		}

		$this->avail_shifts[$job_id][$date] = $pref;
	}

	/**
	 *
	 */
	public function getNumAvailableShiftsRatio($job_id) {
		if (!isset($this->num_shifts_to_fill[$job_id]) ||
			($this->num_shifts_to_fill[$job_id] == 0)) {
			return NULL;
		}

		// number of shifts can work / number of shifts they need filled
		return (count($this->avail_shifts[$job_id]) / 
			$this->num_shifts_to_fill[$job_id]);
	}

	/**
	 *
	 */
	public function setAssignedShift($job_id, $date) {
		if ($this->num_shifts_to_fill[$job_id] < 1) {
			echo "$this->username doesn't have any more shifts to fill ($job_id, $date)!\n";
			return FALSE;
		}

		$this->assigned[$job_id][] = $date;
		$this->num_shifts_to_fill[$job_id]--;
		unset($this->avail_shifts[$job_id][$date]);

		return TRUE;
	}

	/**
	 *
	 */
	public function printResults($first) {
		if (empty($this->assigned)) {
			return;
		}

		if ($first) {
			print_r($this);
		}

		$out = '';
		foreach($this->assigned as $job_id=>$dates) {
			$out .= " $job_id: " . implode(', ', $dates) . "\n";
		}
		print "$this->username\n{$out}\n"; 
	}
}

?>
