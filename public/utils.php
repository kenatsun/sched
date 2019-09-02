<?php

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


// ADMIN LOGIN & DASHBOARD FUNCTIONS ---------------------------------------------------------------

function changeUserStatus() {
	if (0) deb("utils.changeUserStatus: _REQUEST['sign_in_as'] = ", $_REQUEST['sign_in_as']);
	if ($_REQUEST['sign_in_as'] === "admin") { 
		promptForAdminPassword();
	} elseif ($_REQUEST['password'] === ADMIN_PASSWORD) {
		$_SESSION['access_type'] = 'admin';
	} elseif ($_REQUEST['sign_in_as'] === "guest") {
		$_SESSION['access_type'] = 'guest';
	}
	if (0) deb("utils.changeUserStatus: _SESSION['access_type'] = ", $_SESSION['access_type']);
	if (0) deb("utils.changeUserStatus: _SESSION = ", $_SESSION);
}

function promptForAdminPassword() {
	$_SESSION['access_type'] = 'guest';
	print '<div style="color:red; font-weight:bold; animation:blink;">
		<form method="post" action="' . makeURI($_SERVER['PHP_SELF'], "", REQUEST_QUERY_STRING) . '">  
			<p>For administrator access, enter password:</p>
			<input type="password" name="password">
			<input type="submit" value="go">
		</form>
		</div>
';
}

function userIsAdmin() {
	if (0) deb("utils.userIsAdmin: SESSION['access_type']", $_SESSION['access_type']);
	return isset($_SESSION['access_type']) && $_SESSION['access_type'] == "admin"	? 1 : 0;
}

// ADMIN LOGIN FUNCTIONS - end ----------------------------------------------------


// DATE-RELATED FUNCTIONS - start ----------------------------------------------------

// An array containing various formattings of the months of the year
function months($start_month_num=1, $end_month_num=12, $attribute="") {
	$months = array();
	$attributes = array('full_name', 'short_name', 'number_zero_padded', 'number', '');
	if (in_array($attribute, $attributes)) {
		for($m=$start_month_num; $m<=$end_month_num; $m++) {
			if (!$attribute) {
				// return an array of months, each expressed as an array of attributes
				// $months[] = array(); 
				$months[$m]['full_name'] = date('F', mktime(0,0,0, $m, 1));
				$months[$m]['short_name'] = date('M', mktime(0,0,0, $m, 1));
				$months[$m]['number'] = date('n', mktime(0,0,0, $m, 1));
				$months[$m]['number_zero_padded'] = date('m', mktime(0,0,0, $m, 1));
				} 
			else {
				// return an array of months, each just expressed as the requested attribute
				switch ($attribute) { 
					case 'full_name': $months[$m] = date('F', mktime(0,0,0, $m, 1)); break;
					case 'short_name': $months[$m] = date('M', mktime(0,0,0, $m, 1)); break;
					case 'number': $months[$m] = date('n', mktime(0,0,0, $m, 1)); break;
					case 'number_zero_padded': $months[$m] = date('m', mktime(0,0,0, $m, 1)); break;
				}
			}
		}
	}
	return $months;
}

