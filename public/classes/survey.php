<?php

require_once 'globals.php';
require_once 'utils.php';
require_once 'display/includes/header.php';

require_once 'classes/calendar.php';
require_once 'classes/roster.php';
require_once 'classes/worker.php';
require_once 'classes/WorkersList.php';

class Survey {
	protected $worker;
	protected $calendar;
	protected $roster;

	protected $name;
	protected $username;
	protected $worker_id;

	protected $dbh;

	protected $avoid_list;
	protected $prefer_list;
	protected $saved = 0;
	protected $summary;
	protected $results = array(
		0 => array(),
		1 => array(),
		2 => array(),
	);

	// track the number of jobs marked positively
	protected $positive_count = array();

	protected $requests = array(
		'clean_after_self' => '',
		'bunch_shifts' => '',
		'comments' => '',
		'bundle_shifts' => '',
	);

	protected $is_save_request = FALSE;

	protected $insufficient_prefs_msg;


	public function __construct() {
		$this->calendar = new Calendar();
		$this->roster = new Roster();

		global $dbh;
		$this->dbh = $dbh;
	}

	/**
	 * Set the assigned worker, and load their info from the database / config
	 * overrides.
	 *
	 * @param[in] workername string, the username of this user.
	 * @param[in] worker_id int, the unique ID for this worker.
	 * @param[in] first_name string, the first name of this worker.
	 * @param[in] last_name string, the last name of the worker.
	 */
	public function setWorker($username=NULL, $worker_id=NULL,
		$first_name=NULL, $last_name=NULL) {

		if (is_null($username)) {
			if (is_null($this->username)) {
				echo "Missing username in setWorker!\n";
				exit;
			}
			$username = $this->username;
		}

		if (is_null($worker_id)) {
			if (is_null($this->worker_id)) {
				echo "Missing worker_id in setWorker!\n";
				exit;
			}
			$worker_id = $this->worker_id;
		}

		$this->roster->loadNumShiftsAssigned($username);
		$this->worker = $this->roster->getWorker($username);
		if (is_null($this->worker)) {
			$this->reportNoShifts();
		}

		$this->worker->setId($worker_id);

		if (!is_null($first_name) || !is_null($last_name)) {
			$this->name = "{$first_name} {$last_name}";
			$this->worker->setNames($first_name, $last_name);
		}
	}

	/**
	 * Get the list of workers.
	 * @return array list of workers.
	 */
	public function getWorkers() {
		$workers_list = new WorkersList();
		return $workers_list->getWorkers();
	}

	/**
	 * Get a select list of the various workers available.
	 * @param [in] id string, denotes name of DOM element and form element
	 *     name.
	 * @param[in] first_entry boolean, defaults to FALSE, if true,
	 *     then pre-pend the list with a blank entry.
	 * @param[in] skip_user string (defaults to NULL), if not null, then don't
	 *     display this users' name in the list.
	 * @param[in] chosen_users array specifies as list of chosen usernames.
	 * @param[in] only_user boolean (default FALSE), if true, then instead of a
	 *     "(x) remove" link, display a "clear" link.
	 */
	public function getWorkerList($id, $first_entry=FALSE, $skip_user=NULL,
		$chosen_users=array(), $only_user=FALSE) {

		return $this->calendar->getWorkerList($id, $first_entry,
			$skip_user, $chosen_users, $only_user);
	}

	/**
	 * Get the list of tasks by id, their name and the number of instances this
	 * worker was assigned.
	 */
	public function getShifts() {
		$tasks = $this->worker->getTasks();
		$shifts = $this->worker->getNumShiftsToFill();

		$out = array();
		foreach($tasks as $id=>$name) {
			$out[$id] = array(
				'name' => $name,
				'instances' => $shifts[$id],
			);
		}
		return $out;
	}


	public function getShiftsSummaryHtml() {
		$shifts = $this->getShifts();
		if (empty($shifts)) {
			return NULL;
		}

		$out = '';
		foreach($shifts as $id=>$info) {
			if ($info['instances'] > 0) $out .= "<div>{$info['instances']} {$info['name']}</div>";
		}
		$season_name = get_season_name_from_db();
		return <<<EOHTML
			<div class="shift_instances">
				<h4>You're signed up to do these shifts during {$season_name}:</h4>
				{$out}
			</div>
EOHTML;
	}

