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


// Show change set from form data, get user confirmation
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
	$order_by = "action desc";
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
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['action'] . '</td>
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
	return $changes_table;
}


function insertChangeSet($posts) {
	if (0) deb("change_sets_utils.insertChangeSet(): POST = ", $posts); 
	
	// Insert the change set, and get its id
	$scheduler_run_id = scheduler_run()['id'];
	sqlInsert(CHANGE_SETS_TABLE, "scheduler_run_id", "{$scheduler_run_id}", (0));
	$change_set_id = sqlSelect("id", CHANGE_SETS_TABLE, "scheduler_run_id = {$scheduler_run_id}", "id desc", (0))[0]['id'];
	if (0) deb("change_sets_utils.insertChangeSet(): change_set_id = ", $change_set_id);
	
	
	// Insert remove requests into changes table
	if ($posts['remove']) {
		foreach ($posts['remove'] as $i=>$remove){
			if ($remove) {
				$assignment_id = $remove;
				$assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$assignment_id}", "", (0))[0];
				insertChange('remove', $assignment['worker_id'], $assignment['shift_id'], $change_set_id, $scheduler_run_id);		
				// $worker_id = $assignment['worker_id'];
				// $shift_id = $assignment['shift_id'];
				// $columns = "action, change_set_id, worker_id, shift_id";
				// sqlInsert(CHANGES_TABLE, $columns, "'remove', {$change_set_id}, {$worker_id}, {$shift_id}", (0));
				// insertChange('remove', $worker_id, $shift_id, $change_set_id, $prior_removed_by_change_id, $prior_added_by_change_id);	
			}
		}
	}

	// Insert add requests into changes table
	if ($posts['add']) {
		foreach ($posts['add'] as $j=>$add){
			if (0) deb("change_sets_utils.insertChangeSet(): add = ", $add);
			if ($add) {
				$worker_id = explode("_to_",$add)[0];
				$shift_id = explode("_to_",$add)[1];
				if (0) deb("change_sets_utils.insertChangeSet(): gonna add worker = $worker_id");
				insertChange('add', $worker_id, $shift_id, $change_set_id, $scheduler_run_id);		
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$worker_id}, {$shift_id}", (0));
			}
		}
	}

	// Insert move requests into changes table 
	if ($posts['move']) {
		foreach ($posts['move'] as $k=>$move){ 
			if (0) deb("change_sets_utils.insertChangeSet(): move = ", $move);
			if ($move) {
				// Get data on the existing assignment
				$assignment_id = explode("_to_",$move)[0];
				$assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$assignment_id}", "", (0))[0];
				// Move this worker to that shift
				$worker_id = $assignment['worker_id'];
				$from_shift_id = $assignment['shift_id'];
				$to_shift_id = explode("_to_",$move)[1];
				if (0) deb("change_sets_utils.insertChangeSet(): gonna move worker = $worker_id");
				insertChange('remove', $worker_id, $from_shift_id, $change_set_id, $scheduler_run_id);
				insertChange('add', $worker_id, $to_shift_id, $change_set_id, $scheduler_run_id);		
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {$worker_id}, {$from_shift_id}", (0));
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$worker_id}, {$to_shift_id}", (0));
			}
		}
	}

	// Insert trade requests into changes table 
	// $trades = array();
	if ($posts['trade']) {
		foreach ($posts['trade'] as $l=>$trade){ 
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
				insertChange('remove', $this_worker_id, $this_shift_id, $change_set_id, $scheduler_run_id);		
				insertChange('add', $this_worker_id, $that_shift_id, $change_set_id, $scheduler_run_id);		
				// Move that worker to this shift
				insertChange('remove', $that_worker_id, $that_shift_id, $change_set_id, $scheduler_run_id);		
				insertChange('add', $that_worker_id, $this_shift_id, $change_set_id, $scheduler_run_id);		
				// // Move this worker to that shift
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {$this_worker_id}, {$this_shift_id}", (0), "rem this from this");
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$this_worker_id}, {$that_shift_id}", (0), "add this to that");
				// // Move that worker to this shift
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'remove', {$change_set_id}, {$that_worker_id}, {$that_shift_id}", (0), "rem that from that");
				// sqlInsert(CHANGES_TABLE, "action, change_set_id, worker_id, shift_id", "'add', {$change_set_id}, {$that_worker_id}, {$this_shift_id}", (0), "add that to this");
			}
		}
	}
	
	if (0) deb("change_sets_utils.insertChangeSet(): change_set_id = ", $change_set_id); 

	return $change_set_id;
}


