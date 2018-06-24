<?php
global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}
require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";
require_once "{$relative_dir}/globals.php";
require_once "{$relative_dir}/display/includes/header.php";

session_start();

$headline = renderHeadline("Dashboard"); 
$revision_form = renderRevisionForm();

// Functions to render sections of the Dashboard

function renderRevisionForm() {	
	$jobs_table = SURVEY_JOB_TABLE;
	$shifts_table = SCHEDULE_SHIFTS_TABLE;
	$workers_table = AUTH_USER_TABLE;
	$assignments_table = ASSIGNMENTS_TABLE;
	$season_id = SEASON_ID;
	
	$revision_form_headline = "<h2>Assignments</h2>";
	$jobs = getJobs();
	if (0) deb("dashboard.php.renderRevisionForm(): jobs = ", $jobs);
	
	// Get data for Meals table
	$select = " DISTINCT s.string as meal_date, s.string as meal_day_name";
	$from = "$jobs_table j, $shifts_table s";
	$where = "s.job_id = j.id
		and j.season_id = $season_id";
	$order_by = "date(s.string)";
	$meals = sqlSelect($select, $from, $where, $order_by);
	if (0) deb("dashboard.php.renderRevisionForm(): meals = ", $meals);
	
	// Make the table header row
	$header_row .= "<tr>
		<td><strong>date</td>
		<td><strong>day</td>";
	foreach($jobs as $index=>$job){
		$header_row .= "<td><strong>{$job['description']}</strong></td>";
	}
	$header_row .= "</tr>";
	if (0) deb("dashboard.php.renderRevisionForm(): header_row =", $header_row);

	// Make the table row for each meal
	foreach($meals as $index=>$meal) {	
		$date_ob = new DateTime($meal['meal_date']);
		$meal['meal_day_name'] = $date_ob->format('l');
		// $meal['meal_day_name'] = date('D', $meal['meal_date']);
		// $meal['meal_day_name'] = normalizeDate($meal['meal_date']);
		// $meal['meal_day_name'] = date('D', $meal['meal_day_name']);
		if (0) deb("dashboard.php.renderRevisionForm(): day name = {$meal['meal_day_name']}, date = {$meal['meal_date']}");
		
		// Make the worker cell for each job in this row
		$job_cells = "";
		foreach($jobs as $index=>$job){
			
			if (0) deb("dashboard.php.renderRevisionForm(): job_id = {$job['job_id']}, meal_id = {$meal['id']}");
			
			$select = "s.string as shift_date, w.username as worker_name, s.job_id, w.id as worker_id";
			$from = "$workers_table w, $assignments_table a, $shifts_table s";
			$where = "a.worker_id = w.id
				and a.shift_id = s.id
				and s.job_id = {$job['job_id']}
				and s.string = '{$meal['meal_date']}'";
			$order_by = "worker_name";
			$workers = sqlSelect($select, $from, $where, $order_by);
			if (0) deb("dashboard.php.renderRevisionForm(): workers = ", $workers);
			
			// Make the embedded table listing the workers & controls for this worker cell
			$job_cell = '<td><table>';
			foreach($workers as $w_index=>$worker) {
				$job_cell .= '<tr><td style="background:White">' . "{$worker['worker_name']}</td></tr>";
			}
			
			// Figure out which workers could be added to this job
			$select =
			$
			
			$job_cell .= '<tr><td style="background:White">' . "add-worker widget</td></tr>";
			$job_cell .= "</table></td>";
			if (0) deb("dashboard.php.renderRevisionForm(): job_cell = ", $job_cell);
			$job_cells .= $job_cell;
		}
		$meal_row = <<<EOHTML
		<tr>
			<td style="background:White">{$meal['meal_date']}</td>
			<td style="background:White">{$meal['meal_day_name']}</td>
			{$job_cells}
		</tr>
EOHTML;
		$meal_rows .= $meal_row;
	}
	// <table  cellpadding="8" cellspacing="3" border="1" width="100%" style="table-layout:auto">
	$meals_table = <<<EOHTML
	<table style="table-layout:auto; width:100%" style="background:Yellow">
		<table style="table-layout:auto; width:100%" border="1" cellspacing="3">
		{$header_row}
		{$meal_rows}
		</table>
	</table>
EOHTML;
	$revision_form = <<<EOHTML
	{$revision_form_headline}
	{$meals_table}
EOHTML;
	return $revision_form;
}

$page = <<<EOHTML
	{$headline}
	{$revision_form}
EOHTML;
print $page

?>