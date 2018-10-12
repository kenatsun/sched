<?php

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}
require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";
require_once "{$relative_dir}/globals.php";

define('OK_CHANGE_VALUE', 'ok_change');
if (0) deb("change_sets.php: OK_CHANGE_VALUE = ", OK_CHANGE_VALUE);

// Work with assignments and changes from the latest scheduler run in the current season.
function scheduler_run() {
	return sqlSelect("*", SCHEDULER_RUNS_TABLE, "season_id = " . SEASON_ID, "run_timestamp desc", (0))[0];
}

// Show change set from form data, get user confirmation
function confirmChangeSet($posts) {
	if (0) deb("change_sets_utils.confirmChangeSet(): POST = ", $posts); 
	
	// Insert the change set, and get its id
	$scheduler_run_id = scheduler_run()['id'];
	sqlInsert(CHANGE_SETS_TABLE, "scheduler_run_id", "{$scheduler_run_id}", (0));
	$change_set_id = sqlSelect("id", CHANGE_SETS_TABLE, "scheduler_run_id = {$scheduler_run_id}", "id desc", (0))[0]['id'];
	if (0) deb("change_sets_utils.confirmChangeSet(): change_set_id = ", $change_set_id);
	
	// Insert remove requests into changes table
	$removes = array();
	if ($posts['remove']) {
		foreach ($posts['remove'] as $i=>$remove){
			if ($remove) 
				$assignment_id = $remove;
				$assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$assignment_id}", "", (0))[0];
				$worker_id = $assignment['worker_id'];
				$shift_id = $assignment['shift_id'];
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {$worker_id}, {$shift_id}", (0));
		}
	}

	// Insert add requests into changes table
	$adds = array();
	if ($posts['add']) {
		foreach ($posts['add'] as $i=>$add){
			if ($add) {
				$worker_id = explode("_to_",$add)[0];
				$shift_id = explode("_to_",$add)[1];
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$worker_id}, {$shift_id}", (0));
			}
		}
	}

	// Insert move requests into changes table
	$moves = array();
	if ($posts['move']) {
		foreach ($posts['move'] as $i=>$move){ 
			if ($move) {
				$assignment_id = explode("_to_",$move)[0];
				$assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$assignment_id}", "", (0))[0];
				// Move this worker to that shift
				$worker_id = $assignment['worker_id'];
				$from_shift_id = $assignment['shift_id'];
				$to_shift_id = explode("_to_",$move)[1];
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {$worker_id}, {$from_shift_id}", (0));
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$worker_id}, {$to_shift_id}", (0));
			}
		}
	}

	// Insert trade requests into changes table 
	$trades = array();
	if ($posts['trade']) {
		foreach ($posts['trade'] as $i=>$trade){ 
			if ($trade) {
				// Get data on this assignment
				$this_assignment_id = explode("_with_",$trade)[0];
				$this_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$this_assignment_id}", "", (0))[0];
				$this_worker_id = $this_assignment['worker_id'];
				$this_shift_id = $this_assignment['shift_id'];
				// Get data on that assignment
				$that_assignment_id = explode("_with_",$trade)[1];
				$that_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$that_assignment_id}", "", (0))[0];
				$that_worker_id = $that_assignment['worker_id'];
				$that_shift_id = $that_assignment['shift_id']; 
				// Move this worker to that shift
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {this_worker_id}, {$this_shift_id}", (0), "rem this from this");
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$this_worker_id}, {$that_shift_id}", (0), "add this to that");
				// Move that worker to this shift
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {$that_worker_id}, {$that_shift_id}", (0), "rem that from that");
				sqlInsert(CHANGES_TABLE, "add_or_remove, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$that_worker_id}, {$this_shift_id}", (0), "add that to this");
			}
		}
	}
	
	if (0) deb("change_sets_utils.confirmChangeSet(): change_set_id = ", $change_set_id); 

	return $change_set_id;
}

