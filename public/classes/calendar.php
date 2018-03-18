<?php
global $relative_dir;
if (!isset($relative_dir)) {
	$relative_dir = './';
}
require_once $relative_dir . 'globals.php';
require_once $relative_dir . 'utils.php';
require_once 'WorkersList.php';

class Calendar {
	public $web_display = TRUE;
	public $key_filter = 'all';

	public $assignments = array();
	private $cur_date_assignments = array();

	public $holidays;

	public $is_report = FALSE;

	public $num_shifts = array(
		'sunday' => 0,
		'weekday' => 0,
		'meeting' => 0,
	);
	
	public $job_key = 'all';
	public $data_key = 'all';

	public function __construct() {
		// 'all' is the default, so only change if it's numeric
		if (isset($_GET['key']) && is_numeric($_GET['key'])) {
			$this->key_filter = $_GET['key'];
		}

		$this->holidays = get_holidays(SEASON_NAME);
	}

	public function setIsReport($setting=TRUE) {
		$this->is_report = $setting;
	}

	/**
	 * Don't build a display for the web, just get the info needed.
	 * XXX This is used by the auto-assignment routine, to get the needed
	 * dates.
	 */
	public function disableWebDisplay() {
		$this->web_display = FALSE;
	}

	public function loadAssignments() {
		global $json_assignments_file;
		$dir = BASE_DIR;
		$file = $json_assignments_file;

		if (!file_exists($file)) {
			return FALSE;
		}

		$this->assignments = json_decode(file_get_contents($file), true);
	}

	public function renderMonthsOverlay() {
		$current_season = get_current_season();

		$out = "";
EOHTML;
		
		foreach($current_season as $month_num=>$month_name) {
			$out .= <<<EOHTML
				<li><a href="#{$month_name}">{$month_name}</a></li>
EOHTML;
		}

		// add admin-only options
		if (!isset($_SESSION['access_type']) ||
			($_SESSION['access_type'] != 'guest')) {
			$out .= <<<EOHTML
<!--				<li><a href="#confirm_checks">confirm checks</a></li> -->
<!--				<li><a href="#special_requests">Special Requests</a></li> -->
EOHTML;
		}

		return <<<EOHTML
			<ul id="summary_overlay">
				<li>Jump to:</li>
				<li><a href="#top">Top of Page</a></li>
				{$out}
				<li><a href="#end">Bottom of Page</a></li>
				<li><a href="{$dir}/index.php">Survey Home Page</a></li>
			</ul>
EOHTML;
	}


	/**
	 * Get the weekly spacer html.
	 */
	protected function renderWeekSelector() {
		return <<<EOHTML
			<td class="week_selector multiselector">
				mark this whole week:
				<a class="prefer">prefer</a>
				<a class="ok">ok</a>
				<a class="avoid">avoid</a>
			</td>
EOHTML;
	}

	/**
	 * Get the weekday selector html
	 * @param[in] day_num int the meal number for the season, for debugging.
	 * @param[in] day_of_week string, the short name for the day of the week,
	 *     e.g. 'Tue'.
	 * @return string the rendered html.
	 */
	protected function renderWeekdaySelector($day_num, $day_of_week) {
		if (0) deb ("calendar.renderWeekdaySelector: day_num, day_of_week", $day_num. ", " . $day_of_week);
		$short_day = substr($day_of_week, 0, 3);
		// $short_day = $day_of_week;
		$day_of_week_name = date('l', $day_num);
		return <<<EOHTML
			<td class="weekday_selector weekday_num_{$day_num} multiselector" >
				mark every {$short_day}:<br>
				<a class="prefer">prefer</a>
				<a class="ok">ok</a>
				<a class="avoid">avoid</a>
			</td>
EOHTML;
	}

