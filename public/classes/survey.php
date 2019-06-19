<?php

require_once 'start.php';
// require_once 'globals.php';
// require_once 'utils.php';
// require_once 'display/includes/header.php';

require_once 'classes/calendar.php';
require_once 'classes/roster.php';
require_once 'classes/worker.php';
require_once 'classes/WorkersList.php';
require_once 'classes/OffersList.php';
require_once 'finish.php';
	
class Survey {
	public $worker;
	public $calendar;
	public $roster;

	public $name;
	public $username;
	public $worker_id;

	public $dbh;

	public $avoid_list;
	public $prefer_list;
	public $saved = 0;
	public $summary;
	public $results = array(
		0 => array(),
		1 => array(),
		2 => array(),
	);
	public $shifts_summary;

	// track the number of jobs marked positively
	public $positive_count = array();

	public $requests = array(
		'clean_after_self' => '',
		'bunch_shifts' => '',
		'comments' => '',
		'bundle_shifts' => '',
	);

	public $is_save_request = FALSE;
	public $insufficient_prefs_msg;


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
		if (0) deb("survey.setWorker username = $username, worker_id = $worker_id"); 

		// if (is_null($username)) {
			// if (is_null($this->username)) {
				// echo "Missing username in setWorker!\n";
				// exit;
			// }
			// $username = $this->username;
		// }

		if (is_null($worker_id)) {
			if (is_null($this->worker_id)) {
				echo "Missing worker_id in setWorker!\n";
				exit;
			}
			$worker_id = $this->worker_id;
		}

		$this->roster->loadNumShiftsAssigned($username);
		$this->worker = $this->roster->getWorker($username);
		if (0) deb ("survey.setWorker(): this->worker", $this->worker);
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
		if (0) deb("survey.getWorkers(): workers_list =", workers_list);
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

	public function worker_is_both_cook_and_cleaner() {
		$shifts = $this->worker->num_shifts_to_fill;
		if (0) deb("survey.worker_is_both_cook_and_cleaner(): shifts:", $shifts);

		$sunday_cook = $sunday_clean = FALSE;
		$weekday_cook = $weekday_clean = FALSE;
		$mtg_cook = $mtg_clean = FALSE;
		foreach($shifts as $shift_id=>$instances) {
			if (0) deb("survey.worker_is_both_cook_and_cleaner(): shift_id:", $shift_id);
			if (0) deb("survey.worker_is_both_cook_and_cleaner(): instances:", $instances);
			if ($instances == 0 || $instances == NULL) continue;
			if (0) deb("survey.worker_is_both_cook_and_cleaner(): instances after continue:", $instances);
			if (is_a_cook_job($shift_id)) {
				if (is_a_sunday_job($shift_id)) {
					$sunday_cook = TRUE;
				}
				else if (is_a_mtg_night_job($shift_id)) {
					$mtg_cook = TRUE;
				}
				else {
					$weekday_cook = TRUE;
				}
			}
			else {
				if (is_a_sunday_job($shift_id)) {
					$sunday_clean = TRUE;
				}
				else if (is_a_mtg_night_job($shift_id)) {
					$mtg_clean = TRUE;
				}
				else {
					$weekday_clean = TRUE;
				}
			}
		}
		if (0) deb("survey.worker_is_both_cook_and_cleaner(): weekday_cook :", ($weekday_cook));
		if (0) deb("survey.worker_is_both_cook_and_cleaner(): weekday_clean:", ($weekday_clean));
		if (0) deb("survey.worker_is_both_cook_and_cleaner(): weekday_cook && weekday_clean:", ($weekday_cook && $weekday_clean));
	
		return (($sunday_cook && $sunday_clean) ||
			($weekday_cook && $weekday_clean) ||
			($mtg_cook && $mtg_clean));
	}

