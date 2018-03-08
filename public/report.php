<?php
require_once('globals.php');

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";

session_start();

require_once('display/includes/header.php');

$_SESSION['access_type'] = 'guest';
// // Temporarily disabling the following logic...
// if (!isset($_SESSION['access_type'])) {
	// if (isset($_GET['guest'])) {
		// $_SESSION['access_type'] = 'guest';
	// }
	// else if (isset($_POST['password']) && ($_POST['password'] == 'robotron')) {
		// $_SESSION['access_type'] = 'admin';
	// }
	// else if (!isset($_SESSION['access_type'])) {
		// $dir = BASE_DIR;
		// print <<<EOHTML
			// <h2>Meals scheduling reporting</h2>
			// <h3>Please choose access type:</h3>
			// <div class="access_type">
				// <a href="{$dir}/report.php?guest=1">guest</a>
			// </div>
			// <div class="access_type">
				// admin
				// <form method="post" action="{$_SERVER['PHP_SELF']}">
					// <input type="password" name="password">
					// <input type="submit" value="go">
				// </form>
			// </div>
// EOHTML;
		// exit;
	// }
// }

require_once('classes/calendar.php');
require_once('participation.php');

$calendar = new Calendar();
$job_key = (isset($_GET['key']) && is_numeric($_GET['key'])) ?
	intval($_GET['key']) : 'all';
$jobs_html = "<p><em>Show these jobs:</em></p>
	{$calendar->getJobsIndex($job_key)}";

$calendar->loadAssignments();
$calendar->setIsReport(TRUE);

$job_key_clause = ($job_key != 0) ? "AND s.job_id={$job_key}" : '';

// --------   per-worker summary   -----------

// get list of pref (available) counts:
$prefs_table = SCHEDULE_PREFS_TABLE;
$shifts_table = SCHEDULE_SHIFTS_TABLE;
$auth_user_table = AUTH_USER_TABLE;
$sql = <<<EOSQL
	SELECT u.username as username, p.worker_id, s.job_id, count(*) as num
		FROM {$prefs_table} as p, {$auth_user_table} as u, {$shifts_table} as s
		WHERE u.id = p.worker_id
			AND p.date_id=s.id
			{$job_key_clause}
			AND p.pref > 0
		GROUP BY p.worker_id, s.job_id
EOSQL;
$user_pref_count = array();
foreach($dbh->query($sql) as $row) {
	$user_job = $row['username'] . '_' . $row['job_id'];
	$user_pref_count[$user_job] = $row['num'];
}

$job_id_clause = ($job_key != 'all') ?
	"j.id = '{$job_key}' AND\n" : '';

// get the number of assignments per each worker
$ids_clause = get_job_ids_clause();
$sid = SEASON_ID;
$jobs_table = SURVEY_JOB_TABLE;
$assn_table = ASSIGN_TABLE;
$sql = <<<EOSQL
	SELECT u.username, a.job_id, j.description, a.instances
		FROM {$assn_table} as a, {$auth_user_table} as u, {$jobs_table} as j
		WHERE a.season_id={$sid} AND
			({$ids_clause}) AND
			a.type="a" AND
			a.worker_id = u.id AND
			{$job_id_clause}
			a.job_id = j.id
		order by u.username
EOSQL;
$diffs = array();
$assignments = array();
foreach($dbh->query($sql) as $row) {
	$user_job = $row['username'] . '_' . $row['job_id'];

	$num_prefs = isset($user_pref_count[$user_job]) ?
		$user_pref_count[$user_job] : 0;
	$row['num_prefs'] = $num_prefs;
	$assignments[$user_job] = $row;

	$shifts = $row['instances'] * 4;
	$diffs[$user_job] = ($shifts > 0) ?
		round($num_prefs / $shifts, 2) : 0;
}


global $json_assignments_file;
$assigned_data = array();
$assigned_counts = array();
$file = $json_assignments_file;
if (file_exists($file)) {
	$assigned_data = json_decode(file_get_contents($file), true);

	// date => array(job_id => array(workers))
	foreach($assigned_data as $date=>$info) {
		// if a job key is specified, then only display info for that job
		if ($job_key != 'all') {
			if (!isset($info[$job_key])) {
				continue;
			}
			foreach($info[$job_key] as $w) {
				if (isset($assigned_counts[$job_key])) {
					$assigned_counts[$job_key][$w]++;
				}
			}
		}
		else {
			foreach($info as $shift_id=>$workers) {
				foreach($workers as $w) {
					if (isset($assigned_counts[$shift_id])) {
						$assigned_counts[$shift_id][$w]++;
					}
				}
			}
		}
	}
}