// Render one change set
function renderChangeSet($change_set_id, $show_ok_checkbox=TRUE) {
	
	// Read changes in current change set from database
	$select = "c.*, s.string as meal_date, w.first_name || ' ' || w.last_name as worker_name, j.description as job_name";
	$from = SCHEDULE_SHIFTS_TABLE . " as s, " . 
		CHANGES_TABLE . " as c, " .  
		AUTH_USER_TABLE . " as w, " .
		SURVEY_JOB_TABLE . " as j"; 
	$where = "c.change_set_id = {$change_set_id}
		and c.shift_id = s.id
		and c.worker_id = w.id
		and s.job_id = j.id";
	$order_by = "add_or_remove desc";
	$changes = sqlSelect($select, $from, $where, $order_by, (0), "changes in this change set"); 

	// Sort the meals by date (ascending)
	usort($changes, "meal_date_sort");
	if (0) deb("change_sets_utils.renderAssignmentsForm(): changes =", $changes);
	
	// Make the table header row
	$change_rows .= '
		<tr style="width:1px; white-space:nowrap;">
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;"><strong>meal date</strong></th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;"><strong>job</strong></th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;"><strong>action</strong></th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;"><strong>worker</strong></th>';
	if ($show_ok_checkbox) { 		
		$change_rows .= '
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px;"><strong>ok?</strong></th>';
	}
	$change_rows .= '
		</tr>';
	if (0) deb("change_sets_utils.renderAssignmentsForm(): change_rows =", $change_rows);
	
	// Make rows for the changes
	$ok_change_value = OK_CHANGE_VALUE;
	foreach($changes as $i=>$change) {
		$dt = new DateTime($change['meal_date']);
		$meal_date = $dt->format('M j (D)');

		$change_rows .= '
			<tr style="width:1px; white-space:nowrap;">
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $meal_date . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['job_name'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['add_or_remove'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['worker_name'] . '</td>';
		if ($show_ok_checkbox) { 		
			$change_rows .= '
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;"><input type="checkbox" name="' . $change['id'] . '" value="' . $ok_change_value . '"checked></td>';
		}
		$change_rows .= '
			</tr>';
	} 
	
	// Render the changes table
	$changes_table = <<<EOHTML
		<table style="table-layout:auto; width:1px; vertical-align:middle;" border="1" > 
		{$change_rows} 
		</table>
EOHTML;
	// $changes_table = <<<EOHTML
	// <table style="table-layout:auto; width:1px;"><tr>
		// <table style="table-layout:auto; width:1px; vertical-align:middle;" border="1" > 
		// {$change_rows} 
		// </table>
	// </tr></table>
// EOHTML;
	return $changes_table;
}

function saveAssignmentChanges($change_set_id, $posts) {

	if (0) deb("change_sets_untils:saveAssignmentChanges() post = ", $posts);

	// Get ids of confirmed changes from $posts into a query string
	$ok_changes_array = array();
	$ok_change_value = $posts['ok_change_value'];
	unset($posts['ok_change_value']);
	if (0) deb("change_sets_untils:saveAssignmentChanges() post = ", $posts);
	foreach($posts as $i=>$post) {
		if ($post == $ok_change_value) {
			$ok_changes_array[] = $i;
		}
	}
	$ok_changes_string = implode(", ", $ok_changes_array);
	if (0) deb("change_sets_untils:saveAssignmentChanges() ok_changes_string = ", $ok_changes_string);
	
	// If there are ok changes in the change set, save them in the database
	if ($ok_changes_string) {
		
		// Delete the not-ok changes from this change set
		sqlDelete(CHANGES_TABLE, "change_set_id = {$change_set_id} and not id in ({$ok_changes_string})");
		// sqlSelect("*", CHANGES_TABLE, "change_set_id = {$change_set_id} and not (id in ({$ok_changes_string}))", "", (0));

		// Set the change timestamp for this change set to now
		$when_saved = date("Y/m/d H:i:s");
		sqlUpdate(CHANGE_SETS_TABLE, "when_saved = '{$when_saved}'", "id = {$change_set_id}");

		// Store the confirmed changes in the assignments table
		$select = "c.*, s.scheduler_run_id";
		$from = CHANGES_TABLE . " as c, " . CHANGE_SETS_TABLE . " as s"; 
		$where = "s.id = {$change_set_id} 
			and c.change_set_id = s.id
			and c.id in ({$ok_changes_string})";
		$changes = sqlSelect($select, $from, $where, "", (0));
		foreach ($changes as $i=>$change) {
			if (0) deb("change_sets_untils:saveAssignmentChanges() change = ", $change);
			// $assignment_id = sqlSelect("id", ASSIGNMENTS_TABLE, )
			if ($change['add_or_remove'] == 'remove') {
				$set = "removed_by_change_id = {$change['id']}";
				$where = "shift_id = {$change['shift_id']}
					and worker_id = {$change['worker_id']}
					and season_id = " . SEASON_ID;
				sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment deletes");
			}
			if ($change['add_or_remove'] == 'add') {
				$columns = "shift_id, worker_id, season_id, scheduler_run_id, added_by_change_id";
				$values = "{$change['shift_id']}, {$change['worker_id']}, " . SEASON_ID . ", {$change['scheduler_run_id']}, {$change['id']}";
				sqlInsert(ASSIGNMENTS_TABLE, $columns, $values, (0), "assignment creates");
			}
		}	
	} 
	// If there are no ok changes in the change set, delete the whole change set 
	else 
	{
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0));
	}
}