	/**
	 * Get the list of tasks by id, their name and the number of instances this
	 * worker was assigned.
	 */
	public function getShifts() {
		$tasks = $this->worker->getTasks();
		if (0) deb("survey.getShifts(): tasks = ", $tasks);

		$shifts = array();
		foreach($tasks as $job_id=>$name) {
			$shifts[$job_id] = array(
				'name' => $name['description'],
				'instances' => $name['instances'],
			);
		}
		if (0) deb("survey.getShifts(): shifts = ", $shifts); 
		return $shifts;
	}


	public function renderShiftsSummary() {
		$shifts = $this->getShifts();
		if (empty($shifts)) {
			return NULL;
		}

		$summary = '';
		$li_style = 'style="list-style-type: circle;"';
		foreach($shifts as $id=>$info) {
			if ($info['instances'] > 0) $summary .= "
<li {$li_style}>{$info['name']} - {$info['instances']} times</li>";
		}
		$season_name = get_season_name_from_db();
		if (0) deb("survey.renderShiftsSummary(): worker =", $this->worker);
		$first_name = $this->worker->getFirstName();
		if (0) deb("survey.renderShiftsSummary(): first_name =", $first_name);
		return "
<ul>{$summary}</ul>"; 
	}

	public function renderSurvey() {
		if (is_null($this->worker)) {
			$this->reportNoShifts();
		}

		if ($this->is_save_request) {
			$this->shifts_summary = $this->renderShiftsSummary();
			if (0) deb("survey.renderSurvey: this->shifts_summary =", $this->shifts_summary);
			if (0) deb("survey.renderSurvey: survey object:", $this);
			finishSurvey($this, $this->worker->id);
			return;
		}

		// query for this worker's tasks.
		$this->shifts_summary = $this->renderShiftsSummary();
		if (is_null($this->shifts_summary)) {
			$this->reportNoShifts();
		}

		if (0) deb("survey.renderSurvey: _GET =", $_GET);		
		if (0) deb("survey.renderSurvey: _POST =", $_POST);  
		$headline = renderHeadline("Step 2: Tell Us Your Preferences", CRUMBS);
		// $headline = renderHeadline("Step 2: Tell Us Your Preferences", HOME_LINK . SIGNUPS_LINK . $this->worker->id);
		$season_name = get_season_name_from_db();
		$first_name = $this->worker->getFirstName();
		$send_email = renderSendEmailControl($this->worker->getName());
		// if(userIsAdmin()) {
			// $send_email = '<div align="right"><input type="checkbox" name="send_email" value="yes">Email summary to ' . $this->worker->getName() . '?</div><br>';
			// // $finish_widget = '<button class="pill" type="submit" value="Save and Send Email" id="email" name="email">Finish and Send Email</button>';
			// // $finish_widget .= '<button class="pill" type="submit" value="Save but Send No Email" id="noemail" name="noemail">Finish but Send No Email</button>';
		// } else {
			// $send_email = '<div align="right"><input type="hidden" name="send_email" value="default">';
			// // $finish_widget = '<button class="pill" type="submit" value="Save" id="end">Finish</button>';	
		// }
		
		return <<<EOHTML
		{$headline}
		<form method="POST" class="kform" action="process.php">
			<input type="hidden" name="username" value="{$this->worker->getUsername()}">
			<input type="hidden" name="posted" value="1">
			<br>
			<p>Thanks, {$first_name}!  You've signed up to do these jobs during {$season_name}:</p>
			{$this->shifts_summary}
			<p>Here's your chance to tell the "Scheduler" program about when you can do these jobs and who you prefer to do them with.  As it generates a schedule of dinners for the {$season_name} season, the Scheduler will do its best to honor everybody's expressed preferences.</p>
			<p style="color:red"><strong>If you enter any changes on this page, be sure to scroll down to the bottom and hit the "Finish" button before you leave the page, so your changes will be saved.</strong></p>
			<br>
			{$this->renderCalendar()}
			{$this->renderCleanAfterSelfPreferences($this->requests)}
			<br>
			{$this->renderCoworkerPreferences()}
			{$this->renderComments()}
			{$send_email}
			<button class="pill" type="submit" value="Save" id="end">Finish</button>
		</form>
EOHTML;

			// 

			// NOTE: Below is like above except it includes "renderMonthsOverlay"
		// return <<<EOHTML
		// {$headline}
		// {$this->calendar->renderMonthsOverlay()}
		// <form method="POST" class="kform" action="process.php">
			// <input type="hidden" name="username" value="{$this->worker->getUsername()}">
			// <input type="hidden" name="posted" value="1">
			// <br>
			// <p>Thanks, {$first_name}!  You've signed up to do these jobs during {$season_name}:</p>
			// {$this->shifts_summary}
			// <p>Here's your chance to tell the "Scheduler" program about when you can do these jobs and who you prefer to do them with.  As it generates a schedule of dinners for the {$season_name} season, the Scheduler will do its best to honor everybody's expressed preferences.</p>
			// <p style="color:red"><strong>If you enter any changes on this page, be sure to scroll down to the bottom and hit the "Finish" button before you leave the page, so your changes will be saved.</strong></p>
			// <br>
			// {$this->renderCalendar()}
			// {$this->renderCleanAfterSelfPreferences($this->requests)}
			// <br>
			// {$this->renderCoworkerPreferences()}
			// {$this->renderComments()}
			// <button class="pill" type="submit" value="Save" id="end">Finish</button>
		// </form>
// EOHTML;
	}