	protected function renderMonthSelector() {
		return <<<EOHTML
			<tr>
				<td class="month_selector multiselector"  colspan=8>
					<p style="text-align:center;" class="month_mark_all">mark entire month: 
					<a class="prefer">prefer</a> 
					<a class="ok">ok</a> 
					<a class="avoid">avoid</a></p>
				</td>
			</tr>
EOHTML;
	}

	
	/**
	 * Figure out which dates have which shifts applied to them.
	 *
	 * @param[in] worker array (optional) 
	 *     If set, then this calendar will act in survey mode, presenting
	 *     the worker with the list of shifts they need to fill for each day
	 *     that the shift is available. If not set, then use report mode and show
	 *     a summary of all available workers for that date.
	 * @param[in] dates array of date/job_id/preference level listing available
	 *     workers for the shift. Currently only used for reporting.
	 */
	public function evalDates($worker=NULL, $dates=NULL) {
		global $sunday_jobs;
		global $weekday_jobs;
		global $mtg_jobs;
		global $mtg_nights;

		$current_season = get_current_season();

		$meal_days = get_weekday_meal_days();

		$mtg_day_count = array();
		foreach(array_keys($mtg_nights) as $dow) {
			$mtg_day_count[$dow] = 0;
		}

		$weekly_spacer = '';
		$weekly_selector = '';
		if (!is_null($worker)) {
			$saved_prefs = $this->getSavedPrefs($worker->getId());
			$day_spacer = '<td class="day_of_week" style="background:yellow;" width="1%"><!-- weekly spacer --></td>';
			$weekly_spacer = '<td class="multiselector" width="1%"><!-- weekly spacer --></td>';
			$weekly_selector = $this->renderWeekSelector();
		}

		$blank_day = '<td></td>';
		$blank_header_cell = '<td class="blank_header_cell"></td>';

		$day_labels = '';
		$day_num = 0;
		
		// set up the labels and selectors
		$day_selectors = '';
		foreach(get_days_of_week() as $dow) {
			$day_labels .= <<<EOHTML
				<th class="day_of_week">{$dow}</th>
EOHTML;

			// only create the selectors when a worker is specified, meaning
			// for survey mode
			if (!is_null($worker)) {
				if (in_array($day_num, array_merge(array(0), $meal_days))) {
					$day_selectors .= $this->renderWeekdaySelector($day_num, $dow);
				}
				else {
					$day_selectors .= $blank_header_cell;
				}
			}
			$day_num++;
		}
		$day_labels = <<<EOHTML
			<tr class="day_labels">
				{$day_spacer}
				{$day_labels}
			</tr>
EOHTML;

		$selectors = '';
		if (!is_null($worker)) {
				$selectors .= <<<EOHTML
				<tr class="weekdays">
					{$weekly_spacer}
					{$day_selectors}
				</tr>
EOHTML;
		}

		$day_of_week = NULL;
		$out = '';
		$dates_and_shifts = array();
		// for each month in the season
		$month_count = 0;
		foreach($current_season as $month_num=>$month_name) {
			$month_count++;
			$month_entries = array();
			$month_week_count = 1;

			// get unix ts
			$start_ts = strtotime("{$month_name} 1, " . SEASON_YEAR);
			$days_in_month = date('t', $start_ts);

			// figure out the first day of the starting month
			if (is_null($day_of_week)) {
				// pull out the dow
				$day_of_week = date('w', $start_ts);
			}

			$week_num = date('W', $start_ts);
			$week_id = "week_{$week_num}_{$month_num}";
			$table = <<<EOHTML
				<tr class="week" id="{$week_id}">
					{$weekly_selector}
EOHTML;
			for($dw = 0; $dw < $day_of_week; $dw++) {
				$table .= $blank_day;
			}

			foreach(array_keys($mtg_day_count) as $key) {
				$mtg_day_count[$key] = 0;
			}

			// for each day in the current month
			for ($i=1; $i<=$days_in_month; $i++) {
				$tally = '';
				$this_year = SEASON_YEAR;
				if (0) deb("calendar.evalDate(): month={$month_num}, date={$i}, year={$this_year}");
				// $today =  $month_num . "/" . $i . "/" . SEASON_YEAR;

				// if this is sunday... add the row start
				if (($day_of_week == 0) && ($i != 1)) {
					$week_num++;
					$week_id = "week_{$week_num}_{$month_num}";
					$table .= <<<EOHTML
						<tr class="week" id="{$week_id}">
							{$weekly_selector}
EOHTML;
				}

				$date_string = "{$month_num}/{$i}/" . SEASON_YEAR;
				$cell = '';		
				$skip_dates = get_skip_dates();
				if (0) deb("calendar.evalDates(): skip_dates:", $skip_dates); 
				if (0) deb("calendar.evalDates(): date_string: <b>$date_string</b>"); 				
				if (0) deb("calendar.evalDates(): month_num:", $i); 				
				if (0) deb("calendar.evalDates: Meals on Holidays?", MEALS_ON_HOLIDAYS);
				
				// check for holidays
				if (isset($this->holidays[$month_num]) &&
					in_array($i, $this->holidays[$month_num]) && 
					!MEALS_ON_HOLIDAYS) {
					$cell .= '<span class="skip">holiday</span>';
				}
				// check for manual skip dates
				// SUNWARD: using manual skip_dates for community meeting nights because the GO formula for CM nights is not true for us
				else if (isset($skip_dates[$month_num]) &&
					in_array($i, $skip_dates)) {
					// in_array($i, $skip_dates[$month_num])) {
					$cell = '<span class="skip">community meeting</span>';
				}
				// sundays
				else if (ARE_SUNDAYS_UNIQUE && ($day_of_week == 0)) {
					$this->num_shifts['sunday']++;

					if (!$this->web_display) {
						$jobs_list = array_keys($sunday_jobs);
						if (!empty($jobs_list)) {
							$dates_and_shifts[$date_string] = $jobs_list;
						}
					}
					else if (!is_null($worker)) {
						foreach($sunday_jobs as $key=>$name) {
							$saved_pref_val =
								isset($saved_prefs[$key][$date_string]) ?
									$saved_prefs[$key][$date_string] : NULL;

							// if this job is in the list of assigned tasks.
							if (array_key_exists($key, $worker->getTasks())) {
								$cell .= $this->renderday($date_string, $name, $key,
									$saved_pref_val);
							}
						}
					}
					// generate the date cell for the report
					else if (!empty($dates) &&
						array_key_exists($date_string, $dates)) {
						// report the available workers
						$tally = <<<EOHTML
<span class="type_count">[S{$this->num_shifts['sunday']}]</span>
EOHTML;
						$cell = $this->list_available_workers($date_string,
							$dates[$date_string], TRUE);
					}
				}
				// process weekday meals nights
				else if (in_array($day_of_week, $meal_days)) {

					// is this a meeting night?
					// is this the nth occurence of a dow in the month?
					$ordinal_int = intval(($i - 1) / 7) + 1;
					$is_mtg_night = FALSE;

					$reg_meal_override = FALSE;
					$mtg_override = FALSE;
					if ($month_num == 9) {
						if ($i == 19) {
							$mtg_override = TRUE;
						}
						else if ($i == 17) {
							$reg_meal_override = TRUE;
						}
					}

					if ($mtg_override || (!$reg_meal_override &&
						array_key_exists($day_of_week, $mtg_nights) &&
						($mtg_nights[$day_of_week] == $ordinal_int))) {
						$is_mtg_night = TRUE;
						$this->num_shifts['meeting']++;
						$jobs = $mtg_jobs;
					}
					else {
						$this->num_shifts['weekday']++;
						$jobs = $weekday_jobs;
					}

					if (!$this->web_display) {
						$jobs_list = array_keys($jobs);
						if (!empty($jobs_list)) {
							$dates_and_shifts[$date_string] = $jobs_list;
						}
					}
					else if (!is_null($worker)) {
						if (0) deb("calendar.evalDates(): worker = ", $worker);
						if (0) deb("calendar.evalDates(): worker['num_shifts_to_fill'] = ", $worker->num_shifts_to_fill);		
						foreach($jobs as $key=>$name) {
							if (0) deb("calendar.evalDates(): worker->num_shifts_to_fill['key'] = ", $worker->num_shifts_to_fill[$key]);
							if ($worker->num_shifts_to_fill[$key] == 0) continue;
							$saved_pref_val =
								isset($saved_prefs[$key][$date_string]) ?
									$saved_prefs[$key][$date_string] : NULL;

							if (array_key_exists($key, $worker->getTasks())) {
								// is this preference saved already?

								$cell .= $this->renderday($date_string, $name,
									$key, $saved_pref_val);
							}
						}
					}
					else if ($is_mtg_night) {
						$tally = <<<EOHTML
<span class="type_count">[M{$this->num_shifts['meeting']}]</span>
EOHTML;
						$cell .= '<span class="note">meeting night</span>';
						// report the available workers
						$cell .= $this->list_available_workers($date_string,
							$dates[$date_string]);
					}
					// generate the date cell for the report
					else if (array_key_exists($date_string, $dates)) {
						$tally = <<<EOHTML
<span class="type_count">[W{$this->num_shifts['weekday']}]</span>
EOHTML;

						// report the available workers
						$cell .= $this->list_available_workers($date_string,
							$dates[$date_string]);
					}
				}

				$table .= <<<EOHTML
				<td class="dow_{$day_of_week}">
					<div class="date_number">{$i}{$tally}</div>
					{$cell}
				</td>
EOHTML;
				if (0) deb("calendar.list_available_workers(): cell = ", $cell);

				// close the row at end of week (saturday)
				// if ($day_of_week == 6 || $i == $days_in_month) {
				if ($day_of_week == 6) {
					$table .= "\n</tr>\n";
					$month_week_count++;
				}

				$day_of_week++;
				// wrap-around
				if ($day_of_week == 7) {
					$day_of_week = 0;
				}
			}
			if (0) deb("Day of week after last day of {$month_name}:", $day_of_week);

			// Fill out last cells of last week of month with blanks and end the calendar row
			if (!$day_of_week == 0) {
				for($dw = $day_of_week; $dw < 7; $dw++) {		
					$table .= $blank_day;
				}
				$table .= "\n</tr>\n";
			}
	
			if (!$this->web_display) {
				continue;
			}

			$survey = ($this->is_report) ? '' : 'survey';
			$quarterly_month_ord = ($month_num % 4);
			$season_year = SEASON_YEAR;
			$month_selector = (!$this->is_report ? $this->renderMonthSelector() : ""); 
			if (0) deb("survey.evalDates(): day_labels =", '"'.$day_labels.'"');
			if (0) deb("calendar.evalDates(): day_selectors = ", $day_selectors);
			if (0) deb("calendar.evalDates(): weekly_spacer = ", '"'.$weekly_spacer.'"');
			if (0) deb("calendar.evalDates(): selectors = ", $selectors);
			if (0) deb("calendar.evalDates(): table = ", $table);
			
			$out .= <<<EOHTML
			<div id="{$month_name}" class="month_wrapper">
				<div class="surround month_{$quarterly_month_ord}">
					<table cellpadding="8" cellspacing="3" border="1" width="100%">
						<tr>
							<td colspan=8 style="text-align:center; background:yellow;">
								<h2 class="month {$survey}" style="text-align:center;">{$month_name} {$season_year}</h2>
							</td>
						</tr>
						{$month_selector}
						{$day_labels}
						{$selectors}
						{$table}
					</table>
				</div>
				<br>
			</div>
EOHTML;
			if (0) deb("calendar.evalDates(): out = ", $out);			
		}

		if (!$this->web_display) {
			return $dates_and_shifts;
		}

		return $out;
	}