// count the number of shifts actually assigned to workers
$rows = '';
// generate html
$per_shift = array();
foreach($diffs as $key=>$diff) {
	$row = $assignments[$key];
	$shifts = $row['instances'] * get_num_dinners_per_assignment($row['job_id']);

	// initialize unseen job
	if (!isset($per_shift[$row['description']])) {
		$per_shift[$row['description']] = 0;
	}
	// track the number of assigned shifts for each job
	$per_shift[$row['description']] += $shifts;

	if ($diff < 1) {
		$diff = "<span class=\"highlight\">{$diff}</span>";
	}

	$job_name = ($job_key != 0) ? '' :
		"<td class=\"nowrap\">{$row['description']}</td>";

	$num_prefs = $row['num_prefs'];
	if ($num_prefs == '') {
		$num_prefs = 0;
	}
/*
	else {
		$shift_coverage[$job_name] += $shifts;
	}
*/

	$shift_id = $job_key;
	if ($job_key == 'all') {
		list($unused, $shift_id) = explode('_', $key);
	}

	$num_assigned = '***';
	if (!empty($assigned_counts) &&
		isset($assigned_counts[$shift_id][$row['username']])) {
		$num_assigned = $assigned_counts[$shift_id][$row['username']];
	}

	$rows .= <<<EOHTML
<tr>
	<td>{$row['username']}</td>
	{$job_name}
	<td align="left">{$shifts}</td>
	<!--
	<td align="left">{$num_prefs}</td>
	<td align="right">{$diff}</td>
	<td align="right">{$num_assigned}</td>
	-->
</tr>
EOHTML;
}

$r = new Respondents();
$responses = '';
if ($_SESSION['access_type'] != 'guest') {
	$responses = $r->getSummary((time() < DEADLINE));
}

$worker_dates = $calendar->getWorkerDates();
$cal_string = $calendar->toString(NULL, $worker_dates);

$comments = '';
if ($_SESSION['access_type'] == 'admin') {
	$comments = $calendar->getWorkerComments($job_key_clause);
}

$job_name = ($job_key != 0) ? '' : '<th>Job</th>';

$meals_summary = $calendar->getNumShifts();

$shift_summary_rows = '';
ksort($per_shift);
if (0) {deb('report.php: $per_shift', $per_shift);}
foreach($per_shift as $job_name=>$num_assn_shifts) {
	// figure out how many shifts the schedule calls for on this type of meal
	list($meal_type, $shift_type) = explode(' ', $job_name, 2);
	$meal_type = strtolower($meal_type);

	// default number of workers per shift
	$num_workers_per_shift = 1;
	switch($meal_type) {
		#!# divide by number of shifts per assignment
		case 'weekday':
		case 'sunday':
			if (stristr($shift_type, 'asst cook')) {
				$num_workers_per_shift = 2;
			}
			if (stristr($shift_type, 'cleaner')) {
				$num_workers_per_shift = 3;
			}
			break;
	}

	$job_id = array_search($job_name, $all_jobs);
	if ($job_id === FALSE) {
		$all = print_r($all_jobs, TRUE);
		$shift_summary_rows .= <<<EOHTML
		<tr>
			<td colspan="4">Unable to find job id for "[{$job_id}] {$job_name}"
				<pre>{$all}</pre>
			</td>
		</tr>
EOHTML;
	}

	// figure out how many assignments are needed for the season, rounding up
	$num_meals_in_season = $meals_summary[$meal_type];
	$num_dinners_per_assn = get_num_dinners_per_assignment($job_id);
	$num_assns_needed = ($num_dinners_per_assn == 0) ? 0 :
		ceil(($num_meals_in_season * $num_workers_per_shift) /
			$num_dinners_per_assn);

	$shift_summary_rows .= <<<EOHTML
	<tr>
		<td>{$job_name}</td>
		<td>{$num_assns_needed}</td>
	</tr>
EOHTML;
}

$headline = renderHeadline("Our Responses So Far");
$months_overlay = $calendar->renderMonthsOverlay();
$signups = renderJobSignups();

// ---- toString section ----
print <<<EOHTML
{$headline}
{$months_overlay}
<br>
<h2>Jobs we've signed up to do</h2>
{$signups}
<div class="responses">{$responses}</div>
<br>
<h2>When we can work</h2>
<ul>{$jobs_html}</ul>
{$cal_string}
{$comments}
<!--
<h2>Number of meals scheduled per-day type:</h2>

<p>
Sundays: {$meals_summary['sunday']}
<br>Weekdays: {$meals_summary['weekday']}
<br> Meetings: {$meals_summary['meeting']}
</p>

<h2>Number of assignments needed:</h2>
<table cellpadding="3" cellspacing="0" border="0" class="striped" width="100%">
<thead>
	<tr>
		<th>Job Name</th>
		<th>Num Assignments Needed</th>
	</tr>
</thead>
<tbody>
	{$shift_summary_rows}
</tbody>
</table>
-->

<ul id="end">{$jobs_html}</ul>

</body>
</html>
EOHTML;

// ------------------------------- functions