	private function renderCalendar() {
		$calendar = <<<EOHTML
			<p class="question">When can you work?</p>
			<p>Get out your calendar and tell the Scheduler when you can and can't work in the upcoming season.  </p>
			<p>For each job on each day, you have three options:</p>
			<ul>
				<li style="list-style-type: circle"><strong>prefer</strong> - This means you prefer to do this job on this day.  The Scheduler will try to assign you jobs on your "prefer" days, though this is not always possible.
				<li style="list-style-type: circle"><strong>ok</strong> - This means you're willing to be assigned this job on this day.  As you'll see, "ok" is the default.  To give the Scheduler its best chance to generate a schedule that works well for everyone, please choose "ok" or "prefer" for as many jobs as possible.
				<li style="list-style-type: circle"><strong>avoid</strong> - This means you can't do this job on this day.  The Scheduler will not assign you to any job that you have marked "avoid".
			</ul>
			<p>To speed your process of marking this calendar, the cells with a gray background provide selectors that you can use to choose the same option for every job in an entire month, in a whole week, or for every weekday.  For example, if you're going to be out of town for most of a particular month, you can click on "mark entire month: avoid".  Then you can go in and mark "ok" or "prefer" for the few jobs that you will be available to do that month.  For another example, if you prefer to cook on Sundays, you can select "mark every Sun: prefer".</p>
			<br>
			{$this->calendar->renderCalendar($this->worker)} 
EOHTML;
		return $calendar;
	}

