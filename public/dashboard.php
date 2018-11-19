<?php
session_start();

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}
require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/change_sets_utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";
require_once "{$relative_dir}/globals.php";
require_once "{$relative_dir}/display/includes/header.php";

if ($_POST) {
	if (0) deb("dashboard.php: _POST = ", $_POST);
	$change_set_id = $_POST['change_set_id'];

	// Processing changes from change_set.php

		// Update assignments table with the changes that user has confirmed 
		if (isset($_POST['confirm'])) {
			if (0) deb("dashboard.php: gonna confirm change_set_id = {$change_set_id}");
			saveAssignmentBasedOnChangeSet($change_set_id, $_POST);
		};

		// Delete change set that user wants to discard
		if (isset($_POST['discard'])) {
			if (0) deb("dashboard.php: gonna discard change_set_id = {$change_set_id}");
			sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0)); 
		};
	
	// Processing changes from change_sets.php
	
		// Undo changes from all change sets including and after the one specified
		if (isset($_POST['undo_back_to_change_set_id'])) {
			$undo_back_to_change_set_id = $_POST['undo_back_to_change_set_id'];
			if (0) deb("dashboard.php: undo_back_to_change_set_id = {$undo_back_to_change_set_id}");
			undoChangeSets($undo_back_to_change_set_id, $_POST);
		};		
}

// Delete change sets of this scheduler run that were never saved.
purgeUnsavedChangeSets();  

$headline = renderHeadline("Dashboard"); 
$seasons_section = renderSeasonSection();
$assignments_form = renderAssignmentsForm();
$change_sets_link = '<p><strong><a href="change_sets.php">View Change Sets</a></strong></p>';

$page = <<<EOHTML
	{$headline}
	{$seasons_section}
	{$change_sets_link}
	{$assignments_form}
EOHTML;
print $page;


// Functions to render sections of the Dashboard

function renderSeasonSection() {
	$revision_season_headline = "<h2>Seasons</h2>";
	
	// Get data for Seasons table
	$seasons = sqlSelect("*", "seasons", "", "date(start_date)", (0), "Seasons");
	if (0) deb("dashboard.php.renderSeasonSection(): seasons = ", $seasons);
}

