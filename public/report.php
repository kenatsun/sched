<?php
session_start(); 

require_once('globals.php');

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}

require_once "{$relative_dir}/classes/PeopleList.php";
require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";

require_once('display/includes/header.php');

if (0) deb("report: _SESSION = ", $_SESSION);
if (0) deb("report: userIsAdmin() = " . userIsAdmin());

require_once('classes/calendar.php');
require_once('participation.php');

$calendar = new Calendar();
$calendar->job_key = (isset($_GET['key']) && is_numeric($_GET['key'])) ? intval($_GET['key']) : 'all';
$calendar->data_key = (isset($_GET['show'])) ? $_GET['show'] : 'all';
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
			AND p.shift_id=s.id
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
$assn_table = OFFERS_TABLE;
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

$worker_dates = $calendar->getWorkerShiftPrefs();
$selector_html = $calendar->renderDisplaySelectors($calendar->job_key, $calendar->data_key);
$calendar_body = $calendar->renderCalendar(NULL, $worker_dates);
$calendar_headline = (surveyIsClosed() ? "Calendar" : "When we can work");
$cal_string = <<<EOHTML
	<br>
	<h2>{$calendar_headline}</h2>
	<ul>{$selector_html}</ul>
		{$calendar_body}
	<ul>{$selector_html}</ul>
EOHTML;
$comments = (userIsAdmin() ? renderWorkerComments() : "");

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

	// figure out how many assignments are needed for the season, rounding up `
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
$signups = renderJobSignups("Jobs we've signed up for", TRUE);
$non_responders = (!surveyIsClosed() ? renderNonResponders() : "");


// ----------------------------------- toString section
print <<<EOHTML
{$headline}
{$months_overlay}
<br>
{$cal_string}
<br>
{$signups}
<br>
{$non_responders}
<br>
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


</body>
</html>
EOHTML;

// ------------------------------- functions


function renderNonResponders() {
	$non_responders = getNonResponders();
	if (0) deb("report.renderNonResponders: non_responders =", $non_responders);
	foreach($non_responders as $id=>$name) {
		$non_responder_names .= "<tr><td>{$name}</td></tr>
			";
	}
	$non_responders_count = count($non_responders);	
	$out = <<<EOHTML
	<h2>Who hasn't responded yet</h2>
	<table><tr><td style="background:Yellow">
		<table border="1" cellspacing="3">
		{$non_responder_names} 
		</table>
	</td></tr></table>
	{$non_responders_count} people haven't responded.
EOHTML;
	
	if (0) deb("report.renderNonResponders: out =", $out);
	return $out;
}

function renderWorkerComments() {
	
	// Get the worker comments from database
	$season_id = SEASON_ID;
	$select = "w.first_name || ' ' || w.last_name as worker_name, c.avoids, c.prefers, c.clean_after_self, c.comments";
	$from = SCHEDULE_COMMENTS_TABLE . " as c, " . AUTH_USER_TABLE . " as w";
	$where = "c.worker_id = w.id and c.season_id = {$season_id}";
	$order_by = "w.first_name, w.last_name";
	$comments = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("report.renderWorkerComments(): comments =", $comments); 

	$attributes = array(
		"avoids" => "avoid scheduling with", 
		"prefers" => "prefer scheduling with", 
		"clean_after_self" => "clean after cooking?",
		"comments" => "comments",
	);
	if (0) deb("report.renderWorkerComments(): attributes = ", $attributes);

	// Make header row for the table
	$header = '<tr style="text-align:center;"><th></th>';
	foreach($attributes as $key=>$label) {		
		if (0) deb ("report.renderWorkerComments(): attribute = $key, label = $label");
		$header .= '<th style="text-align:center;">' . $label . "</th>";
	}
	$header .= "</tr>";
	if (0) deb ("report.renderWorkerComments(): header =", $header); 

	// Make data rows
	$prev_person_id = 0;
	$rows = '';
	foreach($comments as $index=>$worker) {
		// If this is a new person, start a new row
		if ($signup['person_id'] != $prev_person_id) {
			if ($prev_person_id != "") $rows .= "</tr><tr>";
			$rows .= "
			<tr>
				<td>{value['worker_name']}</td>";
			$prev_person_id = $value['person_id'];
			if (0) deb ("report.renderWorkerComments(): key = $key, value = $value");
		}		
		if (0) deb ("report.renderWorkerComments(): signup['job_id']) = {$value['job_id']}");
		if (0) deb ("report.renderWorkerComments(): key = $key, value = $value");
		if (0) deb ("report.renderWorkerComments(): availability_index) = $availability_index");
		
		// Render the value of each attribute (including worker name)
		foreach	($worker as $key=>$value) {
			$rows .= " <td>{$value}</td>";
		}
		$rows .= "</tr>";
	}
	$out = <<<EOHTML
	<h2>Other Preferences</h2>
	<table style="table-layout:auto; width:100%"><tr><td style="background:Yellow">
		<table style="table-layout:auto; width:100%" border="1" cellspacing="3">
			<tr>
				{$header}
				{$rows}
			</tr>
		</table>
	</td></tr></table>
EOHTML;
	return $out;
}
?>