	/*
	 * Find the saved preferences for this worker
	 *
	 * @param[in] worker_id the ID number of the current worker
	 * @return array of already-saved preferences for this worker. If empty,
	 *     then this worker has not taken the survey yet.
	 */
	private function getSavedPrefs($worker_id) {
		if (!is_numeric($worker_id)) {
			return array();
		}

		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$sql = <<<EOJS
			select s.id, s.string, s.job_id, p.pref
				FROM {$shifts_table} as s, {$prefs_table} as p
				WHERE s.id=p.date_id
					AND worker_id={$worker_id}
					ORDER BY s.string, s.job_id
EOJS;
		if (0) deb("calendar: SQL to read from shifts and shift_prefs:", $sql);

		global $dbh;
		$data = array();
		foreach ($dbh->query($sql) as $row) {
			if (!array_key_exists($row['job_id'], $data)) {
				$data[$row['job_id']] = array();
			}
			$data[$row['job_id']][$row['string']] = $row['pref'];
		}

		return $data;
	}


	/*
	 * Draw an individual survey table cell for one day.
	 *
	 * @param[in] date_string string of text representing a date, i.e. '12/6/2009'
	 * @param[in] name string name of the job
	 * @param[in] key int the job ID
	 * @param[in] saved_pref number the preference score previously saved
	 */
	private function renderday($date_string, $name, $key, $saved_pref) {
		global $pref_names;

		$name = preg_replace('/^.*meal /i', '', $name);
		// shorten meal names in the survey calendar
		$drop = array(
			' (twice a season)',
			'Meeting night ',
			'Sunday ',
			' (two meals/season)',
		);
		$name = str_replace($drop, '', $name);

		$sel = array('', '', '');
		if (!is_numeric($saved_pref)) {
			$saved_pref = 1;
		}
		$sel[$saved_pref] = 'selected';

		$id = "{$date_string}_{$key}";
		return <<<EOHTML
			<div class="choice">
			{$name}
			<select name="{$date_string}_{$key}" class="preference_selection">
				<option value="2" {$sel[2]}>{$pref_names[2]}</option>
				<option value="1" {$sel[1]}>{$pref_names[1]}</option>
				<option value="0" {$sel[0]}>{$pref_names[0]}</option>
			</select>
			</div>
EOHTML;
	}

