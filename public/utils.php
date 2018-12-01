<?php
require_once 'constants.inc';
require_once 'git_ignored.php';

if (0) deb("utils.php: start"); //_COOKIE =", $_COOKIE);

// connect to SQLite database
function create_sqlite_connection() {
	global $dbh;
	global $db_is_writable;
	$db_is_writable = FALSE;

	try {
		global $relative_dir;
		if (!isset($relative_dir)) { 
			$relative_dir = '';
		}
		else {
			$relative_dir .= '/';
		}

		$db_fullpath = getDatabaseFullpath();  // This function is in git_ignored.php because production & development use different databases
		$db_is_writable = is_writable($db_fullpath);
		$db_file = "sqlite:{$db_fullpath}";
		$dbh = new PDO($db_file);
		$timeout = 5; // in seconds
		$dbh->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
		// Enable foreign keys enforcement in database
		$dbh->exec("PRAGMA foreign_keys = ON;");
	}
	catch(PDOException $e) {
		echo "problem loading sqlite file [$db_fullpath]: {$e->getMessage()}\n";
		exit;
	}
}

// Work with assignments and changes from the latest scheduler run in the current season.
function scheduler_run() { 
	return sqlSelect("*", SCHEDULER_RUNS_TABLE, "season_id = " . SEASON_ID, "run_timestamp desc", (0))[0];
}

// DATE-RELATED FUNCTIONS - start ----------------------------------------------------

// An array containing various formattings of the months of the year
function months($start_month_num=1, $end_month_num=12) {
	$month = array();
	for($m=$start_month_num; $m<=$end_month_num; $m++) {
		// $month[] = array(); 
		$month[$m]['full_name'] = date('F', mktime(0,0,0, $m, 1));
		$month[$m]['short_name'] = date('M', mktime(0,0,0, $m, 1));
		$month[$m]['number'] = date('n', mktime(0,0,0, $m, 1));
		$month[$m]['number_zero_padded'] = date('m', mktime(0,0,0, $m, 1));
	}
	return $month;
}

// An HTML select field for the months of the next num_years years,
// starting with the current month of the current year,
// with the selected month shown as the current value
function renderUpcomingMonthsSelectList($field_name="months", $selected_date=NULL, $num_years=1) {
	if ($selected_date) {
		$selected_month_num = date("n", strtotime($selected_date));
		$selected_year = date("Y", strtotime($selected_date));
	}
	$start_year = date("Y");
	$start_month_num = date("m");
	$extra_year = ($start_month_num == 1) ? 0 : 1;
	$end_year = $start_year + $num_years-1 + $extra_year;
	if (0) deb("utils.renderUpcomingMonthsSelectList: start_year = $start_year, start_month_num = $start_month_num");
	if (0) deb("utils.renderUpcomingMonthsSelectList: selected_month_num = $selected_month_num, selected_year = $selected_year, selected_date = $selected_date");
	$select_field = '<select name="' . $field_name . '">';
	$none_selected = (!$selected_month_num && !$selected_year) ? 'selected' : '';	
	$select_field .= '<option value="" ' . $none_selected . ' ></option>'; 	
	if (0) deb("utils.renderUpcomingMonthsSelectList: select first line =", $select);
	for($year=$start_year; $year<=$end_year; $year++) {
		if (0) deb("utils.renderUpcomingMonthsSelectList: year = $year");
		if ($year == $start_year) {
			$months = months($start_month_num, 12);
		} elseif ($year == $end_year) {
			$months = months(1, ($start_month_num-1)%12);			
		} else {
			$months = months(1, 12);			
		}	
		if (0) deb("utils.renderUpcomingMonthsSelectList: year = $year, months = ", $months);
		foreach($months as $i=>$month) {
			if (0) deb("utils.renderUpcomingMonthsSelectList: selected_month_num = $selected_month_num, month['number_zero_padded'] = {$month['number_zero_padded']}, selected_year = $selected_year, year = $year");
			$selected = ($month['number_zero_padded'] == $selected_month_num && $year == $selected_year) ? 'selected' : '';
			if (0) deb("utils.renderMonthsSelectList(): selected = ", $selected);	
			$select_field .= '<option value="' . $year . '-' . $month['number_zero_padded'] . '" ' . $selected . '>' . $month['full_name'] . ' ' . $year . '</option>';
		}
	}
	$select_field .= '</select>';
	if (0) deb("utils.renderUpcomingMonthsSelectList: final select =", $select_field);
	return $select_field;
}