// An array containing various formattings of days of the week
function days_of_week($dows_arg="", $attribute="") {
	if (!$dows_arg) $dows_arg="0 1 2 3 4 5 6"; // Default is to return all days of the week
	$dows_to_do = explode(" ", $dows_arg);
	$dows = array();
	$attributes = array('full_name', 'full_name_uppercase', 'short_name', 'number', '');
	if (in_array($attribute, $attributes)) {
		if (0) deb("utils.days_of_week(): dows_to_do =", $dows_to_do);
		foreach($dows_to_do as $i=>$dow_to_do) {
			if (is_numeric($dow_to_do)) {
				$increment = 2 + (int)$dow_to_do;
				$dow = date_create("2018/12/$increment"); // 2018/12/02 is known to be a Sunday
				// $dow = date_add($sunday, "P".$i."D");
				if (!$attribute) {
					// return an array of days, each expressed as an array of attributes
					$today = array(); 
					$today['full_name'] = date_format($dow, 'l');
					$today['full_name_uppercase'] = strtoupper(date_format($dow, 'l'));
					$today['short_name'] = date_format($dow, 'D');
					$today['number'] = (int)date_format($dow, 'w');
					$dows[$dow_to_do] = $today;
				} 
				else {
					// return an array of days, each just expressed as the requested attribute
					switch ($attribute) { 
						case 'full_name': $dows[$dow_to_do] = date_format($dow, 'l'); break;
						case 'full_name_uppercase': $dows[$dow_to_do] = strtoupper(date_format($dow, 'l')); break;
						case 'short_name': $dows[$dow_to_do] = date_format($dow, 'D'); break;
						case 'number': $dows[$dow_to_do] = (int)date_format($dow, 'w'); break;
					}
				}
			}
		} 
	}
	if (0) deb("utils.days_of_week(): dows =", $dows);
	return $dows;
}

// An HTML select field for the months of the next num_years years,
// starting with the current month of the current year,
// with the selected month shown as the current value
function renderUpcomingMonthsSelectList($field_name="months", $selected_date=NULL, $num_years=1) {
	if (0) deb("utils.renderUpcomingMonthsSelectList: start selected_date = $selected_date");
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
	if ($selected_date && strtotime($selected_date) < strtotime("now")) {
		if (0) deb("utils.renderUpcomingMonthsSelectList: selected_date = " . $selected_date);
		$select_field = date("F Y", strtotime($selected_date));
		$select_field .= '<input type="hidden" name="' . $field_name . '" value="' . $selected_year. '-' .$selected_month_num . '">';
	}
	else {
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
	}
	if (0) deb("utils.renderUpcomingMonthsSelectList: final select =", $select_field);
	return $select_field;
}