	/**
	 * Return HTML to select what info to display in calendar day cells.
	 * @param[in] job_key string Either an int representing the unique ID
	 *     for the job to report on, or 'all' to show all jobs.
	 * @param[in] data_key string Either an int representing the type of data
	 *     to report on, or 'all' to show all data.
	 * @return string HTML for displaying the display selectors.
	 */
	public function renderDisplaySelectors($job_key, $data_key) {
		global $all_jobs;
		$dir = BASE_DIR;
		$selector_html = '';
		if (0) deb("report.renderDisplaySelectors(): _GET = ", $_GET);
		if (0) deb("report.renderDisplaySelectors(): job_key = $job_key, data_key = $data_key");
		
		// What jobs to display?
		$selector_html .= "<p><em>show these jobs: </em>";		
		foreach($all_jobs as $key=>$label) {
			$only = ($label == 'all' ? "" : " only");
			if (isset($_GET['key']) && ($key == $job_key)) {
				$selector_html .= "&nbsp;&nbsp;<b>{$label}{$only}</b>";
				continue;
			}
			$selector_html .= <<<EOHTML
&nbsp;&nbsp;<a href="{$dir}/report.php?key={$key}&show={$data_key}">{$label}{$only}</a>
EOHTML;
		}
		
		// What data to display for each job?
		$selector_html .= "<p><em>show these data: </em>";
		$data_types = array('all', 'assignments', 'preferences');
		foreach($data_types as $key=>$data_type) {
			$only = ($data_type == 'all' ? "" : " only");
			if (0) deb("report.renderDisplaySelectors(): key = $key, data_type = $data_type, data_key = $data_key");
			if (isset($_GET['show']) && ($data_type == $data_key)) {
				$selector_html .= "&nbsp;&nbsp;<b>{$data_type}{$only}</b>";
				continue;
			}
			$selector_html .= <<<EOHTML
&nbsp;&nbsp;<a href="{$dir}/report.php?key={$job_key}&show={$data_type}">{$data_type}{$only}</a>
EOHTML;
		}
		return $selector_html;
	}