function renderYearsSelectList($num_years=3, $field_name="years") {
	$first_year = date("Y");
	$select = '<select name=' . $field_name . '>';
	for($year=$first_year; $year<$first_year+$num_years; $year++) {
		$selected = ($year == $first_year) ? 'selected' : '';
		if (0) deb("utils.renderYearsSelectList(): year = $year");	
		$select .= '<option value="' . $year . '" ' . $selected . '>' . $year . '</option>';
	}
	$select .= '</select>';	
	if (0) deb("utils.renderYearsSelectList(): select = ", $select);	
}

// An HTML select field for the months of the year, with the selected one highlighted
function renderMonthsSelectList($selected_month_num=NULL, $field_name="months") {
	$select = '<select name="' . $field_name . '">';
	$months = months();
	foreach($months as $i=>$month) {
		$selected = ($month['number_zero_padded'] == $selected_month_num) ? 'selected' : '';
		if (0) deb("utils.renderMonthsSelectList(): selected = ", $selected);	
		$select .= '<option value="' . $month['number_zero_padded'] . '" ' . $selected . '>' . $month['full_name'] . '</option>';
	}
	$select .= '</select>';
	
	return $select;
}


function formatted_date($date, $format) {
	$date_ob = date_create($date);
	return date_format($date_ob, $format);
}

function zeroPad($int, $length) {
	$str = (string)$int;
	for (; $length - strlen($str) > 0; ) { 
		$str = "0" . $str;
	}
	return $str;
}

function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
// DATE-RELATED FUNCTIONS - end ----------------------------------------------------


function autoIncrementId($table) { 
	// Returns the highest id in the specified table + 1
	return sqlSelect("max(id)+1 as id", $table, "", "", (0), "autoIncrementId($table)")[0]['id'];
}


function meal_date_sort($a, $b) {
	if (0) deb("utils.meal_date_sort: arg a = ", $a);
	if (0) deb("utils.meal_date_sort: arg b = ", $b);
    $diff = strtotime($a['meal_date']) - strtotime($b['meal_date']); 
	if (0) deb("utils.meal_date_sort: diff = ", $diff);
	return $diff;
}

function surveyIsClosed() {
	$is_closed = (DEADLINE < time() ? TRUE : FALSE); 
	$deadline = DEADLINE;
	$time = time();
	if (0) deb("utils:surveyIsClosed(): is_closed = $is_closed, DEADLINE = $deadline, time() = $time");
	return DEADLINE < time();
}

function determineUserStatus() {
	if (isset($_GET['admin']) || isset($_GET['a'])) { 
		promptForAdminPassword();
	}
	if (isset($_POST['password'])) { 
		if ($_POST['password'] == 'a') {  
			if (0) deb("utils.determineUserStatus: Should be setting admin cookie");
			$_SESSION['access_type'] = 'admin';
			// setcookie("admin", TRUE, time()+86400,"/");
		} else {
			$_SESSION['access_type'] = 'guest';
			// setcookie("admin", FALSE, time()+86400,"/");
			print "Wrong password for 'admin' access.  You're in this session as a 'guest'.";
		}
	}
	if (0) deb("utils.determineUserStatus: _GET = ", $_GET);
	if (0) deb("utils.determineUserStatus: _POST = ", $_POST);
	if (0) deb("utils.determineUserStatus: _COOKIE = ", $_COOKIE);
	if (0) deb("utils.determineUserStatus: _SESSION['access_type'] = ", $_SESSION['access_type']);
	if (0) deb("utils.determineUserStatus: _SESSION = ", $_SESSION);
}

function promptForAdminPassword() {
	$_SESSION['access_type'] = 'guest';
	print <<<EOHTML
		<form method="post" action="{$_SERVER['PHP_SELF']}">
			<p>For administrator access, enter password:</p>
			<input type="password" name="password">
			<input type="submit" value="go">
		</form>
EOHTML;
}

function userIsAdmin() {
	// return (isset($_COOKIE["admin"]) && $_COOKIE["admin"] == TRUE ? 1 : 0);
	return (isset($_SESSION['access_type']) && $_SESSION['access_type'] == "admin" ? 1 : 0);
}