	public function toString() {
		if (is_null($this->worker)) {
			$this->reportNoShifts();
		}

		if ($this->is_save_request) {
			$out = $this->renderSaved();
			$this->sendEmail($this->worker->getUsername(), $out);
			return <<<EOHTML
<div class="saved_notification">{$out}</div>
EOHTML;
		}

		// query for this worker's tasks.
		$shifts_summary = $this->getShiftsSummaryHtml();
		if (is_null($shifts_summary)) {
			$this->reportNoShifts();
		}

		if (1) deb("survey.toString(): _GET =", $_GET);
		if (1) deb("survey.toString(): worker =", $worker);
		return <<<EOHTML
		<h2>Welcome, {$this->worker->getName()}</h2>
		{$this->calendar->renderMonthsOverlay()}
		<form method="POST" action="process.php">
			<input type="hidden" name="username" value="{$_GET['worker']}">
			<input type="hidden" name="posted" value="1">
			{$shifts_summary}
			{$this->renderRequests()}
			{$this->calendar->toString($this->worker)}
			<button class="pill" type="submit" value="Save" id="end">Save</button>
		</form>
EOHTML;
	}

	public function reportNoShifts() {
		$dir = BASE_DIR;
		echo <<<EOHTML
			<div style="padding: 50px;">
				<div class="highlight">Sorry {$_GET['worker']} - you don't
				have any meals shifts for the upcoming season</div>
				<a href="{$dir}" class="pill">&larr; go back</a>
			</div>
EOHTML;
		exit;
	}

	/**
	 * Render the form inputs for asking if the worker wants to clean after
	 * doing their cook shift.
	 *
	 * @param[in] requests key-value pairs of preferences saved to database.
	 * @return string rendered html for the clean after cook form input option.
	 */
	protected function renderCleanAfter($requests) {
		$tasks = $this->worker->getTasks();

		$sunday_cook = $sunday_clean = FALSE;
		$weekday_cook = $weekday_clean = FALSE;
		$mtg_cook = $mtg_clean = FALSE;
		foreach($tasks as $task_id=>$unused) {
			if (is_a_cook_job($task_id)) {
				if (is_a_sunday_job($task_id)) {
					$sunday_cook = TRUE;
				}
				else if (is_a_mtg_night_job($task_id)) {
					$mtg_cook = TRUE;
				}
				else {
					$weekday_cook = TRUE;
				}
			}
			else {
				if (is_a_sunday_job($task_id)) {
					$sunday_clean = TRUE;
				}
				else if (is_a_mtg_night_job($task_id)) {
					$mtg_clean = TRUE;
				}
				else {
					$weekday_clean = TRUE;
				}
			}
		}

		$clean_after_cook =
			(($sunday_cook && $sunday_clean) ||
			($weekday_cook && $weekday_clean) ||
			($mtg_cook && $mtg_clean));

		if (!$clean_after_cook) {
			return '';
		}

		return <<<EOHTML
			<li>
				I prefer to take a clean shift the same day
				I do a cook shift in order to clean up after myself.<br>
				<div class="radio_buttons">
					<label>
						<input type="radio" name="clean_after_self" value="yes"
							{$requests['clean_after_self_yes']}>
						<span>Yes</span>
					</label>

					<label>
						<input type="radio" name="clean_after_self" value="dc"
							{$requests['clean_after_self_dc']}>
						<span>Don't Care</span>
					</label>

					<label>
						<input type="radio" name="clean_after_self" value="no"
							{$requests['clean_after_self_no']}>
						<span>No</span>
					</label>
				</div>
			</li>
EOHTML;
	}