	/**
	 * Load which dates the workers have marked as being available.
	 */
	function getWorkerDates() {
		// grab all the preferences for every date
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT s.string, s.job_id, a.username, p.pref
				FROM {$auth_user_table} as a, {$prefs_table} as p,
					{$shifts_table} as s
				WHERE p.pref>0
					AND a.id=p.worker_id
					AND s.id = p.date_id
				ORDER BY s.string ASC,
					p.pref DESC,
					a.username ASC;
EOSQL;
		$data = array();
		global $dbh;
		foreach($dbh->query($sql) as $row) {
			$data[] = $row;
		}

		$dates = array();
		foreach($data as $d) {
			if (!array_key_exists($d['string'], $dates)) {
				$dates[$d['string']] = array();
			}
			if (!array_key_exists($d['job_id'], $dates[$d['string']])) {
				$dates[$d['string']][$d['job_id']] = array();
			}
			$dates[$d['string']][$d['job_id']][$d['pref']][] = $d['username'];
		}

		if (0) deb("Calendar: dates array =", $dates);
		return $dates;
	}

	public function getWorkerComments($job_key_clause) {
		$special_prefs = array(
			'avoids',
			'prefers',
			'clean_after_self',
			'bunch_shifts',
			'bundle_shifts',
		);

		// render the comments
		$comments_table = SCHEDULE_COMMENTS_TABLE;
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT a.username, c.*
				FROM {$auth_user_table} as a, {$comments_table} as c
				WHERE c.worker_id=a.id
					AND a.username in (SELECT u.username
							FROM {$auth_user_table} as u, {$prefs_table} as p,
								{$shifts_table} as s
							WHERE u.id=p.worker_id
								AND p.date_id=s.id
								{$job_key_clause}
							GROUP BY u.username)
				ORDER BY a.username, c.timestamp
EOSQL;
		$comments = array();
		$out = '</a><h2>Comments</h2>\n';
		$checks = array();
		$check_separator = 'echo "-----------";';
		global $dbh;
		foreach($dbh->query($sql) as $row) {
			$username = $row['username'];

			$requests = '';
			foreach($special_prefs as $req) {
				if (empty($row[$req])) {
					continue;
				}
				if ($row[$req] === 'dc') {
					continue;
				}

				$requests .= "{$req}: {$row[$req]}<br>\n";

				// generate check script lines
				switch($req) {
				case 'avoids':
					$avoids = explode(',', $row[$req]);
					foreach($avoids as $av) {
						$checks[] = $check_separator;
						$checks[] = "echo '{$username}' avoids '{$av}'";
						$checks[] = "grep '{$username}' " . RESULTS_FILE .
							" | grep '{$av}'";
					}
					break;

				case 'prefers':
					$prefers = explode(',', $row[$req]);
					foreach($prefers as $pr) {
						$checks[] = $check_separator;
						$checks[] = "echo '{$username}' prefers '{$pr}'";
						$checks[] = "grep '{$username}' " . RESULTS_FILE .
							" | grep '{$pr}'";
					}
					break;

				case 'clean_after_self':
					$checks[] = $check_separator;
					$checks[] = "echo '{$username}' clean after self: '{$row[$req]}'";
					$checks[] = "grep '{$username}.*{$username}' " . RESULTS_FILE;
					break;

				/* not sure if these are used right now
				case 'bunch_shifts':
				case 'bundle_shifts':
				*/
				}
			}

			$comments[] = $row;
			$remark = stripslashes($row['comments']);
			$content = (empty($requests) && empty($remark)) ? '' :
				"<p>{$requests}<br>{$remark}</p>\n";

			$out .= <<<EOHTML
		<fieldset>
			<legend>{$username} - {$row['timestamp']}</legend>
			{$content}
		</fieldset>
EOHTML;
		}

		$check_script = implode("\n", $checks);
		$check_script = <<<EOHTML
<h2 id="confirm_checks">Confirm results check</h2>
<div class="confirm_results">{$check_script}</div>
EOHTML;
		return $out . $check_script;
	}