	private function reportNoShifts() {
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
	private function renderCleanAfterSelfPreferences($requests) {
		$comments_info = $this->worker->getComments();
		if (0) deb("survey.renderCleanAfterSelfPreferences: comments_info['clean_after_self'] =", $comments_info['clean_after_self']);
		$clean_after_self = $comments_info['clean_after_self'];
		if (0) deb("survey:renderCleanAfterSelfPreferences(): clean_after_self", $clean_after_self);
		$worker_is_both_cook_and_cleaner = $this->worker_is_both_cook_and_cleaner();
		if (0) deb("survey:renderCleanAfterSelfPreferences(): worker_is_both_cook_and_cleaner =", $worker_is_both_cook_and_cleaner);
		if (0) deb("survey:renderCleanAfterSelfPreferences(): this->requests", $this->requests);
		$yes = ($clean_after_self == 'yes' ? 'checked="checked"' : '');
		$dc = ($clean_after_self == 'dc' ? 'checked="checked"' : '');
		$no = ($clean_after_self == 'no' ? 'checked="checked"' : '');
		if (0) deb("survey:renderCleanAfterSelfPreferences(): yes", $yes);
		if (0) deb("survey:renderCleanAfterSelfPreferences(): dc", $dc);
		if (0) deb("survey:renderCleanAfterSelfPreferences(): no", $no);
		
		if ($worker_is_both_cook_and_cleaner) return <<<EOHTML
			<p class="question">Do you prefer to cook and clean on the same day?</p>
			<p>Some folks like to clean up after themselves, and get two jobs done on the same day.  Others prefer to do their jobs on different days.  How about you?</p>
			<div class="radio_buttons">
				<table><tr><td style="background: yellow";>
					<table cellpadding="8" cellspacing="3" border="1"><tr>
						<td>
						<label>
							<input type="radio" name="clean_after_self" value="yes" {$yes}>
							<span>Yes</span>
						</label>
						<label>
							<input type="radio" name="clean_after_self" value="dc" {$dc}>
							<span>Don't Care</span>
						</label>
						<label>
							<input type="radio" name="clean_after_self" value="no" {$no}>
							<span>No</span>
						</label>
						</td>
					</tr></table>
				</td></tr></table>
			</div>
EOHTML;
	}

	private function renderCoWorkerPreferences() {
		$comments_info = $this->worker->getComments();
		if (0) deb("survey.renderRequests: $comments_info =", $comments_info);

		$avoids = explode(',', array_get($comments_info, 'avoids', ''));
		$avoids = array_flip($avoids);
		$avoids = array_fill_keys(array_keys($avoids), 1);
		if(0) deb("survey.renderRequests: avoids =", $avoids);
		$avoid_worker_selector = $this->getWorkerList('avoid_worker', FALSE,
			$this->worker->getUsername(), $avoids);

		$prefers = explode(',', array_get($comments_info, 'prefers', ''));
		$prefers = array_flip($prefers);
		$prefers = array_fill_keys(array_keys($prefers), 1);
		$prefer_worker_selector = $this->getWorkerList('prefer_worker', FALSE,
			$this->worker->getUsername(), $prefers);

		return <<<EOHTML
			<p class="question">Who will you work with?</p>
			<p>Working together in diverse combinations is an important way that meal-making builds community.   
			<p>But sometimes there are practical reasons to avoid or prefer particular teammates.  For example, a couple with young kids may need to work different meals so one of them can mind the kids.  Or, working <em>with</em> a particular person may be crucial to your participation in some way.
			<table><tr><td style="background:yellow">
				<table cellpadding="8" cellspacing="3" border="1" style="font-size:11pt; ">
					<td >
						<label id="avoid_workers">
							<p>Avoid scheduling with: (e.g. housemates)</p>
							{$avoid_worker_selector}
						</label>
					</td>
					<td >
						<label id="prefer_workers">
							<p>Prefer to schedule with: (e.g. housemates)</p>
							{$prefer_worker_selector}
						</label>
					</td>
				</tr></table>
			</td></tr></table>
EOHTML;
	}

	private function renderBunchShiftsPreferences() {
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
		return <<<EOHTML
			{$this->renderCleanAfter($requests)}
EOHTML;
	}

	private function renderComments() {
		$comments_text = $this->worker->getCommentsText();
		return <<<EOHTML
			<a name="special_requests"></a><p class="question">Anything else?</p>
			<label class="explain">
				<p>If you have any preferences, suggestions, or comments for the More Meals Committee, write them here.</p>
				<textarea name="comments" rows="7" cols="100">{$comments_text}</textarea>
			</label>
EOHTML;
	}

/* ------------------------------------------------ */

	public function run() {
		if (0) deb("survey.run: _POST =", $_POST);
		$this->popUsername();
		if (0) deb("survey.run: this->username =", $this->username);
		$this->lookupWorkerId();
		if (0) deb("survey.run: this->worker_id =", $this->worker_id);
		$this->setWorker();
		if (0) deb("survey.run: this->worker =", $this->worker);

		$this->lookupAvoidList();
		$this->lookupPreferList();
		$this->processPost();
		$this->confirmWorkLoad();

		// save the survey
		$this->saveRequests();
		$this->savePreferences();
	}

	public function setIsSaveRequest($value) {
		// is this a posting? save that and delete from POST.
		$this->is_save_request = $value;
		if ($value == TRUE || isset($_POST['posted'])) {
			unset($_POST['posted']);
		}
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
		if (0) deb("survey.processPost(): _POST:", $_POST);
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
			$this->requests[$r] = $_POST[$r];
			unset($_POST[$r]);
		}
		if (0) deb("survey.processPost(): this->requests (except calendar):", $this->requests);

		// process the remaining post vars
		foreach($_POST as $key=>$choice) {
			if ($key == 'username') {
				continue;
			}
			if (0) deb("survey.processPost(): POST key = $key, value = ", $choice);
			list($date_string, $task) = explode('_', $key);
			$this->results[$choice][$task][] = $date_string;
			if ($choice > 0) {
				if (!isset($this->positive_count[$task])) {
					$this->positive_count[$task] = 0;
				}
				$this->positive_count[$task]++;
			}
		}
		if (0) deb("survey.processPost(): this->results = ", $this->results);	
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
				$shortage = $num_instances - $pos_count;
				$day_text = ($shortage == 1 ? "a day" : "days");
				$insufficient_prefs[] = 
					"<strong>{$all_jobs[$job_id]}:</strong>  You signed up for {$num_instances} jobs, but only said 'ok' or 'prefer' for {$pos_count}.  Could you find  {$day_text} to do {$shortage} more?\n";
			}
		}

		if (!empty($insufficient_prefs)) {
			$missing = implode('<br>', $insufficient_prefs);
			$dir = BASE_DIR;
			$out = <<<EOHTML
			<div class="warning">
				<h2>Warning:</h2>
				<p>You haven't marked enough meals as "ok" or "prefer" 
				to give you the number of jobs you signed up to do. </p>
				<p>Your preferences have been saved.  You can come back and add more later. However, without
				enough available dates, assignments will be more difficult.</p>
			</div>

			<div class="attention">
				<h3>Jobs which need more "ok" or "prefer" ratings:</h3>
				<p>{$missing}</p>
				<p>Perhaps you can trade a shift, or <a
					href="{$dir}/index2.php?worker={$this->username}">
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
		$work_prefs_table = SCHEDULE_COMMENTS_TABLE;
		$coworker_requests_table = SCHEDULE_COWORKER_REQUESTS_TABLE;
		$workers_table = AUTH_USER_TABLE;
		$season_id = SEASON_ID;
		$timestamp = date('Y-m-d H:i:s');
		if (0) deb("survey.saveRequests(): timestamp:", $timestamp);
		
		// Save all requests into the SCHEDULE_COMMENTS_TABLE
		$sql = <<<EOSQL
replace into {$work_prefs_table} (worker_id, timestamp, avoids, prefers, comments, clean_after_self, bunch_shifts, bundle_shifts, season_id)
	values(
		{$this->worker_id},
		'{$timestamp}',
		'{$this->avoid_list}',
		'{$this->prefer_list}',
		'{$this->requests['comments']}',
		'{$this->requests['clean_after_self']}',
		'{$this->requests['bunch_shifts']}',
		'{$bundle}',
		{$season_id}
	)
EOSQL;
		if (0) deb("survey.saveRequests(): SQL to insert work_prefs", $sql);
		$success = $this->dbh->exec($sql);
		if (0) deb("survey.saveRequests(): success?", $success);

		// Save coworker requests into coworker_requests table.
		if (0) {  // write existing requests to debug
			$select = "r.request, w.id, w.first_name, w.last_name";
			$from = "{$coworker_requests_table} as r, {$workers_table} as w";
			$where = "r.requester_id = {$this->worker_id} and r.season_id = {$season_id} and r.coworker_id = w.id";
			$order_by = "request, username";
			$requests = sqlSelect($select, $from, $where, $order_by, (0));
			deb("survey.saveRequests(): coworker_requests by worker {$this->worker_id} before update = ", $requests);
		}

		// Delete this worker's existing coworker_requests for this season.
		$rows_affected = sqlDelete("{$coworker_requests_table}", "requester_id = {$this->worker_id} and season_id = {$season_id}", (0));
		if (0) deb("survey.saveRequests(): request rows deleted = ", $rows_affected);
		// Insert new coworker requests (which may be the same as some old ones just deleted).
		$this->saveCoworkerRequests('avoid', $this->avoid_list);
		$this->saveCoworkerRequests('prefer', $this->prefer_list);

		if (0) {  // write updated requests to debug
			$select = "r.request, w.id, w.first_name, w.last_name";
			$from = "{$coworker_requests_table} as r, {$workers_table} as w";
			$where = "r.requester_id = {$this->worker_id} and r.season_id = {$season_id} and r.coworker_id = w.id";
			$order_by = "request, username";
			$requests = sqlSelect($select, $from, $where, $order_by, (0));
			deb("survey.saveRequests(): coworker_requests by worker {$this->worker_id} after update = ", $requests);
		}
	}


	/**
	 * Save the coworker requests into the coworker_requests table.
	 * Note:  These are also redundantly stored in a column of the work_prefs table,
	 * which is preserved because some parts of this system still put/get them there.
	 */
	protected function saveCoworkerRequests($request, $coworkers_list) {
		$coworker_requests_table = SCHEDULE_COWORKER_REQUESTS_TABLE;
		$workers_table = AUTH_USER_TABLE;
		$season_id = SEASON_ID;

		$coworkers = explode(",", $coworkers_list);
		foreach($coworkers as $key=>$coworker) {
			// Get the id of the coworker
			$coworker_id = sqlSelect("id", "{$workers_table} as w", "w.username = '{$coworker}'", "", (0));
			// Insert the coworker request
			$table = $coworker_requests_table;
			$columns = "request, requester_id, coworker_id, season_id";
			$values = "'{$request}', {$this->worker_id}, {$coworker_id[0]['id']}, {$season_id}";
			$rows_affected = sqlInsert($table, $columns, $values, (0));
			if (0) deb("survey.saveRequests(): request rows inserted = ", $rows_affected);
		}
	}
	
	
	/**
	 * Save the shift preferences.
	 */
	protected function savePreferences() {
		global $pref_names;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$meals_table = MEALS_TABLE;

		// reverse the array to process the higher priority preferences first,
		// which puts them at the top of the prefs summary listing.
		krsort($this->results);
		if (0) deb("survey.savePreferences(): this->results = ", $this->results);
		
		foreach($this->results as $pref_rating=>$job_ids) {
			if (0) deb("survey.savePreferences(): pref_rating = $pref_rating, job_ids = ", $job_ids); 
			$job_n++;
			if (empty($job_ids)) continue;

			foreach($job_ids as $job_id=>$shift_ids) {
				// store prefs for each meal
				$prev_pref = NULL;
				foreach($shift_ids as $shift_id) {
					$into = SCHEDULE_PREFS_TABLE;
					$columns = "shift_id, worker_id, pref";
					$values = "{$shift_id}, {$this->worker_id}, {$pref_rating}";
					$success = sqlReplace($into, $columns, $values, (0), "survey.savePreferences()");
					if ($success) { 
						$this->saved++;
						if ($prev_pref !== $pref_rating) {
							$this->summary[$job_id][] = '<p></p>';
						}
						$prev_pref = $pref_rating;

						$s = "{$date} {$pref_names[$pref_rating]}"; 
						if (0) deb("survey.php: s = ", $s);
						$this->summary[$job_id][] = $s;
					}
				}
			}
		}
	}
}

?>