function renderAssignmentsForm() {	
	$jobs_table = SURVEY_JOB_TABLE;
	$shifts_table = SCHEDULE_SHIFTS_TABLE;
	$workers_table = AUTH_USER_TABLE;
	$assignments_table = ASSIGNMENTS_TABLE;
	$changes_table = CHANGES_TABLE;
	$change_sets_table = CHANGE_SETS_TABLE;
	$meals_table = MEALS_TABLE;
	$season_id = SEASON_ID;
	
	$assignments_form_headline = "<h2>Assignments</h2>";
	$jobs = getJobs();
	if (0) deb("dashboard.php.renderAssignmentsForm(): jobs = ", $jobs);

	if (!scheduler_run()) {
		return "{$assignments_form_headline}
			<p>So far, no assignments have been generated for this season.</p>
			<p>Probably you need to run the Scheduler.</p>";
	}
	
	// Get id of the most recent scheduler run
	$scheduler_run_id = scheduler_run()['id'];
	if (0) deb("dashboard.php.renderAssignmentsForm(): scheduler_run_id = ", $scheduler_run_id); 

	$select = " DISTINCT m.date as meal_date, m.id";
	$from = "{$jobs_table} j, {$shifts_table} s, {$meals_table} m";
	$where = "s.job_id = j.id
		and m.id = s.meal_id
		and j.season_id = {$season_id}";
	$order_by = "m.date asc";
	$meals = sqlSelect($select, $from, $where, $order_by, (0), "dashboard.renderAssignmentsForm(): get assignments for season");
	if (0) deb("dashboard.php.renderAssignmentsForm(): meals = ", $meals); 
	
	// Make the table header row
	$ncols = 1;
	$header_row .= "<tr>
		<td><strong>meal date</td>";
	foreach($jobs as $index=>$job){
		$header_row .= "<td><strong>{$job['description']}</strong></td>";
		++$ncols;
	}
	$header_row .= "</tr>";
	if (0) deb("dashboard.php.renderAssignmentsForm(): header_row =", $header_row);

	// Sort the meals by date (ascending)
	usort($meals, "meal_date_sort");
	if (0) deb("dashboard.php.renderAssignmentsForm(): meals after sort = ", $meals);
	if (0) deb("dashboard.php.renderAssignmentsForm(): time_order = ", $time_order); 
		
	$previous_meal_month = 0;
	
		
	// Make the table row for each meal
	$nrows = 3;
	$save_button_interval = 3;
	foreach($meals as $i=>$meal) {
		if (0) deb("dashboard.renderAssignmentsForm() meal['meal_date'] = {$meal['meal_date']}");
		$date_ob = new DateTime($meal['meal_date']);
		$meal['meal_day_name'] = $date_ob->format('l');
		$meal_month = $date_ob->format('m');
		$meal_date = $date_ob->format('F j'); 
		if (0) deb("dashboard.renderAssignmentsForm() meal_month = {$meal_month}");
		if (0) deb("dashboard.php.renderAssignmentsForm(): day name = {$meal['meal_day_name']}, date = {$meal['meal_date']}");

		if ($nrows == $save_button_interval) {
			$meal_rows .= <<<EOHTML
				<tr><td colspan={$ncols} style="text-align: left;"><input type="submit" value="Review All Changes"> <input type="reset" value="Cancel All Changes"> </td><tr> 
				{$header_row}
EOHTML;
			$nrows = 1;
		} else {
			++$nrows;
		}
		$previous_meal_month = $meal_month;
	
		// Make the worker cell for each job in this row
		$shift_cells = "";
		foreach($jobs as $i=>$job){
			
			if (0) deb("dashboard.php.renderAssignmentsForm(): job_id = {$job['job_id']}, meal_id = {$meal['id']}");
			
			// Get the id of this shift (i.e. this job for this meal)
			$select = "s.id as id";
			$from = "{$shifts_table} as s,
				{$meals_table} as m";
			$where = "s.job_id = {$job['job_id']}
				and s.meal_id = m.id
				and m.id = {$meal['id']}";
			$order_by = "";
			$shifts = sqlSelect($select, $from, $where, $order_by, (0), "dashboard.renderAssignmentsForm()"); 
			$shift_id = $shifts[0]['id'];
			if (0) deb("dashboard.php.renderAssignmentsForm(): shift_id = {$shift_id}");

			// Find the worker(s) doing this shift (i.e. this job for this meal)
			$select = "w.username as worker_name, 
				w.id as worker_id,  
				a.id as assignment_id,  
				a.latest_change_id,
				a.when_last_changed,
				a.generated,
				a.exists_now"; 
			$from = "{$workers_table} as w, 
				{$assignments_table} as a";
			$where = "a.worker_id = w.id
				and a.shift_id = {$shift_id}";
			$order_by = "worker_name";
			$assignments = sqlSelect($select, $from, $where, $order_by, (0), "renderAssignmentsForm(): assignments in shift");
			if (0) deb("dashboard.php.renderAssignmentsForm(): assignments = ", $assignments);
			
			// Make the embedded table listing the workers & controls for this shift cell
			$shift_cell = '<td><table>';
			if (SHOW_IDS) $shift_cell .= '<tr><td>shift #' . $shift_id . '</td></tr>';
			foreach($assignments as $w_index=>$assignment) {
				if (SHOW_IDS) $wkr_id = ' (#' . $assignment['worker_id'] . '), assmt #' . $assignment['assignment_id']; 

				if (0) deb("dashboard.php.renderAssignmentsForm(): assignment = ", $assignment);
				$exists_now = $assignment['exists_now']; 
				$has_changed = ($assignment['generated'] != $exists_now ? 1 : 0);
				if (0) deb("dashboard.php.renderAssignmentsForm(): exists_now = {$exists_now}, has_changed = {$has_changed}, assm_id = {$assignment['assignment_id']}");		
				
				// If assignment's status changed since generation, make a change marker
				if ($has_changed) {
					// Get data about the latest change
					$select = "s.when_saved as when_saved, s.id as id";
					$from = CHANGES_TABLE . " as c, " . CHANGE_SETS_TABLE . " as s";
					$where = "c.id = {$assignment['latest_change_id']}
						and s.id = c.change_set_id";
					$latest_change_set = sqlSelect($select, $from, $where, "", (0), "latest change set")[0];
					if (0) deb("dashboard.php.renderAssignmentsForm(): latest_change_set = ", $latest_change_set); 
					
					if (SHOW_IDS) $chg_id = '<br>(chg set #' . $latest_change_set['id'] . ')';
					$change_marker = formatted_date($latest_change_set['when_saved'], "M j g:ia") . $chg_id;
					if (0) deb("dashboard.php.renderAssignmentsForm(): change_marker = {$change_marker}"); 
					
					// If assignment exists now, make an "added" marker	 
					if ($exists_now) {
						$assignment_color = "LightGreen";
						$change_marker = ' - added ' . $change_marker; 
					} 
					// Else assignment doesn't exist now, so make a "removed" marker
					else { 
						$assignment_color = "Pink";
						$change_marker = ' - removed ' . $change_marker; 						
					}
				}
				// Else assignment's status is the same as at generation, so don't make a change marker
				else {
					$assignment_color = "White";
					$change_marker = "";					
				}
				
				// Render the assignment if it exists and/or has been removed since generation
				// Don't show an assignment that was not generated and doesn't currently exist
				if ($exists_now || $has_changed) {
					$shift_cell .= '<tr><td style="background:' . $assignment_color . '"><strong>' . $assignment['worker_name'] . '</strong>' . $wkr_id . $change_marker; 
				}
				
				// Display controls that would remove worker from shift, unless worker has been removed already
				if ($exists_now) {	
					$shift_cell .= '<table>';
					$shift_cell .= '<tr><td style="text-align: right;">remove</td><td><input type="checkbox" name="remove[]" value="' . $assignment['assignment_id'] . '"></td></tr>';

					// Figure out which shifts this worker could be added to
					$possible_shifts = getPossibleShiftsForWorker($assignment['worker_id'], $job['job_id'], TRUE);
					if (0) deb("dashboard.php.renderAssignmentsForm(): worker = {$assignment['worker_name']} {$assignment['worker_id']}, possible_shifts = ", $possible_shifts); 
					// Display the possible shifts in a dropdown box
					if ($possible_shifts) {
						$shift_cell .= '<tr><td style="text-align: right;">move to</td><td><select class="preference_selection" style="font-size: 9pt" name = "move[]">';
						$shift_cell .= '<option value=""></option>';
						foreach($possible_shifts as $s_index=>$possible_shift) {
							$shift_date_ob = new DateTime($possible_shift['shift_date']);
							$dow = $shift_date_ob->format('D');
							$shift_cell .= '<option style="font-size: 9pt" value="' . $assignment['assignment_id'] . '_to_' . $possible_shift['shift_id'] . '">' . "{$dow} {$possible_shift['shift_date']}</option>";
						}
						$shift_cell .= "</select></td></tr>";
					}
					
					// Get possible trades into this shift for this worker
					$possible_trades = getPossibleTradesForWorkerOnShift($assignment['worker_id'], $shift_id, $job['job_id']);
					if (0) deb("dashboard.php.renderAssignmentsForm(): worker = {$assignment['worker_name']} {$assignment['worker_id']}, possible_trades = ", $possible_trades); 
					// Display the possible trades in a dropdown box
					if ($possible_trades) {
						$shift_cell .= '<tr><td style="text-align: right;">trade to</td><td><select class="preference_selection" name="trade[]" style="font-size: 9pt">';
						$shift_cell .= '<option value="' . "" . '">' . "</option>";
						foreach($possible_trades as $t_index=>$possible_trade) {
							$shift_date_ob = new DateTime($possible_trade['shift_date']);
							$dow = $shift_date_ob->format('D');
							$shift_cell .= '<option value="' . $assignment['assignment_id'] . '_with_' . $possible_trade['assignment_id'] . '">';
							$shift_cell .= "{$dow} {$possible_trade['shift_date']} for {$possible_trade['worker_name']}</option>";
						}
						$shift_cell .= '</select></td></tr>';
					}
					$shift_cell .= '</td></tr>';
					$shift_cell .= "</table>";
				}
			}
			
			// Figure out which workers could be added to this shift
			$available_workers = getAvailableWorkersForShift($shift_id, FALSE, TRUE);
			if (0) deb("dashboard.php.renderAssignmentsForm(): meal_date = {$meal['meal_date']}, available_workers = ", $available_workers); 
			// Display the available workers in a dropdown box
			if ($available_workers) {
				$shift_cell .= '<tr><td style="background:White"><hr>add ' . $job['description'] . ' <select class="preference_selection" style="font-size: 9pt" name = "add[]">';
				$shift_cell .= '<option value="' . "" . '">' . "</option>";
				foreach($available_workers as $w_index=>$available_worker) {
					$color = $available_worker['open_offers_count'] > 0 ? "LightGreen" : "LightGray";
					$shift_cell .= '<option style="background-color:' . $color . ';" value="' . $available_worker['worker_id'] . '_to_' . $shift_id . '">' . "{$available_worker['worker_name']}  ({$available_worker['open_offers_count']})</option>";
				}
				$shift_cell .= '</select></td></tr>';
			}
			$shift_cell .= "</table></td>"; 
			if (0) deb("dashboard.php.renderAssignmentsForm(): shift_cell = ", $shift_cell); 
			$shift_cells .= $shift_cell;
		}

		$meal_row = <<<EOHTML
		<tr>
			<td style="background:White"><strong><big>{$meal_date}</big></strong><br>{$meal['meal_day_name']}</td> 
			{$shift_cells}
		</tr>
EOHTML;
		$meal_rows .= $meal_row; 
	}

	$meal_rows .= <<<EOHTML
		<tr><td colspan={$ncols} style="text-align: center;"><input type="submit" value="Review All Changes"> <input type="reset" value="Cancel All Changes"></td><tr>
EOHTML;
		
	$meals_table = <<<EOHTML
	<table style="table-layout:auto; width:100%" style="background:Yellow">
		<table style="table-layout:auto; width:100%" border="1" cellspacing="3">
		{$meal_rows} 
		</table>
	</table>
EOHTML;

	$assignments_form = <<<EOHTML
	{$assignments_form_headline}  
	<form action="change_set.php" method="post">
	{$meals_table}
	<input type="hidden" name="scheduler_run_id" id="scheduler_run_id" value="{$scheduler_run_id}" />
	</form>
EOHTML;
	return $assignments_form;
}