	/**
	 * Get a select list of the various workers available.
	 * @param [in] id string, denotes name of DOM element and form element
	 *     name.
	 * @param[in] first_entry boolean, defaults to FALSE, if true,
	 *     then pre-pend the list with a blank entry.
	 * @param[in] skip_user string (defaults to NULL), if not null, then don't
	 *     display this users' name in the list.
	 * @param[in] chosen array specifies as list of chosen usernames.
	 * @param[in] only_user boolean (default FALSE), if true, then instead of a
	 *     "(x) remove" link, display a "clear" link.
	 */
	public function getWorkerList($id, $first_entry=FALSE, $skip_user=NULL,
		$chosen=array(), $only_user=FALSE) {

		$worker_list = new WorkersList();
		$workers = $worker_list->getWorkers();
		$options = ($first_entry) ? '<option value="none"></option>' : '';
		foreach($workers as $username=>$info) {
			if (!is_null($skip_user) && $username == $skip_user) {
				continue;
			}

			if ($info['first_name'] . " " . $info['last_name'] == $username) {
				$visible_name = <<<EOTXT
{$info['first_name']} {$info['last_name']}
EOTXT;
			} else {
				$visible_name = <<<EOTXT
{$info['first_name']} {$info['last_name']} ({$username})
EOTXT;
			}
			
			$selected = isset($chosen[$username]) ? ' selected' : '';
			$options .= <<<EOHTML
			<option value="{$username}"{$selected}>{$visible_name}</option>
EOHTML;
		}

		return <<<EOHTML
		<select name="{$id}[]" id="{$id}" multiple="multiple">
			{$options}
		</select>
EOHTML;
	}