function renderJobSignups() {
	$jobs = getJobs();
	$job_names_header = "";
	foreach($jobs as $index=>$job) {
		if (0) deb ("report.renderJobSignups(): name['description']) =", $job['description']);
		$job_names_header .= "<th>" . $job['description'] . "</th>
		";
		if (0) deb ("report.renderJobSignups(): job_names_header =", $job_names_header);
	}
	if (0) deb ("report.renderJobSignups(): job_names_header =", $job_names_header);

	$signups = getJobSignups();
	// $prev_person_id = 0;
	$signup_rows = "";
	foreach($signups as $index=>$signup) {
		// If this is a new person, start a new row
		if ($signup['person_id'] != $prev_person_id) {
			if ($signup_rows != "") $signup_rows .= "</tr>";
			$signup_rows .= "
			<tr>
				<td>{$signup['first_name']} {$signup['last_name']}</td>";
			$prev_person_id = $signup['person_id'];
		}
		// Render the number of times this person will do this job
		if (0) deb("report.renderJobSignups(): signup['person_id'] =? prev_person_id) AFTER =", $signup['person_id'] . "=?" . $prev_person_id);
		$signup_rows .= "
			<td>{$signup['instances']}</td>";
		// Increment the total number of signups for this job
		if (0) deb ("report.renderJobSignups(): signup['job_id']) =", $signup['job_id']);
		$job = array_search($signup['job_id'], array_column($jobs, 'job_id'));
		$jobs[$job]['signups'] += $signup['instances'];
		if (0) deb ("report.renderJobSignups(): jobs[job]['signups'] =", $jobs[$job]['signups']);
	}
	$signup_rows .= "</tr>";
	if (0) deb ("report.renderJobSignups(): signup_rows =", $signup_rows);
	$background = ' style="background:lightgreen;" ';
	// $background = ' style="background:#ffff80;" ';

	// Render a row showing total signups for each job
	// $job = 
	// $job['signups'] = array_search($, array_column);
	$totals_row = "<tr>
		<td {$background}><strong>signups so far</strong></td>";
	foreach($jobs as $index=>$job) {
		$totals_row .= "<td {$background}><strong>{$job['signups']}</strong></td>";
	}
	$totals_row .= "</tr>";
	
	// Render a row showing total signups needed for each job
	$needed_row = "<tr>
		<td {$background}><strong>jobs to fill</strong></td>";
	foreach($jobs as $index=>$job) {
		$needed_row .= "<td {$background}><strong>{$job['instances']}</strong></td>";
	}
	$needed_row .= "</tr>";

	// Render a row showing total signups needed for each job
	$shortfall_row = "<tr>
		<td {$background}><strong>signups still needed</strong></td>";
	foreach($jobs as $index=>$job) {
		$shortfall = max($job['instances'] - $job['signups'], 0);
		if ($shortfall == 0) $shortfall = '';
		$shortfall_row .= "<td {$background}><strong>{$shortfall}</strong></td>";
	}
	$shortfall_row .= "</tr>";

	$out = <<<EOHTML
	<table><tr><td style="background:Yellow">
		<table border="1" cellspacing="3">
			<tr>
				<th></th>
				{$job_names_header}
				{$totals_row}
				{$needed_row}
				{$shortfall_row}
				{$signup_rows}
			</tr>
		</table>
	</td></tr></table>
EOHTML;
	if (0) deb ("report.renderJobSignups(): out =", $out);
	return $out;
}

function getJobs() {
	$jobs_table = SURVEY_JOB_TABLE;
	$select = "j.description, j.id as job_id, j.instances, 0 as signups";
	$from = "{$jobs_table} as j";
	$where = "";
	$order_by = "j.display_order";
	$jobs = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("report.getJobSignups(): jobs =", $jobs);
	return $jobs;
}

function getJobSignups() {
	$person_table = AUTH_USER_TABLE;
	$offers_table = ASSIGN_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$select = "p.id as person_id, p.first_name, p.last_name, o.instances, j.id as job_id, j.description";
	$from = "{$person_table} as p, {$offers_table} as o, {$jobs_table} as j";
	$where = "p.id = o.worker_id and o.job_id = j.id";
	$order_by = "p.first_name, p.last_name, j.display_order";
	$signups = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("report.getJobSignups(): signups =", $signups);
	return $signups;
}

//display_shift_count($shift_coverage);

/*
function display_shift_count($shift_coverage) {
	$shift_count = array();
	// XXX not a great way of implementing this...
	foreach($shift_coverage as $job=>$count) {
		if (stristr($job, 'sunday')) {
			$shift_count['sunday'] += $count;	
		}
		if (stristr($job, 'weekday')) {
			$shift_count['weekday'] += $count;	
		}
		if (stristr($job, 'meeting')) {
			$shift_count['meeting'] += $count;	
		}
		$shift_count['total'] += $count;
	}

	print_r($shift_count);
}
*/

?>
