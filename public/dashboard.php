<?php
session_start();

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}
require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";
require_once "{$relative_dir}/globals.php";
require_once "{$relative_dir}/display/includes/header.php";


$headline = renderHeadline("Dashboard"); 
$revision_form = renderRevisionForm();
$seasons_section = renderSeasonSection();
$page = <<<EOHTML
	{$headline}
	{$seasons_section}
	{$revision_form}
EOHTML;
print $page;


// Functions to render sections of the Dashboard

function renderSeasonSection() {
	$revision_season_headline = "<h2>Seasons</h2>";
	
	// Get data for Seasons table
	$seasons = sqlSelect("*", "seasons", "", "date(start_date)", (0), "Seasons");
	if (0) deb("dashboard.php.renderSeasonSection(): seasons = ", $seasons);
}

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
	$select = " DISTINCT s.string as meal_date";
	$from = "{$jobs_table} j, {$shifts_table} s";
	$where = "s.job_id = j.id
			and j.season_id = {$season_id}";
	// $order_by = "";
	$order_by = "date(s.string) asc";
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

	// Sort the meals by date (ascending)
	usort($meals, "meal_date_sort");
	if (0) deb("dashboard.php.renderRevisionForm(): meals after sort = ", $meals);
	if (0) deb("dashboard.php.renderRevisionForm(): time_order = ", $time_order);
	
	
	// Make the table row for each meal
	foreach($meals as $m_index=>$meal) {	
		$date_ob = new DateTime($meal['meal_date']);
		$meal['meal_day_name'] = $date_ob->format('l');

		if (0) deb("dashboard.php.renderRevisionForm(): day name = {$meal['meal_day_name']}, date = {$meal['meal_date']}");
		
		// Make the worker cell for each job in this row
		$shift_cells = "";
		foreach($jobs as $m_index=>$job){
			
			if (0) deb("dashboard.php.renderRevisionForm(): job_id = {$job['job_id']}, meal_id = {$meal['id']}");
			
			// Get the id of this shift (i.e. this job for this meal)
			$select = "s.id as id";
			$from = "$shifts_table s";
			$where = "s.job_id = {$job['job_id']}
				and s.string = '{$meal['meal_date']}'";
			$order_by = "";
			$shift = sqlSelect($select, $from, $where, $order_by);
			$shift_id = $shift[0]['id'];
			if (0) deb("dashboard.php.renderRevisionForm(): shift_id = {$shift_id}");

			// Find the worker(s) doing this shift (i.e. this job for this meal)
			$select = "w.username as worker_name, w.id as worker_id";
			$from = "$workers_table w, $assignments_table a";
			$where = "a.worker_id = w.id
				and a.shift_id = {$shift_id}";
			$order_by = "worker_name";
			$workers = sqlSelect($select, $from, $where, $order_by);
			if (0) deb("dashboard.php.renderRevisionForm(): workers = ", $workers);
			
			// Make the embedded table listing the workers & controls for this shift cell
			$shift_cell = '<td><table>';
			foreach($workers as $w_index=>$worker) {
				$shift_cell .= '<tr><td style="background:White">' . "{$worker['worker_name']} ";
				// Figure out which shifts this worker could be added to
				$possible_shifts = getPossibleShiftsForWorker($worker['worker_id'], $job['job_id'], TRUE);
				if (0) deb("dashboard.php.renderRevisionForm(): worker = {$worker['worker_name']} {$worker['worker_id']}, possible_shifts = ", $possible_shifts); 
				// Display the possible shifts in a dropdown box
				if ($possible_shifts) {
					$shift_cell .= '<select class="preference_selection"  name = "' . $worker['worker_id'] . '_shifts">';
					$shift_cell .= '<option value="">' . "move to</option>";
					foreach($possible_shifts as $s_index=>$possible_shift) {
						$shift_cell .= '<option value="' . "{$possible_shift['shift_id']}" . '">' . "{$possible_shift['shift_date']}</option>";
					}
					$shift_cell .= "</select>";
					$possible_swaps = getPossibleSwapsForWorkerOnShift($worker['worker_id'], $shift_id, $job['job_id']);
					if (0) deb("dashboard.php.renderRevisionForm(): worker = {$worker['worker_name']} {$worker['worker_id']}, possible_swaps = ", $possible_swaps); 
					// Display the possible shifts in a dropdown box
					if ($possible_swaps) {
						$shift_cell .= '<select class="preference_selection" name = "' . $worker['worker_id'] . '_swaps">';
						$shift_cell .= '<option value="' . "" . '">' . "swap with</option>";
						foreach($possible_swaps as $t_index=>$possible_swap) {
							$shift_cell .= '<option value="' . "{$possible_swap['assignment_id']}" . '">';
							$shift_cell .= "{$possible_swap['worker_name']} from {$possible_swap['shift_date']}</option>";
						}
						$shift_cell .= "</select>";
					}
					$shift_cell .= "</td></tr>";
				}
			}
			
			// Figure out which workers could be added to this shift
			$available_workers = getAvailableWorkersForShift($shift_id, TRUE, TRUE);
			if (0) deb("dashboard.php.renderRevisionForm(): meal_date = {$meal['meal_date']}, available_workers = ", $available_workers); 
			// Display the available workers in a dropdown box
			if ($available_workers) {
				$shift_cell .= '<tr><td style="background:White"><select class="preference_selection" name = "' . $shift_id . '">';
				$shift_cell .= '<option value="' . "" . '">' . "available</option>";
				foreach($available_workers as $w_index=>$available_worker) {
					$shift_cell .= '<option value="' . "{$available_worker['worker_id']}" . '">' . "{$available_worker['worker_name']}</option>";
				}
				$shift_cell .= "</select></td></tr>";
			}
			$shift_cell .= "</table></td>";
if (0) deb("dashboard.php.renderRevisionForm(): job_cell = ", $shift_cell); 
			$shift_cells .= $shift_cell;
		}
		$meal_row = <<<EOHTML
		<tr>
			<td style="background:White">{$meal['meal_date']}</td>
			<td style="background:White">{$meal['meal_day_name']}</td>
			{$shift_cells}
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


// Supporting functions

function getAvailableWorkersForShift($shift_id, $addable_only=FALSE, $omit_avoiders=TRUE) { 
	if (0) deb("dashboard.getAvailableWorkersForShift: shift_id = $shift_id");
	$season_id = SEASON_ID;
	$assignments_table = ASSIGNMENTS_TABLE;
	$workers_table = AUTH_USER_TABLE;
	$shifts_table = SCHEDULE_SHIFTS_TABLE;
	$shift_prefs_table = SCHEDULE_PREFS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$offers_table = ASSIGN_TABLE;

	$select = "worker_id, 
			worker as worker_name,
			-- shift_id,
			job_name";
	$from = "possible_shifts_for_workers";
	$where = "shift_id = {$shift_id}      -- This shift.
			and season_id = {$season_id}  -- This season.";
	// Optionally, include only workers who have more offers than assignments to do this job.
	if ($addable_only) $where = $where . "
			-- Include only workers who have more offers than assignments to do this job
			and worker_id in (						-- This worker is in
				select worker_id 					-- the set of workers		
				from open_offers_count
				where open_offers_count > 0			-- who have more offers than assignments
					and job_id = job_id				-- to do this job
					and season_id = {$season_id}	-- in this season.
		)";
	$order_by = "pref desc, worker asc";
	$available_workers = sqlSelect($select, $from, $where, $order_by, (0), "available_workers");
	if (0) deb("dashboard.php:getPossibleShiftsForWorker() available_workers for shift {$shift_id} (from view) = ", $available_workers);

	return $available_workers;
}

function getPossibleShiftsForWorker($worker_id, $job_id, $omit_avoiders=TRUE) {
	$season_id = SEASON_ID;
	$assignments_table = ASSIGNMENTS_TABLE;
	$workers_table = AUTH_USER_TABLE;
	$shifts_table = SCHEDULE_SHIFTS_TABLE;
	$shift_prefs_table = SCHEDULE_PREFS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;

	$select = "shift_id, 
			worker,
			shift_date,
			job_id,
			job_name";
	$from = "possible_shifts_for_workers";
	$where = "worker_id = {$worker_id}    -- This worker.
			and season_id = {$season_id}  -- This season.
			and job_id = {$job_id}       -- This job type.";
	$order_by = "";
	$possible_shifts = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleShiftsForWorker()");
	if (0) deb("dashboard.php:getPossibleShiftsForWorker() possible_shifts = ", $possible_shifts);

	return $possible_shifts;
}

function getPossibleSwapsForWorkerOnShift($worker_id, $shift_id, $job_id) {
	$season_id = SEASON_ID;
	$assignments_table = ASSIGNMENTS_TABLE;
	$workers_table = AUTH_USER_TABLE;
	$shifts_table = SCHEDULE_SHIFTS_TABLE;
	$shift_prefs_table = SCHEDULE_PREFS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$offers_table = ASSIGN_TABLE;

	$select = 
		"that_worker_id as worker_id,
		that_worker_current_shift_id as shift_id,
		that_worker_current_assignment_id as assignment_id,
		job_id as job_id,
		that_worker_name as worker_name,
		that_worker_current_shift_date as shift_date";
	$from = "swaps";
	$where = "this_worker_id = {$worker_id}
		and this_worker_current_shift_id = {$shift_id}
		and job_id = {$job_id}";

	$order_by = "date(shift_date) asc";
	$possible_swaps = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleSwapsForWorkerOnShift()");
	if (0) deb("dashboard.php:getPossibleSwapsForWorkerOnShift() possible_shifts = ", $possible_swaps);
	
	return $possible_swaps; 
}

?>