/**
 * Get an element from an array, with a backup.
 */
function array_get($array, $key, $default=NULL) {
	if (is_array($array) && !empty($array) && isset($array[$key])) {
		return $array[$key];
	}

	return $default;
}

/**
 * Get the upcoming season's ID.
 */
function get_season_id() {
	$start_date = 'March 1, 2018, 12pm';
	$start = new DateTime($start_date);

	$now = new DateTime();
	$diff = date_diff($start, $now);

	$out = ($diff->y * 3) + floor($diff->m / 3);
	return $out;
}

/* 
 * Get the season's name from database based on SEASON_ID
 */
function get_season_name_from_db() {
	global $dbh;
	$SEASONS_TABLE = SEASONS_TABLE;
	$season_id = SEASON_ID;
	$sql = <<<EOSQL
		SELECT name FROM {$SEASONS_TABLE} WHERE id = {$season_id};
EOSQL;
	$season_name = array();
	foreach($dbh->query($sql) as $row) {
		$season_name[] = $row['name'];
		break;
	}
	if (0) deb("	Utils: Season name from DB:", $season_name);
	return $season_name[0];
}

/**
 * Get the months contained in the current season.
 *
 * @return array list of month names contained in the requested season.
 */
function get_current_season_months() {
	if (0) deb("utils.get_current_season_months: SEASON_TYPE = ", SEASON_TYPE);
	switch(SEASON_TYPE) {
		case "SPRING":
			return [
				4=>'April',
				5=>'May',
				6=>'June',
			];
			break;		
		case "SUMMER":
			return [	
				7=>'July',
				8=>'August',
				9=>'September',
			];
			break;
		case "FALL":
			return [
				10=>'October',
				11=>'November',
				12=>'December',
			];
			break;
		case "WINTER":
			return [
				1=>'January',
				2=>'February',
				3=>'March',
			];
			break;
		case 'test':
			return [
				1=>'January',
			];
			break;
	}
}

/**
 * #!# WTF... why is this off by 2 months?
 * Is that for planning purposes? That shouldn't be done here...
 */
function get_season_name($date=NULL) {
	if (is_null($date)) {
		$date = time();
	}
	$month = date('n', $date);

	switch($month) {
		case 3:
		case 4:
		case 5:
			return SPRING;

		case 6:
		case 7:
		case 8:
			return SPRING;

		case 9:
		case 10:
		case 11:
			return FALL;

		case 12:
		case 1:
		case 2:
			return WINTER;

	}
}

/**
 * Add the easter date to the holidates array.
 */
function add_easter($holidays) {
	// add easter, which floats between march and april
	$easter_month = date('n', easter_date(SEASON_START_YEAR));
	$easter_day = date('j', easter_date(SEASON_START_YEAR));
	$holidays[$easter_month][] = $easter_day;

	return $holidays;
}

/*
 * Get the list of all holidays.
 * @return associative array where the keys are the months, and the values are
 *     dates in the months.
 */
function get_holidays() {
	$holidays = [
		1 => [0],
		7 => [4],
		10 => [31],
		12 => [24,25, 31],
	];

	$holidays = add_easter($holidays);

	// add memorial day
	$mem_day = date('j', strtotime('last monday of May, ' . SEASON_START_YEAR));
	// sunday, day before
	$holidays[5][] = ($mem_day - 1);
	// monday, memorial day
	$holidays[5][] = $mem_day; 

	// sunday before labor day
	// if last day of aug is sunday, then next day is labor day... skip
	$last_aug = date('D', strtotime('last day of August, ' . SEASON_START_YEAR));
	if ($last_aug == 'Sun') {
		$holidays[8][] = 31;
	}

	// labor day
	$labor_day = date('j', strtotime('first monday of September, ' . SEASON_START_YEAR));
	// if the Sunday before is in Sept, then skip it
	if ($labor_day > 1) {
		$holidays[9][] = ($labor_day - 1);
	}
	$holidays[9][] = $labor_day;

	// thanksgiving
	$thx_day = date('j', strtotime('fourth thursday of November, ' . SEASON_START_YEAR));
	$holidays[11][] = $thx_day;
	$last_sunday = date('j', strtotime('last sunday of November, ' . SEASON_START_YEAR));
	if ($last_sunday > $thx_day) {
		$holidays[11][] = $last_sunday;
	}

	ksort($holidays);
	$yr = SEASON_START_YEAR;
	if (0) {deb("Holidays for {$yr}:", $holidays);}
	return $holidays;
}

