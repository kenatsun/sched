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
	if (0) deb("change_sets_utils.renderChangeSet(): changes =", $changes);
	
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
	if (0) deb("change_sets_utils.renderChangeSet(): change_rows =", $change_rows);
	
	// Make rows for the changes
	$ok_change_value = OK_CHANGE_VALUE;
	foreach($changes as $i=>$change) {
		$dt = new DateTime($change['meal_date']);
		$meal_date = $dt->format('M j (D)');
		switch ($change['action']) {
			case "add":
				$background_color = "LightGreen";
				break;
			case "remove":
				$background_color = "Pink";
				break;
			default:
				$background_color = "White";
		}
		if (0) deb("change_sets_utils.renderChangeSet(): background_color = ", $background_color); 

		$change_rows .= '
			<tr style="width:1px; white-space:nowrap;">
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $meal_date . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['job_name'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; background:' . $background_color . ';">' . $change['action'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; background:' . $background_color . ';">' . $change['worker_name'] . '</td>';
		if ($show_ok_checkbox) { 		
			$change_rows .= '
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; background:' . $background_color . ';"><input type="checkbox" name="' . $change['id'] . '" value="' . $ok_change_value . '"checked></td>';
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


// Functions to save change sets

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
		// $prior_action = $assignment['latest_action'] ? $assignment['latest_action'] : "null"; 
		// $columns = "action, worker_id, shift_id, change_set_id, prior_action, existed, prior_change_id";
		// $values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, {$prior_action}, {$existing_assignment['exists_now']}, {$existing_assignment['latest_change_id']}";
		// $columns = "action, worker_id, shift_id, change_set_id, prior_action, existed";
		// $values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, {$prior_action}, {$existing_assignment['exists_now']}";
		$columns = "action, worker_id, shift_id, change_set_id, existed";
		$values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, {$existing_assignment['exists_now']}";
		if (0) deb("change_sets_utils.insertChange(): values = ", $values); 
	} else {
		// $columns = "action, worker_id, shift_id, change_set_id, prior_action, existed"; 
		// $values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, null, 0";
		$columns = "action, worker_id, shift_id, change_set_id, existed"; 
		$values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}, 0"; 
	}
	sqlInsert(CHANGES_TABLE, $columns, $values, (0), "insert one change");
}


// Functions to save changes to assignments based on change sets

function saveAssignmentChangeSet($change_set_id, $posts) {

	if (0) deb("change_sets_utils:saveAssignmentChangeSet() post = ", $posts);

	// Get ids of confirmed changes from $posts into a query string
	$ok_changes_array = array();
	$ok_change_value = $posts['ok_change_value'];
	unset($posts['ok_change_value']);
	if (0) deb("change_sets_utils:saveAssignmentChangeSet() post = ", $posts);
	foreach($posts as $i=>$post) {
		if ($post == $ok_change_value) {
			$ok_changes_array[] = $i;
		}
	}
	$ok_changes_string = implode(", ", $ok_changes_array);
	if (0) deb("change_sets_utils:saveAssignmentChangeSet() ok_changes_string = ", $ok_changes_string);
	
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
			if (0) deb("change_sets_utils:saveAssignmentChangeSet() change = ", $change);			
			saveAssignmentChange($change);
		}	
	} 
	// If the change set contains no ok changes, delete it 
	else 
	{
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0));
	}
}

function saveAssignmentChange($change) {
	
	$change_id = ($change['id'] ? $change['id'] : "null");
	// Get existing assignment, if any
	$select = "*";
	$from = ASSIGNMENTS_TABLE;
	$where = "shift_id = {$change['shift_id']}
		and worker_id = {$change['worker_id']}
		and season_id = " . SEASON_ID;
	$existing_assignment = sqlSelect($select, $from, $where, "", (0))[0];
	if (0) deb("change_sets_utils.saveAssignmentChange(): change['action'] = {$change['action']}");
	
	// If assignment record exists, update or delete it per this change
	if ($existing_assignment) {
		$new_exists_value = ($change['action'] == "add" ? 1 : 0);
		$set = "latest_change_id = {$change_id}, exists_now = {$new_exists_value}";
		$where = "id = {$existing_assignment['id']}";
		sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment record update");
	} 
	// If not, the change action should be "add", so create the assignment record.
	else {
		$columns = "shift_id, worker_id, season_id, scheduler_run_id, latest_change_id, generated, exists_now";
		$values = "{$change['shift_id']}, {$change['worker_id']}, " . SEASON_ID . ", {$change['scheduler_run_id']}, {$change_id}, 0, 1";
		sqlInsert(ASSIGNMENTS_TABLE, $columns, $values, (0), "add a non-generated assignment");	 				
	}
	// Else do nothing.  There should never be a "remove" action on an assignment whose record doesn't already exist. 
	// Maybe there should be an error message here in case the UI lets in a bad case.
}

// Delete change sets whose assignment changes were never saved. 
function purgeUnsavedChangeSets() {	
	$sets_to_purge = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id = " . scheduler_run()['id'] . " and when_saved is null", "", (0), "purgeUnsavedChangeSets()");
	sqlDelete(CHANGE_SETS_TABLE, "scheduler_run_id = " . scheduler_run()['id'] . " and when_saved is null", (0), "purge unsaved");
}


// Functions to undo changes to assignments based on change sets