	/*
	 * reporting feature - list the workers available for this day
	 */
	private function list_available_workers($date_string, $cur_date_prefs, $is_sunday=FALSE) {
		$cell = '';

		$job_titles = array();
		if ($is_sunday) {
			global $sunday_jobs;
			$job_titles = $sunday_jobs;
		}
		else {
			global $weekday_jobs;
			global $mtg_jobs;
			$job_titles = $weekday_jobs + $mtg_jobs;
		}

		if ($this->key_filter != 'all') {
			// don't figure out a listing for a non-supported day of week
			if (!isset($cur_date_prefs[$this->key_filter])) {
				return;
			}

			$cur_date_prefs = array($this->key_filter =>
				$cur_date_prefs[$this->key_filter]);
		}

		if (0) deb("calendar.list_available_workers(): cur_date (pre-sort) = ", $cur_date_prefs);
		ksort($cur_date_prefs);
		if (0) deb("calendar.list_available_workers(): cur_date_prefs (sorted) = ", $cur_date_prefs);
		if (0) deb("calendar.list_available_workers(): cur_date_assignments (sorted) = ", $this->cur_date_assignments);
		ksort($this->assignments[$date_string]);
		if (0) deb("calendar.list_available_workers(): this->assignments[date_string] (sorted) = ", $this->assignments[$date_string]);
		
		foreach($cur_date_prefs as $job=>$info) {
			// don't report anything for an empty day
			if (empty($info)) {
				if (isset($job_titles[$job])) {
					$cell .= '<div class="warning">empty!<div>';
				}
				continue;
			} 

			// show job title
			$cell .= "<h3 class=\"jobname\">{$job_titles[$job]}</h3>\n";

			// list people assigned to this job on this date
			if ($this->data_key == 'all' || $this->data_key == 'assignments') {
				$cell .= '<ul style="background:lightgreen;">';
				$assignments = $this->render_assignments($date_string, $job_key);
				if (0) deb("calendar.list_available_workers(): assignments = ", $assignments);
				if (0) deb("calendar.list_available_workers(): job key = $job, assmt job id = {$assignment['job_id']}, assignee = {$assignment['worker_name']}");
				foreach($assignments as $key=>$assignment) {
					// if (0) deb("calendar.list_available_workers(): job key = $job, assmt job id = {$assignment['job_id']}, assignee = {$assignment['worker_name']}");
					if ($job == $assignment['job_id']) {
						if (0) deb("calendar.list_available_workers(): job key = $job, assmt job id = {$assignment['job_id']}, assignee = {$assignment['worker_name']}");
						$cell .= "<li><strong>{$assignment['worker_name']}</strong></li>";  
					}
				}
				$cell .= "</ul>";
			}

			if (0) deb("calendar.list_available_workers(): job_key = $job_key, job = $job");			
			if ($this->data_key == 'all' || $this->data_key == 'preferences') {
				// list people who prefer the job first
				if (array_key_exists(2, $info)) {
					$cell .= '<div class="highlight">prefer:<ul><li>' . 
						implode("</li>\n<li>\n", $info[2]) . 
						"</li></ul></div>\n";
				}

				// next, list people who would be ok with it
				if (array_key_exists(1, $info)) {
					$cell .= '<div class="ok">ok:<ul><li>' . 
						implode("</li>\n<li>\n", $info[1]) . 
						"</li></ul></div>\n";
				}
			}
		}
		if (0) deb("calendar.list_available_workers(): cell = ", $cell); 
		return $cell;
	}