// Delete change sets that were never saved. 
function purgeUnsavedChangeSets() {	
	$sets_to_purge = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id = " . scheduler_run()['id'] . " and when_saved is null", "", (0), "purgeUnsavedChangeSets()");
	sqlDelete(CHANGE_SETS_TABLE, "scheduler_run_id = " . scheduler_run()['id'] . " and when_saved is null", (0), "purge unsaved");
}

function undoChangeSets($undo_back_to_change_set_id, $post) {

	// Get the earliest change set in the series of change sets to undo
	$earliest_change_set = sqlSelect("*", CHANGE_SETS_TABLE, "id = {$undo_back_to_change_set_id}", "", (0), "earliest change set to undo")[0];
	// Get all the change sets to undo
	$where = "scheduler_run_id = {$earliest_change_set['scheduler_run_id']} and when_saved >= '{$earliest_change_set['when_saved']}'";
	$order_by = "when_saved desc";
	$change_sets = sqlSelect("*", CHANGE_SETS_TABLE, $where, $order_by, (0), "change sets to undo"); 
	
	// Undo all the selected change sets
	foreach($change_sets as $i=>$change_set) {
		$changes = sqlSelect("*", CHANGES_TABLE, "change_set_id = {$change_set['id']}", "", (0), "change to undo");
		foreach($changes as $j=>$change) {
			// Re-add assignments that were soft-deleted
			if ($change['add_or_remove'] == 'remove') {
				$set = "removed_by_change_id = NULL";
				$where = "shift_id = {$change['shift_id']}
					and worker_id = {$change['worker_id']}
					and season_id = " . SEASON_ID;
				sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment un-removes", TRUE);				
				sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0), "assignment to (re-) CREATE");			
			}
			// Delete assignments that were added
			if ($change['add_or_remove'] == 'add') {
				$where = "shift_id = {$change['shift_id']}
					and worker_id = {$change['worker_id']}
					and season_id = " . SEASON_ID;
				sqlDelete(ASSIGNMENTS_TABLE, $where, (0), "assignment un-adds", TRUE);
				sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0), "assignment to DELETE");				
			}
		}
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set['id']}", (0), "change set to delete", TRUE);
	}
}

?>