function undoChangeSets($undo_back_to_change_set_id, $post) {

	// Get the earliest change set in the series of change sets to undo
	$earliest_change_set = sqlSelect("*", CHANGE_SETS_TABLE, "id = {$undo_back_to_change_set_id}", "", (0), "earliest change set to undo")[0];

	// Get all the change sets to undo
	$select = "*";
	$from = CHANGE_SETS_TABLE;
	$where = "scheduler_run_id = {$earliest_change_set['scheduler_run_id']} 
		and when_saved >= '{$earliest_change_set['when_saved']}'";
	$order_by = "when_saved desc";
	$change_sets_to_undo = sqlSelect($select, $from, $where, $order_by, (0), "change sets to undo"); 
	
	// Undo all the selected change sets
	foreach($change_sets_to_undo as $i=>$change_set_to_undo) {
		$changes_to_undo = sqlSelect("*", CHANGES_TABLE, "change_set_id = {$change_set_to_undo['id']}", "", (1), "changes to undo");
		foreach($changes_to_undo as $j=>$change_to_undo) {			
			// // Construct an inverse change
			// $undoer = array();
			// $undoer['action'] = ($change_to_undo['action'] == "remove" ? "add" : "remove");
			// // $undoer['action'] = ($change_to_undo['prior_action'] == "remove" ? "add" : "remove");
			// $undoer['shift_id'] = $change_to_undo['shift_id'];
			// $undoer['worker_id'] = $change_to_undo['worker_id'];
			// $undoer['scheduler_run_id'] = $change_to_undo['scheduler_run_id'];  // this attribute doesn't exist, or now it does
			// $undoer['existed'] = $change_to_undo['existed'];
			// if (0) deb("change_sets_utils:saveAssignmentChangeSet() undoer = ", $undoer);			
			// // Undo by saving that change
			// undoChange($undoer);
			undoChange($change_to_undo);
		}

		// Delete the change_set that was just undone.
		sqlDelete(CHANGES_TABLE, "change_set_id = {$change_set_to_undo['id']}", (0), "delete changes from undone change set", TRUE);
			// The above explicit delete seems to be needed because database cascading delete sometimes doesn't work.
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_to_undo['id']}", (0), "delete undone change set", TRUE);
	}
}


function undoChange($change_to_undo) {
	
	// $change_id = ($change['id'] ? $change['id'] : "null");
	// Get existing assignment that was changed by the change that is to be undone
	$select = "*";
	$from = ASSIGNMENTS_TABLE;
	$where = "shift_id = {$change_to_undo['shift_id']}
		and worker_id = {$change_to_undo['worker_id']}
		and season_id = " . SEASON_ID;
	$existing_assignment = sqlSelect($select, $from, $where, "", (1), "assignment to undo")[0];
	if (0) deb("change_sets_utils.saveAssignmentChange(): change['action'] = {$change_to_undo['action']}");
	
	$new_exists_value = ($change_to_undo['action'] == "add" ? 0 : 1);
	// $new_exists_value = ($change['action'] == "add" ? 1 : 0);
	$where = "id = {$existing_assignment['id']}";
	// If this change is to remove an assignment that didn't exist before, delete the assignment record.
	// if ($change['action'] == "remove" && !$existing_assignment['generated']) {
	if (1) deb("change_sets_utils.saveAssignmentChange(): new_exists_value = $new_exists_value, existing_assignment['generated'] = {$existing_assignment['generated']}");
	if ($new_exists_value == 0 && !$existing_assignment['generated']) {
		sqlDelete(ASSIGNMENTS_TABLE, $where, (1), "assignment record delete");
	}
	// Else this change is either adding or removing an assignment whose record already exists,
	// so just update the existence status of this record.
	else {
		// $set = "latest_change_id = {$change_id}, exists_now = {$new_exists_value}";
		$set = "exists_now = {$new_exists_value}";
		$where = "id = {$existing_assignment['id']}";
		sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (1), "assignment record update");
	}

	// // If assignment record exists, update or delete it per this change
	// if ($existing_assignment) {
		// $new_exists_value = ($change['action'] == "add" ? 1 : 0);
		// $where = "id = {$existing_assignment['id']}";
		// // If this change is to remove an assignment that didn't exist before, delete the assignment record.
		// if ($change['action'] == "remove" && !$existing_assignment['generated']) {
			// sqlDelete(ASSIGNMENTS_TABLE, $where, (0), "assignment record delete");
		// }
		// // Else this change is either adding or removing an assignment whose record already exists,
		// // so just update the existence status of this record.
		// else {
			// $set = "latest_change_id = {$change_id}, exists_now = {$new_exists_value}";
			// $where = "id = {$existing_assignment['id']}";
			// sqlUpdate(ASSIGNMENTS_TABLE, $set, $where, (0), "assignment record update");
		// }
	// } 
	// // If not, and if the change action is "add", create the assignment record.
	// elseif ($change['action'] == "add" ) {
		// $columns = "shift_id, worker_id, season_id, scheduler_run_id, latest_change_id, generated, exists_now";
		// $values = "{$change['shift_id']}, {$change['worker_id']}, " . SEASON_ID . ", {$change['scheduler_run_id']}, {$change_id}, 0, 1";
		// sqlInsert(ASSIGNMENTS_TABLE, $columns, $values, (0), "add a non-generated assignment");	 				
	// }
	// // Else do nothing.  There should never be a "remove" action on a nonexistent assignment, except during "undo".
}


?>