	public function renderRequests() {
		$comments_text = $this->worker->getCommentsText();
		$comments_info = $this->worker->getComments();

		$questions = array('clean_after_self', 'bunch_shifts');
		$answers = array('yes', 'dc', 'no');
		foreach($questions as $q) {
			$found = FALSE;
			foreach($answers as $a) {
				$choice = $q . '_' . $a;
				$requests[$choice] = '';
				if (array_key_exists($q, $comments_info) &&
					($comments_info[$q] == $a)) {
					$requests[$choice] = ' checked';
					$found = TRUE;
					break;
				}
			}

			// set defaults
			if (!$found) {
				$requests[$q . '_dc'] = ' checked';
			}
		}

		// generate list of workers
		$avoids = explode(',', array_get($comments_info, 'avoids', ''));
		$avoids = array_flip($avoids);
		$avoids = array_fill_keys(array_keys($avoids), 1);
		if(0) deb("survey.renderRequests: avoids =", $avoids);
		$avoid_worker_selector = $this->getWorkerList('avoid_worker', TRUE,
			$this->worker->getUsername(), $avoids);

		$prefers = explode(',', array_get($comments_info, 'prefers', ''));
		$prefers = array_flip($prefers);
		$prefers = array_fill_keys(array_keys($prefers), 1);
		$prefer_worker_selector = $this->getWorkerList('prefer_worker', TRUE,
			$this->worker->getUsername(), $prefers);

		$bundle_checked = (array_get($comments_info, 'bundle_shifts') == 'on') ?
				' checked' : '';

		return <<<EOHTML
			<div class="d_table">
				<div class="d_cell pad">
					<label id="avoid_workers">
						<span>Avoid scheduling with: (e.g. housemates)</span>
						{$avoid_worker_selector}
					</label>
				</div>
				<div class="d_cell pad">
					<label id="prefer_workers">
						<span>Prefer to schedule with: (e.g. housemates)</span>
						{$prefer_worker_selector}
					</label>
				</div>
			</div>

<!--
#!# This doesn't seem to work, so don't support it for now.
			<p>
				<label>
					<input type="checkbox" name="bundle_shifts"{$bundle_checked}>
					Please bundle my shifts together
					<span>(i.e. I want to do all of 3 of my cooking / cleaning
					shifts the same evening</span>
				</label>
			</p>
-->

			{$this->renderCleanAfter($requests)}

			<h3>Special Requests:</h3>
			<label class="explain">
				Please use the calendar below to mark your availability
				and only place special requests not addressed above here:
				<textarea name="comments" rows="7" cols="100">{$comments_text}</textarea>
			</label>
EOHTML;

/*
				<li>
					Please group my shifts in the season:<br>
					<input type="radio" name="bunch_shifts" value="yes"
						id="bunch_shifts_early"{$requests['bunch_shifts_yes']}>
						<label for="bunch_shifts_early">Early</label>

					<input type="radio" name="bunch_shifts" value="dc"
						id="bunch_shifts_dc"{$requests['bunch_shifts_dc']}>
						<label for="bunch_shifts_dc">Don't Care</label>

					<input type="radio" name="bunch_shifts" value="no"
						id="bunch_shifts_late"{$requests['bunch_shifts_no']}>
						<label for="bunch_shifts_late">Late</label>
				</li>
*/
	}

/* ------------------------------------------------ */

	public function run() {
		if (1) deb("survey.run: _POST =", $_POST);
		$this->popUsername();
		if (1) deb("survey.run: this->username =", $this->username);
		$this->lookupWorkerId();
		if (1) deb("survey.run: this->worker_id =", $this->worker_id);
		$this->setWorker();
		if (1) deb("survey.run: this->worker =", $this->worker);

		$this->lookupAvoidList();
		$this->lookupPreferList();
		$this->processPost();
		$this->confirmWorkLoad();

		// save the survey
		$this->saveRequests();
		$this->savePreferences();
	}

	/**
	 * XXX This function is mis-named.
	 */
	protected function popUsername() {
		if (!isset($_POST['username'])) {
			echo "<p class=\"error\">Missing username</p>\n";
			exit;
		}
		$this->username = $_POST['username'];
	}


	protected function lookupWorkerId() {
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
select id from {$auth_user_table} where username='{$this->username}'
EOSQL;

		// get this worker's ID
		$this->worker_id = NULL;
		foreach($this->dbh->query($sql) as $row) {
			$this->worker_id = $row['id'];
			return;
		}
	}

	protected function processPost() {
		// is this a posting? save that and delete from POST.
		if (isset($_POST['posted'])) {
			$this->is_save_request = TRUE;
			unset($_POST['posted']);
		}

		// deal with special requests first, then delete them from POST.
		foreach(array_keys($this->requests) as $r) {
			if (!isset($_POST[$r])) {
				continue;
			}

			// #!# $this->requests[$r] = sqlite_escape_string($_POST[$r]);
			$this->requests[$r] = $_POST[$r];
			unset($_POST[$r]);
		}

		// process the remaining post vars
		foreach($_POST as $key=>$choice) {
			if ($key == 'username') {
				continue;
			}

			list($date_string, $task) = explode('_', $key);
			$this->results[$choice][$task][] = $date_string;
			if ($choice > 0) {
				if (!isset($this->positive_count[$task])) {
					$this->positive_count[$task] = 0;
				}
				$this->positive_count[$task]++;
			}
		}
	}

	protected function lookupAvoidList() {
		$avoids = array();
		if (!empty($_POST['avoid_worker'])) {
			$this->avoid_list = implode(',',
				$_POST['avoid_worker']);
			unset($_POST['avoid_worker']);
		}
	}

	protected function lookupPreferList() {
		$prefers = array();
		if (!empty($_POST['prefer_worker'])) {
			$this->prefer_list = implode(',',
				$_POST['prefer_worker']);
			unset($_POST['prefer_worker']);
		}
	}

	/**
	 * Used by the save process to figure out if the user has selected enough
	 * shifts to fulfill their assignment.
	 */
	protected function confirmWorkLoad() {
		global $all_jobs;
		$insufficient_prefs = array();

		$num_shifts_to_fill = $this->worker->getNumShiftsToFill();
		foreach($num_shifts_to_fill as $job_id => $num_instances) {
			$pos_count = !array_key_exists($job_id, $this->positive_count) ? 0 :
				$this->positive_count[$job_id];

			// if they haven't filled out enough preferences to fulfill this
			// shift, then warn them.
			if ($pos_count < $num_instances) {
				$insufficient_prefs[] = 
					"({$pos_count} avail / <b>{$num_instances} needed</b>) for {$all_jobs[$job_id]}\n";
			}
		}

		if (!empty($insufficient_prefs)) {
			$missing = implode('<br>', $insufficient_prefs);
			$dir = BASE_DIR;
			$out = <<<EOHTML
			<div class="warning">
				<h2>Warning:</h2>
				<p>You haven't marked enough preferences for the jobs assigned. Your
				choices will be saved and you can come back add more later. However, without
				enough available dates, assignments will be more difficult.</p>
			</div>

			<div class="attention">
				<h3>Jobs which need more availability:</h3>
				<p>{$missing}</p>
				<p>Perhaps you can trade a shift, or <a
					href="{$dir}/index.php?worker={$this->username}">
						add more availability</a>.</p>
			</div>
EOHTML;
			$this->insufficient_prefs_msg = $out;
			echo $out;
		}
	}

	/**
	 * Pick out the number of instances someone has been assigned from this
	 * database.
	 */
	protected function getNumInstances($row) {
		$job_id = $row['job_id'];
		$num_dinners = get_num_dinners_per_assignment($job_id);

		// otherwise, look for an entry in from the db
		$num_instances = 0;
		$num_shifts_assigned = $row['instances'];

		// how many shifts are needed?
		return $num_dinners * $num_shifts_assigned;
	}

	/**
	 * Save the special requests to the db
	 */
	protected function saveRequests() {
		$bundle = array_get($this->requests, 'bundle_shifts', '');
		$table = SCHEDULE_COMMENTS_TABLE;
		$sql = <<<EOSQL
replace into {$table} (worker_id, timestamp, comments, avoids, prefers, clean_after_self, bunch_shifts, bundle_shifts)
	values(
		{$this->worker_id},
		datetime('now'),
		'{$this->requests['comments']}',
		'{$this->avoid_list}',
		'{$this->prefer_list}',
		'{$this->requests['clean_after_self']}',
		'{$this->requests['bunch_shifts']}',
		'{$bundle}'
	)
EOSQL;
		if (0) deb("SQL to insert work_prefs", $sql);
		$this->dbh->exec($sql);
	}

	/**
	 * Save the stated preferences.
	 */
	protected function savePreferences() {
		global $pref_names;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;

		// reverse the array to process the higer priority preferences first,
		// which puts them at the top of the prefs summary listing.
		krsort($this->results);
		foreach($this->results as $pref=>$data) {
			if (empty($data)) {
				continue;
			}

			foreach($data as $task=>$dates) {
				// process each date instance
				$prev_pref = NULL;
				foreach($dates as $d) {

					$shift_id = NULL;
					$sql = "select id from {$shifts_table} where string='{$d}' and job_id={$task}";
					foreach($this->dbh->query($sql) as $row) {
						$shift_id = $row['id'];
						break;
					}

					if (is_null($shift_id)) {
						$insert = <<<EOSQL
REPLACE INTO {$shifts_table} (id, string, job_id) VALUES(NULL, '{$d}', {$task})
EOSQL;
						$this->dbh->exec($insert);

						// now check to make sure that entry was saved...
						foreach($this->dbh->query($sql) as $row) {
							$shift_id = $row['id'];
							break;
						}

						if (is_null($shift_id)) {
							echo <<<EOHTML
								<p class="error">
									Couldn't retrieve shifts data after insert - is
									the database writable? 
								</p>
EOHTML;
							if (0) deb("survey.php: select statement:", $sql);
							if (0) deb("survey.php: insert statement:", $insert);
							exit;
						}
					}

					$prefs_table = SCHEDULE_PREFS_TABLE;
					$replace = <<<EOSQL
REPLACE INTO {$prefs_table} (date_id, worker_id, pref) VALUES({$shift_id}, {$this->worker_id}, {$pref})
EOSQL;
					if (0) deb("SQL to insert shift_prefs", $replace);
					$success = $this->dbh->exec($replace);
					if ($success) {
						$this->saved++;
						if ($prev_pref !== $pref) {
							$this->summary[$task][] = '<hr>';
						}
						$prev_pref = $pref;

						$s = "{$d} {$pref_names[$pref]}";
						if ($pref > 1) {
							$s = "<b>{$s}</b>";
						}
						$this->summary[$task][] = $s;
					}
				}
			}
		}
	}


	/**
	 * Render a notification message when the data has been saved.
	 * @return string HTML of the notification message.
	 */
	public function renderSaved() {
		$summary_text = '';
		$dates = '';

		if (!empty($this->summary)) {
			foreach($this->summary as $job_id=>$info) {
				$name = get_job_name($job_id);
				$dates = implode("\n<br>", $info);
				$summary_text .= <<<EOHTML
<div class="summary_listing">
	<h4>{$name}</h4>
	{$dates}
</div>
EOHTML;
			}

			$summary_text = <<<EOHTML
<div>
	<h3>Preferences Summary</h3>
	<div class="pref_listing">
		{$summary_text}
	</div>
</div>
EOHTML;
		}

		$dir = BASE_DIR;
		return <<<EOHTML
	<h2>Saved!</h2>
	<p>Saved {$this->saved} shift preferences.</p>
	<p>You may <a href="{$dir}/index.php?worker={$this->username}">make changes</a>
		or <a href="{$dir}">take the survey for another person</a>.</p>
	{$summary_text}
EOHTML;
	}

	/**
	 * Send an email message with the results.
	 * @param[in] content string the summary results.
	 */
	protected function sendEmail($user, $content) {
		if (SKIP_EMAIL) {
			return;
		}

		$worker = $user . DOMAIN;
		$sent = mail($worker,
			'Meal Scheduling Survey preferences saved',
			strip_tags($content),
			'From: willie@gocoho.org');

		if (!$sent) {
			error_log("Unable to send email to: $to");
		}

		// if user is under pref level, then send warning email
		if (!is_null($this->insufficient_prefs_msg)) {
			$sent = mail('willie@gocoho.org',
				'Meal Scheduling Survey preferences saved under limit',
				$worker . "\n" . strip_tags($content . "\n" .
					$this->insufficient_prefs_msg),
				'From: willie@gocoho.org');
		}

		return $sent;
	}
}

?>