	private function render_assignments($date_string=NULL, $job_id=NULL) {
		// list the assigned workers
		$date_clause = ($date_string ? "and s.string = '{$date_string}'" : "");
		$job_id_clause = ($job_id ? "and j.id = '{$job_id}'" : "");
		if (0) deb("calendar.render_assignments(): this->assignments[date_string]:", $this->assignments[$date_string]); 				
		$select = "w.first_name || ' ' || w.last_name as worker_name, a.shift_id, a.worker_id, a.scheduler_timestamp, s.id as shift_id, s.string as meal_date, j.id as job_id, j.description";
		$from = AUTH_USER_TABLE . " as w, " . ASSIGNMENTS_TABLE . " as a, " . SCHEDULE_SHIFTS_TABLE . " as s, " . SURVEY_JOB_TABLE . " as j";
		$where = "w.id = a.worker_id and a.shift_id = s.id and s.job_id = j.id {$date_clause} and j.season_id = " . SEASON_ID . 
			" and a.scheduler_timestamp = (select max(scheduler_timestamp) from " . ASSIGNMENTS_TABLE . ") {$job_id_clause}";
		$order_by = "j.display_order";
		$cur_date_assignments = sqlSelect($select, $from, $where, $order_by);
		if (0) deb("calendar.render_assignments(): cur_date_assignments:", $cur_date_assignments);
		return $cur_date_assignments;
	}

	public function getNumShifts() {
		return $this->num_shifts;
	}

	/**
	 * Show the number of shifts to assign:
	 */
	public function getShiftCounts() {
		$sum = 0;
		$jobs = $this->num_shifts;
		$jobs['total'] = 0;

		// compute the total row
		foreach($this->num_shifts as $job=>$count) {
			$jobs['total'] += $count;
		}

		return $jobs;
	}

	/**
	 * Output this calendar to a string
	 * @return string html to display.
	 */
	public function toString($worker=NULL, $dates=NULL, $show_counts=FALSE) {
		if (is_null($worker) && empty($dates)) return;
		
		$out = $this->evalDates($worker, $dates, TRUE);		
		return $out;
	}
}
?>