/**
 * Get the first key from the array
 */
function get_first_associative_key($dict) {
	if (empty($dict)) {
		return NULL;
	}

	// do this in 2 steps to avoid errors / warnings
	$tmp = array_keys($dict);
	return array_shift($tmp);
}

/* 
Print debug data to the web page
*/

function deb($label, $data=NULL) {
	$print_data = ($data ? "<pre> " . print_r($data, TRUE) . "</pre>" : '<p> </p>');
	echo <<<EOHTML
<tr>
	<td colspan="4"> <br>{$label}
		{$print_data}
	</td>
</tr>
EOHTML;
}

/* 
Print debug data to the terminal
*/

function debt($label, $data=NULL) {
	$print_data = print_r($data, TRUE);
	print "
	" . $label . "
	";
	if ($data) print $print_data . "
	";
}

/*
Print a headline for a page
*/
function renderHeadline($text, $breadcrumbs_str="") {
	if (0) deb ("utils.renderHeadline(): breadcrumbs_str =", $breadcrumbs_str);
	
	if ($breadcrumbs_str) {
		$breadcrumbs = 'Go back to:';
		$breadcrumbs_arr = explode(';', $breadcrumbs_str);
		foreach($breadcrumbs_arr as $i=>$breadcrumb) {
			if (0) deb ("utils.renderHeadline(): breadcrumb =", $breadcrumb);
			$name = explode(',', $breadcrumb)[0];
			if (0) deb ("utils.renderHeadline(): name =", $name);
			$url = explode(',', $breadcrumb)[1];
			if (0) deb ("utils.renderHeadline(): url =", $url);
			$breadcrumbs .= '&nbsp;&nbsp;<a style="font-size:10pt;" href="'. $url . '">' . $name . '</a>'; 
		}
		$breadcrumbs = '<tr><td colspan="2" style="text-align:right";>' . $breadcrumbs . '</td></tr>';
	}
	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');
	$instance = INSTANCE;
	$database = DATABASE;
	$color = '"color:red"';
	$instance_notice = ($instance ? "<p style={$color}><strong>You're looking at the {$instance} instance.  Database is {$database}.</strong></p>" : "");
	$end_session_label = "End this session and start a new one.";
	$sign_in_as_guest_label = "Sign in as a guest";
	$admin_notice = (userIsAdmin() ? "<div style={$color}>
		<p><strong>You're signed into this session as an admin.</strong></p>
		</div>"
		: "");
	// if (!$at_home) $home_link = '<a style="font-size:10pt; font-weight:bold" href="index.php">Back to Home</a>';

	return <<<EOHTML
	{$instance_notice}
	{$admin_notice}
	<table>
		{$breadcrumbs}
		<tr>
		<td><img src={$community_logo}></td>
		<td class="headline">{$text}</td>
	</tr></table>
EOHTML;
}

function getJobs() {
	$jobs_table = SURVEY_JOB_TABLE;
	$season_id = SEASON_ID;
	$select = "j.description, j.id as job_id, j.instances, 0 as signups";
	$from = "{$jobs_table} as j";
	$where = "j.season_id = {$season_id}";
	$order_by = "j.display_order";
	$jobs = sqlSelect($select, $from, $where, $order_by, (0));
	if (0) deb ("utils.getJobs(): jobs =", $jobs);
	return $jobs;
}

function getJobSignups() {
	$person_table = AUTH_USER_TABLE;
	$offers_table = OFFERS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$season_id = SEASON_ID;
	$select = "p.id as person_id, p.first_name, p.last_name, o.instances, j.id as job_id, j.description";
	$from = "{$person_table} as p, {$offers_table} as o, {$jobs_table} as j";
	$where = "p.id = o.worker_id and o.job_id = j.id and j.season_id = {$season_id}";
	$order_by = "p.first_name, p.last_name, j.display_order";
	$signups = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("utils.getJobSignups(): signups =", $signups);
	return $signups;
}