function renderSendEmailControl($name) {
	if(userIsAdmin()) {
		$send_email = '<div align="right"><input type="checkbox" name="send_email" value="yes">Email summary to ' . $name . '?</div><br>';
	} else {
		$send_email = '<div align="right"><input type="hidden" name="send_email" value="default">';
	}
	return $send_email; 
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


/**
 * Get the months contained in the current season.
 *
 * @return array list of month names contained in the requested season.
 */
function get_current_season_months() {
	$season = sqlSelect("*", SEASONS_TABLE, "id = " . SEASON_ID, "")[0];
	$start_month_num = date_format(date_create($season['start_date']), "n");
	$end_month_num = date_format(date_create($season['end_date']), "n");
	$season_months = array();
	$months = months($start_month_num, $end_month_num);
	foreach($months as $i=>$month) {
		$season_months[$month['number']] = $month['full_name'];
	}
	if (0) deb("utils.get_current_season_months: season_months = ", $season_months);
	return $season_months; 
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

// DATE-RELATED FUNCTIONS - end ----------------------------------------------------


function renderBlockInShowHideWrapper($block, $section_title, $html_before="", $html_after="", $display="none") {
$id = str_replace(' ', '_', $section_title);
$id = str_replace("'", "", $id);
$link_id = $id . "_link";
$div_id = $id . "_div";
$tooltip_id = $id . "_tooltip";
$show_hide = ($display == "none") ? "show" : "hide";
$link = '
		<a 
			style="text-decoration: none; color: black;"
			id="' . $link_id . '" 
			onclick="showHide(\'' . $div_id . '\', \'' . $link_id . '\');" 
			href="#' . $link_id . '"
			>
				<img 
					src="display/images/triangle_pointing_right.png" 
					alt="click to show" 
					height="16" 
					width="16"
				>
				<span style="font-size: 9pt; ">click to ' . $show_hide . '</span>
		</a>'
;
// This version of the above includes a tooltip:
// $link = '
	// <div class="tooltip">
		// <a 
			// id="' . $link_id . '" 
			// onclick="showHide(\'' . $div_id . '\', \'' . $link_id . '\');" 
			// href="#' . $link_id . '"
			// >
				// <img 
					// src="display/images/triangle_pointing_right.png" 
					// alt="click to show" 
					// height="16" 
					// width="16"
				// >
				// <span class="tooltiptext" id="' . $tooltip_id . '">Show ' . $section_title . '</span>
		// </a>
	// </div>'
// ;

$div = '
	<div id="' . $div_id . '" style="display:' . $display . ';">' . 
		$block . '
	</div>';
	
return $html_before . $link . $html_after . $div;
}

function renderBullpen() {
	$td_style = ' style="border:1px shadow;"'; 
	$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . SEASON_ID, "display_order asc");
	$num_jobs = count($jobs);
	$title_row = '<tr><td style="text-align:center; font-weight:bold; font-size:12pt; border:1px shadow; background-color:' . HEADER_COLOR . ';" colspan="' . $num_jobs . '">The Bullpen</td></tr>';
	$header_row = '<tr>';
	$bullpen_row = '<tr>';
	foreach ($jobs as $job) {
		$num_available = 0;
		$select = "*";
		$from = "open_offers_count";
		$where = "job_id = {$job['id']}
			and open_offers_count > 0";
		$order_by = "open_offers_count desc, worker_name asc";
		$workers = sqlSelect($select, $from, $where, $order_by, (0), "utils:renderBullpen()"); 
		$bullpen_for_job = "";
		foreach ($workers as $worker) {
			if ($bullpen_for_job) $bullpen_for_job .= "<br>";
			$bullpen_for_job .= $worker['worker_name'] . " (" . $worker['open_offers_count'] . ")";
			$num_available += $worker['open_offers_count'];
		}
		if (!$num_available) $num_available = "0"; 
		if (0) deb("utils.renderBullpen(): job = ", $job);
		$header_row .= '<th ' . $td_style . '>' . $job['description'] . ' (' . $num_available . ')</th>'; 
		if (0) deb("utils.renderBullpen(): header_row = ", $header_row);
		$bullpen_row .= '<td ' . $td_style . '>' . $bullpen_for_job . '</td>'; 
	}
	$bullpen_row .= '</tr>';
	$header_row .= '</tr>';
	// return '<table style="width:75%; border-collapse:collapse;" >' . $title_row . $header_row . $bullpen_row . '</table>';
	return '<table style="width:75%;" border="1">' . $title_row . $header_row . $bullpen_row . '</table>';
}


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


/**
 * Get an element from an array, with a backup.
 */
function array_get($array, $key, $default=NULL) {
	if (is_array($array) && !empty($array) && isset($array[$key])) {
		return $array[$key];
	}

	return $default;
}

// Get the current season or a specified attribute of it from the database
function getSeason($attribute="") {
	if ($attribute) {
		return sqlSelect($attribute, "seasons", "id = " . SEASON_ID, "", (0), "utils.getSeason('id')")[0][$attribute];
	} else {
		return sqlSelect("*", "seasons", "id = " . SEASON_ID, "", (0), "utils.getSeason('id')")[0];
	}
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
	if (0) deb("utils.get_season_name_from_db(): Season name = $season_name");
	return $season_name[0];
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
Print debug data to the console
*/

function deb($label, $data=NULL) {
	$print_data = $data ? "\n" . print_r($data, TRUE) : "";
	console_log("*****\n" . $label . $print_data . "\n");
}

function console_log($output, $with_script_tags = true) {
	// From https://stackify.com/how-to-log-to-console-in-php/
  $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
	if ($with_script_tags) {
		$js_code = '<script>' . $js_code . '</script>';
	}
	echo $js_code; 
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


// Render a link to another MO page
function renderLink($text, $href) {
	$dir = PUBLIC_DIR;
	$link = '<p class="summary_report"><a href="'. $href . '">' . $text . '</a></p>';
	return $link;
}


function makeURI($url, $crumbs="", $other_queries="", $anchor="") {
	$uri = $url . "?";
	if ($crumbs) $uri .= "breadcrumbs=" . $crumbs;
	if ($crumbs && $other_queries) $uri .= "&";
	if ($other_queries) $uri .= $other_queries;
	if ($anchor) $uri .= "#" . $anchor;
	// $uri .= "#";
	// $uri = urlencode($uri);
	if (0) deb("utils.phpmakeURI(): crumbs = $crumbs"); 
	if (0) deb("utils.phpmakeURI(): other_queries = $other_queries"); 
	if (0) deb("utils.phpmakeURI(): uri = $uri"); 
	return $uri;
}

function renderToolsList($tools, $subhead=null) {
	// Render the page components for each tool in a step
	$body = $subhead;
	foreach ($tools as $tool) { 
		if (0) deb("utils.renderToolsList(): tool = ", $tool);
		if (0) deb("utils.renderToolsList(): URI = ", makeURI($tool['href'], NEXT_CRUMBS_IDS, $tool['query_string']));
		$body .= '<p style="margin-left:2em;"><a href="' . makeURI($tool['href'], NEXT_CRUMBS_IDS, $tool['query_string']) . '">' . $tool['name'] . '</a></p>';
	}
	return $body;
} 

function exportSurveyAnnouncementCSV($season, $filename) { 

	$columns = array();
	$columns[] = array("sql"=>"w.first_name || ' ' || w.last_name", "colname"=>"worker_name");
	$columns[] = array("sql"=>"w.unit", "colname"=>"unit");
	$columns[] = array("sql"=>"s.name", "colname"=>"season_name");
	$columns[] = array("sql"=>"s.name_without_year", "colname"=>"season_name_without_year");
	$columns[] = array("sql"=>"s.start_date", "colname"=>"season_start_date");
	$columns[] = array("sql"=>"s.end_date", "colname"=>"season_end_date");
	$columns[] = array("sql"=>"s.survey_opening_date", "colname"=>"survey_opening_date");
	$columns[] = array("sql"=>"s.survey_closing_date", "colname"=>"survey_closing_date");
	$columns[] = array("sql"=>"s.scheduling_start_date", "colname"=>"scheduling_start_date");
	$columns[] = array("sql"=>"s.change_request_end_date", "colname"=>"change_request_end_date");
	$columns[] = array("sql"=>"s.scheduling_end_date", "colname"=>"scheduling_end_date");
	$columns[] = array("sql"=>"", "colname"=>"start_month");
	$columns[] = array("sql"=>"", "colname"=>"end_month");
	$columns[] = array("sql"=>"", "colname"=>"start_date");
	$columns[] = array("sql"=>"", "colname"=>"end_date");
	$columns[] = array("sql"=>"", "colname"=>"survey_closing_long");
	$columns[] = array("sql"=>"", "colname"=>"survey_opening");
	$columns[] = array("sql"=>"", "colname"=>"survey_closing");
	$columns[] = array("sql"=>"", "colname"=>"scheduling_start");
	$columns[] = array("sql"=>"", "colname"=>"change_request_end");
	$columns[] = array("sql"=>"", "colname"=>"scheduling_end");

	if (0) deb("season_utils.exportSurveyAnnouncementCSV(): columns =", $columns);
	$fputcsv_header = array();
	foreach($columns as $column) {
		if ($column['sql']) {
			if ($select) $select .= ", 
			";
			$select .= $column['sql'] . " as " . $column['colname'];
		}
		$fputcsv_header[] = $column['colname'];
	} 	
	$from = AUTH_USER_TABLE . " as w, " . SEASONS_TABLE . " as s, " . SEASON_WORKERS_TABLE . " as sw";
	$where = "w.id = sw.worker_id and sw.season_id = s.id and s.id = " . $season['id'];
	$order_by = "cast(unit as integer), first_name, last_name";
	$workers = sqlSelect($select, $from, $where, $order_by, (0), "season_utils.exportSurveyAnnouncementCSV()");
	
	$file = fopen($filename,"w");
	fputcsv($file, $fputcsv_header, "\t");
	foreach ($workers as $i=>$worker) { 
		$workers[$i]['start_month'] = date_format(date_create($worker['season_start_date']), "F");
		$workers[$i]['end_month'] = date_format(date_create($worker['season_end_date']), "F");
		$workers[$i]['start_date'] = date_format(date_create($worker['season_start_date']), "M j");
		$workers[$i]['end_date'] = date_format(date_create($worker['season_end_date']), "M j");
		$workers[$i]['survey_closing_long'] = date_format(date_create($worker['survey_closing_date']), "l, F j");
		$workers[$i]['survey_opening'] = date_format(date_create($worker['survey_opening_date']), "M j");
		$workers[$i]['survey_closing'] = date_format(date_create($worker['survey_closing_date']), "M j");
		$workers[$i]['scheduling_start'] = date_format(date_create($worker['scheduling_start_date']), "M j");
		$workers[$i]['change_request_end'] = date_format(date_create($worker['change_request_end_date']), "M j");
		$workers[$i]['scheduling_end'] = date_format(date_create($worker['scheduling_end_date']), "M j");
		fputcsv($file, $workers[$i], "\t");
		$out_rows .= implode("\t", $worker) . "\n";
	}
	if (0) deb("season_utils.exportSurveyAnnouncementCSV(): out =", $out);
	if (0) deb("season_utils.exportSurveyAnnouncementCSV(): workers =", $workers);
	fclose($file); 	
}


function getJobs() {
	$jobs_table = SURVEY_JOB_TABLE;
	$season_id = SEASON_ID;
	$select = "j.description, j.id as job_id, j.instances, j.workers_per_shift, 0 as signups";
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


function seasonWorkerId($worker_id, $season_id) {
	return sqlSelect("*", SEASON_WORKERS_TABLE, "worker_id = " . $worker_id . " and season_id = " . $season_id, "", (0))[0]['id'];
}


function seasonLiaisonId($worker_id, $season_id) {
	return sqlSelect("*", SEASON_LIAISONS_TABLE, "worker_id = " . $worker_id . " and season_id = " . $season_id)[0]['id'];
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
	// $responder_ids = array();
	// $signups_table = OFFERS_TABLE;
	$season_id = SEASON_ID;
	$season_workers_table = SEASON_WORKERS_TABLE;
	$responders = sqlSelect("worker_id as id", SEASON_WORKERS_TABLE, "season_id = {$season_id} and first_response_timestamp is not null", "", (0));
	foreach($responders as $responder) $responder_ids[] = $responder['id'];
	if (0) deb("utils.getResponders: responder_ids =", $responder_ids); 
	return $responder_ids; 
}


function getNonResponders() {
	$non_responders = sqlSelect("worker_id as id", SEASON_WORKERS_TABLE, "season_id = {$season_id} and first_response_timestamp is null", "", (0));
	foreach($non_responders as $non_responder) $non_responder_ids[] = $non_responder['id'];
	if (0) deb("utils.getNonResponders: responder_ids =", $non_responder_ids); 
	return $non_responder_ids; 
}


function renderScoreboard($section_title=NULL) {
	$jobs = getJobs();
	if (0) deb("index.php: renderScoreboard(): getJobs():", $jobs);

	$person_table = AUTH_USER_TABLE;
	$offers_table = OFFERS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$season_id = SEASON_ID;
	$select = "p.id as person_id, 
		p.first_name, 
		p.last_name, 
		o.instances, 
		j.id as job_id, 
		j.description";
	$from = "{$person_table} as p, 
		{$offers_table} as o, 
		{$jobs_table} as j";
	$where = "p.id = o.worker_id 
		and o.job_id = j.id 
		and j.season_id = {$season_id}";
	$order_by = "p.first_name, p.last_name, j.display_order";
	$signups = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("index.renderScoreboard(): signups =", $signups);

	// $signups = getJobSignups();
	// if (0) deb("report.renderScoreboard(): getJobSignups() returns:", $signups);

	// Make header rows for the table
	$job_names_header = '<tr style="text-align:center;"><th></th>';
	$data_types_header = '<tr style="text-align:center;"><th></th>';
	foreach($jobs as $index=>$job) {		
		if (0) deb ("report.renderScoreboard(): job['description']) = {$job['description']}");
		$job_names_header .= '<th colspan="1" style="text-align:center;">' . $job['description'] . "</th>";
		$data_types_header .= '<th style="text-align:center;">signups</th>';
	}
	$job_names_header .= "</tr>";
	$data_types_header .= "</tr>";
	if (0) deb ("index.renderScoreboard(): job_names_header =", $job_names_header); 
	
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
		
		if (0) deb ("index.renderScoreboard(): signup['job_id']) = {$signup['job_id']}");
		if (0) deb ("index.renderScoreboard(): signup) =", $signup);
		if (0) deb ("index.renderScoreboard(): availability_index) = $availability_index");
			
		// Render the number of times this person will do this job
		if (0) deb("index.renderScoreboard(): signup['person_id'] =? prev_person_id) AFTER =", $signup['person_id'] . "=?" . $prev_person_id);
		$person_signups_for_job = ($signup['instances'] > 0 ? $signup['instances'] : '');
		$signup_rows .= "
			<td>{$person_signups_for_job}</td>";

		// Increment the total number of signups for this job
		if (0) deb ("index.renderScoreboard(): signup['job_id']) =", $signup['job_id']);
		$job = array_search($signup['job_id'], array_column($jobs, 'job_id'));
		$jobs[$job]['signups'] += $signup['instances'];
		if (0) deb ("index.renderScoreboard(): jobs[job]['signups'] =", $jobs[$job]['signups']);

	}
	$signup_rows .= "</tr>";

	$meals_in_season = sqlSelect("count(id) as id", MEALS_TABLE, "skip_indicator = 0 and season_id = " . SEASON_ID, (0))[0]['id'];
	// Render a row showing total jobs to fill for each job
	if (0) deb("utils.renderScoreboard(): meals_in_season = ", $meals_in_season);
	$needed_row = "<tr>
		<td {$background}><strong>jobs to fill</strong></td>";
	foreach($jobs as $index=>$job) {
		if (0) deb("utils.renderScoreboard(): job['instances'] = ", $job['instances']);
		$shifts_count = $meals_in_season * $job['workers_per_shift'];
		$needed_row .= "<td {$background}><strong>" . $shifts_count . "</strong></td>";
	}
	$needed_row .= "</tr>";

	// Render a row showing total signups for each job
	$background = ' style="background:white;" ';
	$totals_row = "<tr>
		<td {$background}><strong>signups so far</strong></td>";
	foreach($jobs as $index=>$job) {
		$totals_row .= "<td {$background}><strong>{$job['signups']}</strong></td>";
	}
	$totals_row .= "</tr>";
	
	// Render a row showing total signups shortfall for each job
	$shortfall_row = "<tr>
		<td {$background}><strong>signups still needed</strong></td>";
	foreach($jobs as $index=>$job) {
		$shifts_count = $meals_in_season * $job['workers_per_shift'];
		$shortfall = $shifts_count - $job['signups'];
		if ($shortfall == 0) $shortfall = '';
		$shortfall_row .= "<td {$background}><strong>{$shortfall}</strong></td>";
	}
	$shortfall_row .= "</tr>";

	$out = 
		$section_title . '	
		<div>
			<table><tr><td style="background:Yellow">
				<table border="1" cellspacing="3">
					<tr>' .
						$job_names_header .
						$needed_row .
						$totals_row .
						$shortfall_row . '
					</tr>
				</table>
			</td></tr></table> ' .
			$responders_count . ' people have responded.
		</div>'
	;
	if (0) deb ("index.renderScoreboard(): out =", $out);
	return $out;
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


///////////////////////////// SQL FUNCTIONS

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