// Construct and store one change
function insertChange($action, $worker_id, $shift_id, $change_set_id, $scheduler_run_id) {

	$where = "shift_id = {$shift_id}
		and worker_id = {$worker_id}
		and scheduler_run_id = {$scheduler_run_id} 
		and season_id = " . SEASON_ID;
	$existing_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0), "existing assignment?")[0];
	if ($existing_assignment) {
		$removed_by_change_id = $assignment['removed_by_change_id'] ? $assignment['removed_by_change_id'] : "null";
		$added_by_change_id = $assignment['added_by_change_id'] ? $assignment['added_by_change_id'] : "null";
		if (0) deb("change_sets_utils.insertChange(): added_by_change_id = ", $added_by_change_id); 
		$columns = "action, worker_id, shift_id, change_set_id, prior_removed_by_change_id, prior_added_by_change_id";
		$values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, {$removed_by_change_id}, {$added_by_change_id}";
		if (0) deb("change_sets_utils.insertChange(): values = ", $values); 
	} else {
		$columns = "action, worker_id, shift_id, change_set_id";
		$values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}";
	}
	// $columns = "action, worker_id, shift_id, change_set_id, prior_removed_by_change_id, prior_added_by_change_id";
	// $values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, {$removed_by_change_id}, {$added_by_change_id}";
	sqlInsert(CHANGES_TABLE, $columns, $values, (0), "insert one change");
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

			// Get the existing assignment, if any
			$where = "shift_id = {$change['shift_id']}
				and worker_id = {$change['worker_id']}
				and season_id = " . SEASON_ID;
			$existing_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0))[0];
			if (0) deb("change_sets_untils:saveAssignmentChanges() existing_assignment = ", $existing_assignment);
	
			if ($change['action'] == 'remove') {
				// $where = "shift_id = {$change['shift_id']}
					// and worker_id = {$change['worker_id']}
					// and season_id = " . SEASON_ID;
				// $existing_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0))[0];
				// if (0) deb("change_sets_untils:saveAssignmentChanges() existing_assignment = ", $existing_assignment);
				// if ($existing_assignment) {
				// Mark assignment as removed by this change, and nullify its added_by mark
				$set = "removed_by_change_id = {$change['id']}, added_by_change_id = null";
				$where = "id = {$existing_assignment['id']}";
				sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment deletes");
				// }
			} elseif ($change['action'] == 'add') {
				// $where = "shift_id = {$change['shift_id']}
					// and worker_id = {$change['worker_id']}
					// and season_id = " . SEASON_ID;
				// $existing_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0), "existing assignment on add")[0];
				// if (0) deb("change_sets_untils:saveAssignmentChanges() existing_assignment = ", $existing_assignment);
				if ($existing_assignment) {
					// If assignment already exists, mark it as added by this change and nullify its removed_by mark 
					$set = "added_by_change_id = {$change['id']}, removed_by_change_id = null";
					$where = "id = {$existing_assignment['id']}";
					sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment deletes");					
				} else {
					// If assignment doesn't exist, create it and mark it as added by this change 
					$columns = "shift_id, worker_id, season_id, scheduler_run_id, added_by_change_id, generated";
					$values = "{$change['shift_id']}, {$change['worker_id']}, " . SEASON_ID . ", {$change['scheduler_run_id']}, {$change['id']}, 0";
					sqlInsert(ASSIGNMENTS_TABLE, $columns, $values, (0), "assignment creates");	 				
				}
			}
			// // Record the prior state of the assignment.xxxxed_by_change_id columns into the change record, to be restored in case of undo
			// if (0) deb("change_sets_untils:saveAssignmentChanges() existing_assignment = ", $existing_assignment);
			// if ($existing_assignment) {
				// $set = "prior_change_that_added_id = {$existing_assignment['added_by_change_id']},
					// prior_change_that_removed_id = {$existing_assignment['removed_by_change_id']}";
				// sqlUpdate(CHANGES_TABLE, $set, "id = {$change['id']}");
			// }
		}	
	} 
	// If the change set contains no ok changes, delete it 
	else 
	{
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0));
	}
}


// Delete change sets whose assignment changes were never saved. 
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
			// Get the assignment that's the target of this change
			$from = ASSIGNMENTS_TABLE;
			$where = "shift_id = {$change['shift_id']}
				and worker_id = {$change['worker_id']}
				and season_id = " . SEASON_ID;
			$assignment = sqlSelect("*", $from, $where, "", (0), "target assignment of this change");
			// $set = "removed_by_change_id = {$change['prior_removed_by_change_id']}, 
				// removed_by_change_id = {$change['prior_removed_by_change_id']}";
			// $where = "id = {$assignment['id']}";
			if ($change['action'] == 'add' && $change['prior_added_by_change_id'] == null) {
				// Hard-delete an assignment that did not exist before this change was saved
				$where = "id = {$assignment['id']}";
				sqlDelete(ASSIGNMENTS_TABLE, $where, (0), "assignment un-adds", TRUE);
			} else {
				// Restore prior state of an assignment that existed before this change was saved
				$set = "removed_by_change_id = {$change['prior_removed_by_change_id']}, 
					added_by_change_id = {$change['prior_added_by_change_id']}";
				$where = "id = {$assignment['id']}";
				sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment un-removes", TRUE);								
			}
			// // Restore prior state of an assignment that was soft-deleted by this change
			// if ($change['action'] == 'remove') {
				// sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment un-removes", TRUE);				
				// sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0), "assignment to (re-) CREATE");			
			// }
			// // Delete or soft-delete assignment that was added by this change
			// if ($change['action'] == 'add') {
				// if ($change['prior_added_by_change_id']) {
					// // Soft-delete an assignment that existed before this change
					// sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment un-removes", TRUE);								
				// } else {
					// // Hard-delete an assignment that did not exist before this change.
					// sqlDelete(ASSIGNMENTS_TABLE, $where, (0), "assignment un-adds", TRUE);
				// }	
			// }
		}
		// Delete the change_set that was just undone.
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set['id']}", (0), "change set to delete", TRUE);
	}
}

?>