function getJobAssignments($meal_id=NULL, $job_id=NULL, $worker_id=NULL) {
	// list the assignments for the current season, optionally scoped by meal, job, and/or worker
	$season_id = SEASON_ID;
	$meal_id_clause = ($meal_id ? "
		and m.id = '{$meal_id}'" : "");
	$job_id_clause = ($job_id ? "
		and j.id = '{$job_id} '" : "");
	$worker_id_clause = ($worker_id ? "
		and w.id = '{$worker_id} '" : "");
	$select = "w.first_name || ' ' || w.last_name as worker_name, 
		w.first_name, 
		w.last_name, a.*, 
		m.date as meal_date, 
		s.job_id, 
		j.description";
	$from = AUTH_USER_TABLE . " as w, 
		" . ASSIGNMENTS_TABLE . " as a, 
		" . MEALS_TABLE . " as m, 
		" . SCHEDULE_SHIFTS_TABLE . " as s, 
		" . SURVEY_JOB_TABLE . " as j";
	$where = "w.id = a.worker_id 
		and a.shift_id = s.id 
		and s.job_id = j.id 
		and j.season_id = {$season_id} 
		and s.meal_id = m.id {$meal_id_clause} {$job_id_clause} {$worker_id_clause}
		and a.scheduler_run_id = " . scheduler_run()['id'] . " {$job_id_clause}";
	$order_by = "m.date, j.display_order";
	$assignments = sqlSelect($select, $from, $where, $order_by, (0), "getJobAssignments()");
	if (0) deb("utils.getJobAssignments(): assignments:", $assignments);
	return $assignments;
}

function getResponders() {
	$responder_ids = array();
	$signups_table = OFFERS_TABLE;
	$season_id = SEASON_ID;
	$where = "id IN (select worker_id from {$signups_table} WHERE season_id = {$season_id})";
	$responders =  new PeopleList($where);
	$responders_list = $responders->people;
	if ($responders_list) {
		foreach($responders_list as $index=>$person) {
			if (0) deb("utils.getNonResponders: person[id] =", $person['id']); 
			$responder_ids[] = $person['id'];
		}
	}
	if (0) deb("utils.getNonResponders: responder_ids =", $responder_ids); 
	return $responder_ids; 
}

function getNonResponders() {
	$responder_ids = getResponders();
	if (0) deb("utils.getNonResponders: responder_ids =", $responder_ids);
	$everybody = new PeopleList("");
	$everybody_list = $everybody->people;
	foreach($everybody_list as $index=>$person) {
		if (0) deb("utils.getNonResponders: person[id] =", $person['id']); 
		$everybody_ids[] = $person['id'];
		if (!(in_array($person['id'], $responder_ids))){
			$non_responder_ids[] = $person['id'];
			$non_responder_names[] = $person['first_name'] . " " . $person['last_name'];
		}
	}
	if (0) deb("utils.getNonResponders: everybody_ids =", $everybody_ids);
	if (0) deb("utils.getNonResponders: non_responder_ids =", $non_responder_ids);
	if (0) deb("utils.getNonResponders: non_responder_names =", $non_responder_names);
	
	return $non_responder_names;
}

// Get descriptions of all jobs for the specified season from the database.
// This function coexists uneasily with the "defines" in constants.inc, which also specify the job ids as global constants.
function getJobsFromDB($season_id) {
	$jobs_table = SURVEY_JOB_TABLE;
	$select = "*";
	$from = $jobs_table;
	$where = "season_id = " . $season_id;
	$order_by = "display_order";
	$out = sqlSelect($select, $from, $where, $order_by);
	if (0) debt("utils.getJobsFromDB: jobs", $out);
	return $out;
}

function renderJobSignups($headline=NULL, $include_details) {
	$jobs = getJobs();
	if (0) deb("report.php: renderJobSignups(): getJobs() returns:", $jobs);
	$signups = getJobSignups();
	if (0) deb("report.php: renderJobSignups(): getJobSignups() returns:", $signups);

	// Make header rows for the table
	$job_names_header = '<tr style="text-align:center;"><th></th>';
	$data_types_header = '<tr style="text-align:center;"><th></th>';
	foreach($jobs as $index=>$job) {		
		if (0) deb ("report.renderJobSignups(): job['description']) = {$job['description']}");
		$job_names_header .= '<th colspan="' . (UserIsAdmin() && $include_details ? 3 : 1) . '" style="text-align:center;">' . $job['description'] . "</th>";
		$data_types_header .= '<th style="text-align:center;">signups</th>';
		if (userIsAdmin() && $include_details) {
				$data_types_header .= '<th style="text-align:center;">assigned</th>';
				$data_types_header .= '<th style="text-align:center;">available</th>';
			}
	}
	$job_names_header .= "</tr>";
	$data_types_header .= "</tr>";
	if ($include_details==FALSE) $data_types_header = NULL;
	if (0) deb ("report.renderJobSignups(): job_names_header =", $job_names_header); 
	
	// Make data rows
	$responders_count = 0;
	$prev_person_id = 0;
	$signup_rows = '';
	foreach($signups as $index=>$signup) {
		// If this is a new person, start a new row & print name in first column
		if ($signup['person_id'] != $prev_person_id) {
			if ($prev_person_id != "") $signup_rows .= "</tr>";
			$signup_rows .= "
			<tr>
				<td>{$signup['first_name']} {$signup['last_name']}</td>";
			$prev_person_id = $signup['person_id'];
			$responders_count++;
		}
		
		if (0) deb ("report.renderJobSignups(): signup['job_id']) = {$signup['job_id']}");
		if (0) deb ("report.renderJobSignups(): signup) =", $signup);
		if (0) deb ("report.renderJobSignups(): availability_index) = $availability_index");
			
		// Render the number of times this person will do this job
		if (0) deb("report.renderJobSignups(): signup['person_id'] =? prev_person_id) AFTER =", $signup['person_id'] . "=?" . $prev_person_id);
		$person_signups_for_job = ($signup['instances'] > 0 ? $signup['instances'] : '');
		$signup_rows .= "
			<td>{$person_signups_for_job}</td>";

		// Increment the total number of signups for this job
		if (0) deb ("report.renderJobSignups(): signup['job_id']) =", $signup['job_id']);
		$job = array_search($signup['job_id'], array_column($jobs, 'job_id'));
		$jobs[$job]['signups'] += $signup['instances'];
		if (0) deb ("report.renderJobSignups(): jobs[job]['signups'] =", $jobs[$job]['signups']);

		if (userIsAdmin() && $include_details) {
			// Render the number of times this person is available for this job (signups - assignments)
			$assignments = getJobAssignments(NULL, $signup['job_id'], $signup['person_id']);
			$assignments_count = count($assignments);
			$available_count = $signup['instances'] - $assignments_count;
			$available_count = ($available_count > 0 ? $available_count : '');
			$assignments_count = ($assignments_count > 0 ? $assignments_count : '');
			$available_background = ($available_count != '' ? 'style="background:lightpink;" ' : '');
			$signup_rows .= "
				<td>{$assignments_count}</td>";
			$signup_rows .= "
				<td {$available_background}>{$available_count}</td>";
		}
	}
	$signup_rows .= "</tr>";

	if ($include_details==FALSE) {
		$signup_rows = "";
	}

	// Render a row showing total signups for each job
	$background = ($include_details ? ' style="background:lightgreen;" ' : ' style="background:white;" ');
	$totals_row = "<tr>
		<td {$background}><strong>signups so far</strong></td>";
	foreach($jobs as $index=>$job) {
		$totals_row .= "<td {$background}><strong>{$job['signups']}</strong></td>";
		if (userIsAdmin() && $include_details) $totals_row .= "<td {$background}></td><td {$background}></td>";
	}
	$totals_row .= "</tr>";
	
	// Render a row showing total jobs to fill for each job
	$needed_row = "<tr>
		<td {$background}><strong>jobs to fill</strong></td>";
	foreach($jobs as $index=>$job) {
		$needed_row .= "<td {$background}><strong>{$job['instances']}</strong></td>";
		if (userIsAdmin() && $include_details) $needed_row .= "<td {$background}></td><td {$background}></td>";
	}
	$needed_row .= "</tr>";

	// Render a row showing total signups shortfall for each job
	$shortfall_row = "<tr>
		<td {$background}><strong>signups still needed</strong></td>";
	foreach($jobs as $index=>$job) {
		$shortfall = $job['instances'] - $job['signups'];
		if ($shortfall == 0) $shortfall = '';
		$shortfall_row .= "<td {$background}><strong>{$shortfall}</strong></td>";
		if (userIsAdmin() && $include_details) $shortfall_row .= "<td {$background}></td><td {$background}></td>";
	}
	$shortfall_row .= "</tr>";

	$out = <<<EOHTML
	<h2>{$headline}</h2>
	<table><tr><td style="background:Yellow">
		<table border="1" cellspacing="3">
			<tr>
				{$job_names_header}
				{$data_types_header}
				{$needed_row}
				{$totals_row}
				{$shortfall_row}
				{$signup_rows} 
			</tr>
		</table>
	</td></tr></table>
	{$responders_count} people have responded.
EOHTML;
	if (0) deb ("report.renderJobSignups(): out =", $out);
	return $out;
}

// SQL FUNCTIONS

// Generic SQL SELECT
function sqlSelect($select, $from, $where=NULL, $order_by=NULL, $debs=0, $tag="") {
	global $dbh;
	if ($debs && $tag) $tag = " [$tag]";
	$sql = <<<EOSQL
SELECT {$select} 
FROM {$from} 
EOSQL;
	if ($where) {
		$sql .= <<<EOSQL
		
WHERE {$where}
EOSQL;
	}
	if ($order_by) {
		$sql .= <<<EOSQL
		
ORDER BY {$order_by}
EOSQL;
	}
	if ($debs) deb("utils.sqlSelect(){$tag}: sql:", $sql); 
	$rows = array();
	$found = $dbh->query($sql);
	if ($found) {
		foreach($dbh->query($sql) as $row) {
			// Get rid of the numbered elements that get stuck into these row-arrays,  
			// leaving only named attributes as elements in the rows array
			foreach($row as $key=>$value) {
				if (is_int($key)) unset($row[$key]); 
			}
			$rows[] = $row;
		}
	}
	if ($debs) deb("utils.sqlSelect() {$tag}: rows:", $rows);
	return $rows;
}

// Generic SQL UPDATE
function sqlUpdate($table, $set, $where, $debs=0, $tag="", $do_it=TRUE) {
	global $dbh;
	if ($debs && $tag) $tag = " [$tag]";
	$sql = <<<EOSQL
UPDATE {$table} 
SET {$set}
EOSQL;
	if ($where) {
		$sql .= <<<EOSQL
		
WHERE {$where}
EOSQL;
	}
	if ($debs) deb("utils.sqlUpdate(){$tag}: sql:", $sql); 
	if ($do_it) $rows_affected = $dbh->exec($sql);
	if (!$rows_affected) $rows_affected = 0;
	if ($debs) deb("utils.sqlUpdate() {$tag}: rows_affected: $rows_affected");
	return $rows_affected;
}

// Generic SQL INSERT
function sqlInsert($table, $columns, $values, $debs=0, $tag="", $do_it=TRUE) {
	global $dbh;
	$sql = <<<EOSQL
INSERT INTO {$table} ({$columns})
VALUES ({$values}) 
EOSQL;
	if ($debs) deb("utils.sqlInsert() {$tag}: sql:", $sql);
	if ($do_it) $rows_affected = $dbh->exec($sql);
	if (!$rows_affected) $rows_affected = 0;
	if ($debs) deb("utils.sqlInsert() {$tag}: rows_affected: $rows_affected");
	return $rows_affected;
}

// Generic SQL REPLACE
// REPLACE INTO apparently works with SQLite and MySQL but not PostgreSQL, 
// so would have to rewrite this function for PostgreSQL
function sqlReplace($table, $columns, $values, $debs=0, $tag="", $do_it=TRUE) {
	global $dbh;
	$sql = <<<EOSQL
REPLACE INTO {$table} ({$columns})
VALUES ({$values}); 
EOSQL;
	if ($debs) deb("utils.sqlReplace: sql:", $sql);
	if ($do_it) $rows_affected = $dbh->exec($sql);
	if ($debs) deb("utils.sqlReplace {$tag}: rows_affected = {$rows_affected}");
	return $rows_affected;
}

// Generic SQL DELETE
function sqlDelete($from, $where, $debs=0, $tag="", $do_it=TRUE) {
	global $dbh;
	$sql = <<<EOSQL
		DELETE FROM {$from} 
EOSQL;
	if ($where) {
	$sql .= <<<EOSQL
		WHERE {$where}
EOSQL;
	}
	if ($debs) deb("utils.sqlDelete: sql:", $sql);
	if ($do_it) $rows_affected = $dbh->exec($sql);
	if ($debs) deb("utils.sqlDelete {$tag}: rows_affected = {$rows_affected}");
	return $rows_affected;
}
?>