// Supporting functions

function getAvailableWorkersForShift($shift_id, $addable_only=FALSE, $omit_avoiders=TRUE) { 
	$season_id = SEASON_ID;
	$select = "p.worker_id, 
			p.worker as worker_name,
			p.job_name, 
			o.open_offers_count";
	$from = "possible_shifts_for_workers p, open_offers_count o";
	$where = "p.shift_id = {$shift_id}      	-- This shift.
			and p.season_id = {$season_id}  	-- This season.
			and o.worker_id = p.worker_id		-- The open offers of this worker
			and o.job_id = p.job_id				-- to do this job
			and o.season_id = {$season_id}		-- in this season.";
	// Optionally, include only workers who have more offers than assignments to do this job.
	if ($addable_only) $where = $where . "
			-- Include only workers who have more offers than assignments to do this job
			and p.worker_id in (						-- This worker is in
				select oo.worker_id 					-- the set of workers		
				from open_offers_count as oo
				where oo.open_offers_count > 0			-- who have more offers than assignments
					and oo.job_id = p.job_id			-- to do this job
					and oo.season_id = {$season_id}		-- in this season.
		)";
	$order_by = "open_offers_count desc, pref desc, worker asc";
	$available_workers = sqlSelect($select, $from, $where, $order_by, (0), "available_workers");
	if (0) deb("dashboard.php:getPossibleShiftsForWorker() available_workers for shift {$shift_id} (from view) = ", $available_workers);

	return $available_workers;
}

function getPossibleShiftsForWorker($worker_id, $job_id, $omit_avoiders=TRUE) {
	$season_id = SEASON_ID;
	$select = "shift_id, 
			worker,
			shift_date,
			job_id,
			job_name";
	$from = "possible_shifts_for_workers";
	$where = "worker_id = {$worker_id}    -- This worker.
			and season_id = {$season_id}  -- This season.
			and job_id = {$job_id}        -- This job type.";
	$order_by = "shift_id asc";
	$possible_shifts = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleShiftsForWorker()");
	if (0) deb("dashboard.php:getPossibleShiftsForWorker() possible_shifts = ", $possible_shifts);

	return $possible_shifts;
}

function getPossibleTradesForWorkerOnShift($worker_id, $shift_id, $job_id) {
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
	$order_by = "shift_id asc";
	$possible_trades = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleTradesForWorkerOnShift()");
	if (0) deb("dashboard.php:getPossibleTradesForWorkerOnShift() possible_shifts = ", $possible_trades);
	
	return $possible_trades;  
}

?>