<?php
require_once 'constants.inc';
require_once 'git_ignored.php';

function surveyIsClosed() {
	$is_closed = (DEADLINE < time() ? TRUE : FALSE);
	$deadline = DEADLINE;
	$time = time();
	if (0) deb("utils:surveyIsClosed(): is_closed = $is_closed, DEADLINE = $deadline, time() = $time");
	return DEADLINE < time();
}

function determineUserStatus() {
	if (isset($_GET['admin']) || isset($_GET['a'])) { 
		// if (isset($_COOKIE["admin"])) setcookie("admin", FALSE, time()+86400,"/");
		promptForAdminPassword();
	}
	// else {
		// if (!$_SESSION['access_type'] == 'admin') $_SESSION['access_type'] = 'guest';	
	// }
	// if (0) deb("utils.determineUserStatus: isset(_COOKIE['admin']) before = ", isset($_COOKIE['admin']));
	if (isset($_POST['password'])) { 
		// if ($_POST['password'] == 'robotron') {  
		if ($_POST['password'] == 'r') {  
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
	// if (0) deb("utils.determineUserStatus: isset(_COOKIE['admin']) after = " . isset($_COOKIE["admin"]));
	if (0) deb("utils.determineUserStatus: _COOKIE = ", $_COOKIE);
	if (0) deb("utils.determineUserStatus: _SESSION['access_type'] = ", $_SESSION['access_type']);
	if (0) deb("utils.determineUserStatus: _SESSION = ", $_SESSION);
	// return $_SESSION['access_type'];
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
	$season_table = SEASON_TABLE;
	$season_id = SEASON_ID;
	$sql = <<<EOSQL
		SELECT description FROM {$season_table} WHERE id = {$season_id};
EOSQL;
	$season_name = array();
	foreach($dbh->query($sql) as $row) {
		$season_name[] = $row['description'];
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
function get_current_season() {
	if (0) deb("utils.get_current_season: SEASON_NAME = ", SEASON_NAME);
	switch(SEASON_NAME) {
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
	$easter_month = date('n', easter_date(SEASON_YEAR));
	$easter_day = date('j', easter_date(SEASON_YEAR));
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
		1 => [1],
		7 => [4],
		10 => [31],
		12 => [24,25, 31],
	];

	$holidays = add_easter($holidays);

	// add memorial day
	$mem_day = date('j', strtotime('last monday of May, ' . SEASON_YEAR));
	// sunday, day before
	$holidays[5][] = ($mem_day - 1);
	// monday, memorial day
	$holidays[5][] = $mem_day; 

	// sunday before labor day
	// if last day of aug is sunday, then next day is labor day... skip
	$last_aug = date('D', strtotime('last day of August, ' . SEASON_YEAR));
	if ($last_aug == 'Sun') {
		$holidays[8][] = 31;
	}

	// labor day
	$labor_day = date('j', strtotime('first monday of September, ' . SEASON_YEAR));
	// if the Sunday before is in Sept, then skip it
	if ($labor_day > 1) {
		$holidays[9][] = ($labor_day - 1);
	}
	$holidays[9][] = $labor_day;

	// thanksgiving
	$thx_day = date('j', strtotime('fourth thursday of November, ' . SEASON_YEAR));
	$holidays[11][] = $thx_day;
	$last_sunday = date('j', strtotime('last sunday of November, ' . SEASON_YEAR));
	if ($last_sunday > $thx_day) {
		$holidays[11][] = $last_sunday;
	}

	ksort($holidays);
	$yr = SEASON_YEAR;
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
//SUNWARD
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
//SUNWARD
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
function renderHeadline($text) {
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
	// Note: I was trying to put this into the above, but it got an html error about 'Unexpected Post"
				// <form method="post" action="{$_SERVER['PHP_SELF']}">
				// <input type="hidden" name="guest">
				// <li><input type="submit" value="{$sign_in_as_guest_label}"></li>
			// </form>

	return <<<EOHTML
	{$instance_notice}
	{$admin_notice}
	<table><tr>
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
	$jobs = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("utils.getJobs(): jobs =", $jobs);
	return $jobs;
}

function getJobSignups() {
	$person_table = AUTH_USER_TABLE;
	$offers_table = ASSIGN_TABLE;
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

function getJobAssignments($date_string=NULL, $job_id=NULL, $worker_id=NULL) {
	// list the assigned workers
	$date_clause = ($date_string ? " and s.string = '{$date_string}'" : "");
	$job_id_clause = ($job_id ? " and j.id = '{$job_id} '" : "");
	$worker_id_clause = ($worker_id ? " and w.id = '{$worker_id} '" : "");
	$select = "w.first_name || ' ' || w.last_name as worker_name, w.first_name, w.last_name, a.*, s.string as meal_date, s.job_id, j.description";
	$from = AUTH_USER_TABLE . " as w, " . ASSIGNMENTS_TABLE . " as a, " . SCHEDULE_SHIFTS_TABLE . " as s, " . SURVEY_JOB_TABLE . " as j";
	$where = "w.id = a.worker_id and a.shift_id = s.id and s.job_id = j.id and j.season_id = " . SEASON_ID . " {$date_clause} {$job_id_clause} {$worker_id_clause}
		and a.scheduler_timestamp = (select max(scheduler_timestamp) from " . ASSIGNMENTS_TABLE . ") {$job_id_clause}";
	$order_by = "j.display_order";
	$assignments = sqlSelect($select, $from, $where, $order_by);
	if (0) deb("utils.getJobAssignments(): assignments:", $assignments);
	return $assignments;
}

function getResponders() {
	$responder_ids = array();
	$signups_table = ASSIGN_TABLE;
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

// Generic SQL SELECT
function sqlSelect($select, $from, $where, $order_by) {
	global $dbh;
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
	if (0) deb("utils.sqlSelect: sql:", $sql);
	$results = array();
	foreach($dbh->query($sql) as $row) {
		// Get rid of the numbered elements that get stuck into these row-arrays,  
		// leaving only named attributes as elements in the results array
		foreach($row as $key=>$value) {
			if (is_int($key)) unset($row[$key]);
		}
		$results[] = $row;
	}
	if (0) deb("utils.sqlSelect: results:", $results);
	return $results;
}

// Generic SQL REPLACE
// REPLACE INTO apparently works with SQLite and MySQL but not PostgreSQL, 
// so would have to rewrite this function for PostgreSQL
function sqlReplace($table, $columns, $values) {
	global $dbh;
	$sql = <<<EOSQL
		REPLACE INTO {$table} ({$columns})
		VALUES ({$values}) 
EOSQL;
	if (0) deb("utils.sqlReplace: sql:", $sql);
	$rows_affected = $dbh->exec($sql);
	if (0) deb("utils.sqlSelect: rows_affected:", $rows_affected);
	return $rows_affected;
}
?>
