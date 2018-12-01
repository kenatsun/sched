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

		$this->holidays = get_holidays(SEASON_TYPE);
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
		if (0) deb("calendar.disableWebDisplay(before): this->web_display () = " . $this->web_display);
		$this->web_display = FALSE;
		if (0) deb("calendar.disableWebDisplay(after): this->web_display () = " . $this->web_display);
	}

	public function renderMonthsOverlay() {
		$current_season = get_current_season_months();

		$out = "";
		
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
	public function renderMealsInCalendar($worker=NULL, $meals=NULL) {
		global $sunday_jobs;
		global $weekday_jobs;
		global $mtg_jobs;
		global $mtg_nights;
		if (0) deb("calendar.renderMealsInCalendar(): weekday_jobs =", $weekday_jobs);

		$current_season = get_current_season_months();

		$meal_days = get_weekday_meal_days();

		$mtg_day_count = array();
		foreach(array_keys($mtg_nights) as $dow) {
			$mtg_day_count[$dow] = 0;
		}

		$weekly_spacer = '';
		$weekly_selector = '';
		if (!is_null($worker)) {
			$saved_prefs = $this->getShiftPrefs($worker->getId());
			if (0) deb("calendar.renderMealsInCalendar(): saved_prefs = ", $saved_prefs);		
			$day_spacer = '<td class="day_of_week" style="background:yellow;" width="1%"><!-- daily spacer --></td>';
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
		$meals_and_shifts = array();
		
		// for each month in the season
		$month_count = 0;
		if (0) deb("calendar.renderMealsInCalendar(): current_season = ", $current_season);		
		foreach($current_season as $month_num=>$month_name) {
			$month_count++;
			$month_entries = array();
			$month_week_count = 1;

			// get unix ts
			$start_ts = strtotime("{$month_name} 1, " . SEASON_START_YEAR);
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
				$this_year = SEASON_START_YEAR;
				if (0) deb("calendar.renderMealsInCalendar(): month={$month_num}, date={$i}, year={$this_year}");

				// if this is sunday, add the row start
				if (($day_of_week == 0) && ($i != 1)) {
					$week_num++;
					$week_id = "week_{$week_num}_{$month_num}";
					$table .= <<<EOHTML
						<tr class="week" id="{$week_id}">
							{$weekly_selector}
EOHTML;
				}

				$date_string = SEASON_START_YEAR . "/" . zeroPad($month_num, 2) . "/" . zeroPad($i, 2); 
				$meal = sqlSelect("*", MEALS_TABLE, "date = '{$date_string}'", "", (0), "calendar.renderMealsInCalendar(): meal")[0];
				$meal_id = $meal['id'];
				if (0) deb("calendar.renderMealsInCalendar(): SEASON_START_YEAR: " . SEASON_START_YEAR); 
				$cell = '';	
				if (0) deb("calendar.renderMealsInCalendar(): date_string: {$date_string}"); 				
				if (0) deb("calendar.renderMealsInCalendar(): month_num = {$month_num}, day_num = {$i}", NULL);			
				if (0) deb("calendar.renderMealsInCalendars: Meals on Holidays?", MEALS_ON_HOLIDAYS);
				
				// if today is a holiday, show that in the cell
				if (isset($this->holidays[$month_num]) &&
					in_array($i, $this->holidays[$month_num]) && 
					!MEALS_ON_HOLIDAYS) {
					$cell .= '<span class="skip">holiday</span>';
				}

				// if today's meal is marked as a skip date, show that in the cell
				// SUNWARD: using manual skip_dates for community meeting nights because the GO formula for CM nights is not true for Sunward
				elseif ($meal['skip_indicator']) {
					$cell = '<span class="skip">' . $meal['skip_reason'] . '</span>';				
				}

				// if today is a Sunday, show that in the cell
				elseif (ARE_SUNDAYS_UNIQUE && ($day_of_week == 0)) {
					$this->num_shifts['sunday']++;

					if (!$this->web_display) {
						$jobs_list = array_keys($sunday_jobs);
						if (!empty($jobs_list)) {
							$meals_and_shifts[$meal_id] = $jobs_list;
							// $meals_and_shifts[$date_string] = $jobs_list;
						}
					}
					elseif (!is_null($worker)) {
						foreach($sunday_jobs as $job_id=>$job_name) {
							if (0) deb("calendar.renderMealsInCalendar(): worker->num_shifts_to_fill['job_id'] = ", $worker->num_shifts_to_fill[$job_id]);
							if ($worker->num_shifts_to_fill[$job_id] == 0) continue;
							// get id of shift for this job in this meal
							$select = "id";
							$from = SCHEDULE_SHIFTS_TABLE;
							$where = "meal_id = {$meal_id} and job_id = {$job_id}";
							$shift_id = sqlSelect($select, $from, $where, "", (0), "calendar.renderMealsInCalendar(): shift_id")[0]['id'];
							$saved_pref_val =
								isset($saved_prefs[$job_id][$shift_id]) ?
									$saved_prefs[$job_id][$shift_id] : NULL;

							if (array_key_exists($job_id, $worker->getTasks())) {
								// is this preference saved already?
								$cell .= $this->renderDay($shift_id, $job_name, $job_id, $saved_pref_val);
							}
						}
					}
					// generate the date cell for the report
					elseif (!empty($dates) &&
						array_key_exists($meal_id, $meals)) {
						// report the available workers
						$tally = <<<EOHTML
<span class="type_count">[S{$this->num_shifts['sunday']}]</span>
EOHTML;
						$cell = $this->list_available_workers($meal_id, $meals[$meal_id], TRUE);
					}
				}
				// process weekday meals nights
				elseif (in_array($day_of_week, $meal_days)) {

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
						if (0) deb("calendar.renderMealsInCalendar(): weekday_jobs =", $weekday_jobs);
						$jobs = $weekday_jobs;
					}
					if (0) deb("calendar.renderMealsInCalendar(): jobs = ", $jobs);
					if (0) deb("calendar.renderMealsInCalendar(): this->web_display = " . $this->web_display);

					if (!$this->web_display) {
						$jobs_list = array_keys($jobs);
						if (!empty($jobs_list)) {
							$meals_and_shifts[$meal_id] = $jobs_list;
						}
					}
					else if (!is_null($worker)) {
						if (0) deb("calendar.renderMealsInCalendar(): jobs = ", $jobs);
						if (0) deb("calendar.renderMealsInCalendar(): worker = ", $worker);
						if (0) deb("calendar.renderMealsInCalendar(): worker['num_shifts_to_fill'] = ", $worker->num_shifts_to_fill);		
						foreach($jobs as $job_id=>$job_name) {
							if (0) deb("calendar.renderMealsInCalendar(): worker->num_shifts_to_fill['job_id'] = ", $worker->num_shifts_to_fill[$job_id]);
							if ($worker->num_shifts_to_fill[$job_id] == 0) continue;
							// get id of shift for this job in this meal
							$select = "id";
							$from = SCHEDULE_SHIFTS_TABLE;
							$where = "meal_id = {$meal_id} and job_id = {$job_id}";
							$shift_id = sqlSelect($select, $from, $where, "", (0), "calendar.renderMealsInCalendar(): shift_id")[0]['id'];
							$saved_pref_val =
								isset($saved_prefs[$job_id][$shift_id]) ?
									$saved_prefs[$job_id][$shift_id] : NULL;

							if (array_key_exists($job_id, $worker->getTasks())) {
								// is this preference saved already?
								$cell .= $this->renderDay($shift_id, $job_name, $job_id, $saved_pref_val);
							}
						}
					}
					elseif ($is_mtg_night) {
						$tally = <<<EOHTML
<span class="type_count">[M{$this->num_shifts['meeting']}]</span>
EOHTML;
						$cell .= '<span class="note">meeting night</span>';
						// report the available workers
						$cell .= $this->list_available_workers($meal_id, $meals[$meal_id]);
					}
					// generate the date cell for the report
					elseif (array_key_exists($meal_id, $meals)) {
						$tally = <<<EOHTML
<span class="type_count">[W{$this->num_shifts['weekday']}]</span>
EOHTML;

						// report the available workers
						$cell .= $this->list_available_workers($meal_id,
							$meals[$meal_id]);
					}
				}

				if (0) deb("calendar.php: renderMealsInCalendar(): current_season = ", $current_season[$month_num]);
				$month_short_name = substr($current_season[$month_num], 0, 3);
				$table .= <<<EOHTML
				<td class="dow_{$day_of_week}">
					<div class="date_number"><strong>{$i}</strong> <small><small>{$month_short_name}</small></small> {$tally}</div>
					{$cell}
				</td>
EOHTML;
				if (0) deb("calendar.list_available_workers(): cell = ", $cell);

				// close the row at end of week (saturday)
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
			$season_year = SEASON_START_YEAR;
			$month_selector = (!$this->is_report ? $this->renderMonthSelector() : ""); 
			if (0) deb("calendar.renderMealsInCalendar(): day_labels =", '"'.$day_labels.'"');
			if (0) deb("calendar.renderMealsInCalendar(): day_selectors = ", $day_selectors);
			if (0) deb("calendar.renderMealsInCalendar(): weekly_spacer = ", '"'.$weekly_spacer.'"');
			if (0) deb("calendar.renderMealsInCalendar(): selectors = ", $selectors);
			if (0) deb("calendar.renderMealsInCalendar(): table = ", $table);
			
			$out .= <<<EOHTML
			<div id="{$month_name}" class="month_wrapper">
				<div class="surround month_{$quarterly_month_ord}">
					<table cellpadding="8" cellspacing="3" border="1" width="100%" style="table-layout:auto">
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
			if (0) deb("calendar.renderMealsInCalendar(): out = ", $out);			
		}

		if (!$this->web_display) {
			if (0) debt("calendar.renderMealsInCalendar(): dates_and_shifts =", $meals_and_shifts);
			return $meals_and_shifts;
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
	private function getShiftPrefs($worker_id) {
		if (!is_numeric($worker_id)) {
			return array();
		}

		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$meals_table = MEALS_TABLE;
		$select = "s.id, 
			m.date, 
			s.job_id, 
			p.pref";
		$from = "{$shifts_table} as s, 
			{$prefs_table} as p,
			{$meals_table} as m";
		$where = "s.id = p.shift_id
			and s.meal_id = m.id
			and p.worker_id = {$worker_id}
			and m.season_id = " . SEASON_ID;
		$order_by = "m.date, s.job_id";
		$shift_prefs = sqlSelect($select, $from, $where, $order_by, (0), "calendar.getShiftPrefs()");
		
		$results = array();
		foreach ($shift_prefs as $shift_pref) {
			if (!array_key_exists($shift_pref['job_id'], $results)) {
				$results[$shift_pref['job_id']] = array();
			}
			$results[$shift_pref['job_id']][$shift_pref['id']] = $shift_pref['pref'];
		}
		if (0) deb("calendar.getShiftPrefs(): results = ", $results);
		return $results;
	}


	/*
	 * Draw an individual survey table cell for one day.
	 *
	 * @param[in] date_string string of text representing a date, i.e. '12/6/2009'
	 * @param[in] name string name of the job
	 * @param[in] key int the job ID
	 * @param[in] saved_pref number the preference score previously saved
	 */
	private function renderDay($shift_id, $job_name, $job_id, $saved_pref) {
		global $pref_names;
		if (0) deb("calendar.renderDay: shift_id = $shift_id, saved_pref = $saved_pref");
		$job_name = preg_replace('/^.*meal /i', '', $job_name);
		// shorten meal names in the survey calendar
		$drop = array(
			' (twice a season)',
			'Meeting night ',
			'Sunday ',
			' (two meals/season)',
		);
		$job_name = str_replace($drop, '', $job_name);
		
		$sel = array('', '', '');
		if (!is_numeric($saved_pref)) {
			$saved_pref = 1;
		}
		$sel[$saved_pref] = 'selected';

		$id = "{$meal_id}_{$job_id}";
		return <<<EOHTML
			<div class="choice">
			{$job_name}
			<select name="{$shift_id}_{$job_id}" class="preference_selection"> 
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
	function getWorkerShiftPrefs() {
		// grab all the preferences for every date in this season
		$season_id = SEASON_ID;
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$meals_table = MEALS_TABLE;
		$workers_table = AUTH_USER_TABLE;
		$offers_table = OFFERS_TABLE;
		$jobs_table = SURVEY_JOB_TABLE; 
		
		$select = "s.meal_id,
			s.job_id, 
			w.first_name || ' ' || w.last_name as username, 
			p.pref";
		$from = "{$workers_table} as w, 
			{$prefs_table} as p,
			{$shifts_table} as s,
			{$offers_table} as o,
			{$meals_table} as m";
		$where = "p.pref > 0
			AND w.id = p.worker_id
			AND w.id = o.worker_id
			AND s.job_id = o.job_id
			AND s.id = p.shift_id
			AND m.id = s.meal_id
			AND o.season_id = {$season_id}
			AND o.instances > 0";
		$order_by = "m.date ASC,
			p.pref DESC,
			w.first_name ASC,
			w.last_name ASC";
		$prefs = sqlSelect($select, $from, $where, $order_by, (0), "calendar.getWorkerShiftPrefs()");

		$shifts = array();
		foreach($prefs as $pref) {
			if (!array_key_exists($pref['meal_id'], $shifts)) {
				$shifts[$pref['meal_id']] = array();
			}
			if (!array_key_exists($pref['job_id'], $shifts[$pref['meal_id']])) {
				$shifts[$pref['meal_id']][$pref['job_id']] = array();
			}
			$shifts[$pref['meal_id']][$pref['job_id']][$pref['pref']][] = $pref['username'];
		}

		if (0) deb("Calendar.getWorkerShiftPrefs(): shifts array =", $shifts);
		return $shifts;
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
	private function list_available_workers($meal_id, $cur_date_prefs, $is_sunday=FALSE) {
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
		if (!empty($cur_date_prefs)) ksort($cur_date_prefs);
		if (0) deb("calendar.list_available_workers(): cur_date_prefs (sorted) = ", $cur_date_prefs);
		if (0) deb("calendar.list_available_workers(): cur_date_assignments (sorted) = ", $this->cur_date_assignments);
		
		foreach($cur_date_prefs as $job_id=>$prefs) {
			// don't report anything for an empty day
			if (empty($prefs)) {
				if (isset($job_titles[$job_id])) {
					$cell .= '<div class="warning">empty!<div>';
				}
				continue;
			} 

			// show job title
			$cell .= "<h3 class=\"jobname\">{$job_titles[$job_id]}</h3>\n";

			// list people assigned to this job on this date
			if ($this->data_key == 'all' || $this->data_key == 'assignments') {
				$cell .= '<ul style="background:lightgreen;">';
				// $assignments = $this->render_assignments($date_string, $job_key);
				$assignments = getJobAssignments($meal_id, $job_key);
				if (0) deb("calendar.list_available_workers(): assignments = ", $assignments);
				if (0) deb("calendar.list_available_workers(): job key = $job_id, assmt job id = {$assignment['job_id']}, assignee = {$assignment['worker_name']}");
				foreach($assignments as $key=>$assignment) {
					if ($job_id == $assignment['job_id']) {
						if (0) deb("calendar.list_available_workers(): job key = $job_id, assmt job id = {$assignment['job_id']}, assignee = {$assignment['worker_name']}");
						$cell .= "<li><strong>{$assignment['worker_name']}</strong></li>";  
					}
				}
				$cell .= "</ul>";
			}

			if (0) deb("calendar.list_available_workers(): job_key = $job_key, job = $job_id");			
			if ($this->data_key == 'all' || $this->data_key == 'preferences') {
				// list people who prefer the job first
				if (array_key_exists(2, $prefs)) {
					$cell .= '<div class="highlight">prefer:<ul><li>' . 
						implode("</li>\n<li>\n", $prefs[2]) . 
						"</li></ul></div>\n";
				}

				// next, list people who would be ok with it
				if (array_key_exists(1, $prefs)) {
					$cell .= '<div class="ok">ok:<ul><li>' . 
						implode("</li>\n<li>\n", $prefs[1]) . 
						"</li></ul></div>\n";
				}
			}
		}
		if (0) deb("calendar.list_available_workers(): cell = ", $cell); 
		return $cell;
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
	public function renderCalendar($worker=NULL, $shifts=NULL, $show_counts=FALSE) {
		if (is_null($worker) && empty($shifts)) return;
		
		$out = $this->renderMealsInCalendar($worker, $shifts, TRUE);		
		return $out;
	}
}
?>
