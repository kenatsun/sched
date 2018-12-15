<?php

global $relative_dir; 
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}
require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
require_once "{$relative_dir}/config.php";
require_once "{$relative_dir}/globals.php";

if (0) deb("change_sets.php: _POST = ", $_POST);
 
define('OK_CHANGE_VALUE', 'ok_change');
if (0) deb("change_sets.php: OK_CHANGE_VALUE = ", OK_CHANGE_VALUE);

define('ADDED_COLOR', ' background:#ffff66; text-decoration:underline; ');		// Format for worker added to shift
define('REMOVED_COLOR', ' background:LightBlue; text-decoration:line-through; ');	// Format for worker removed from shift

// Show change set from form data, get user confirmation
// Render one change set
function renderChangeSet($change_set_id, $show_ok_checkbox=TRUE) {
	
	// Read changes in current change set from database
	$select = "c.*, m.date as meal_date, 
		w.first_name || ' ' || w.last_name as worker_name, 
		j.description as job_name";
	$from = SCHEDULE_SHIFTS_TABLE . " as s, " . 
		CHANGES_TABLE . " as c, " .  
		AUTH_USER_TABLE . " as w, " .
		SURVEY_JOB_TABLE . " as j, " .
		MEALS_TABLE . " as m "; 
	$where = "c.change_set_id = {$change_set_id}
		and c.shift_id = s.id
		and c.worker_id = w.id
		and s.job_id = j.id
		and s.meal_id = m.id";
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
				$background_color = ADDED_COLOR;
				break;
			case "remove":
				$background_color = REMOVED_COLOR;
				break;
			default:
				$background_color = "White";
		}
		if (0) deb("change_sets_utils.renderChangeSet(): background_color = ", $background_color); 

		$change_rows .= '
			<tr style="width:1px; white-space:nowrap;">
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $meal_date . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['job_name'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; ' . $background_color . '">' . $change['action'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; ' . $background_color . ';">' . $change['worker_name'] . '</td>'; 
		// $change_rows .= '
			// <tr style="width:1px; white-space:nowrap;">
				// <td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $meal_date . '</td>
				// <td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['job_name'] . '</td>
				// <td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; background:' . $background_color . ';">' . $change['action'] . '</td>
				// <td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; background:' . $background_color . ';">' . $change['worker_name'] . '</td>';
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


// Functions to save change sets ...............................

function saveChangeSet($posts) {
	if (0) deb("change_sets_utils.saveChangeSet(): POST = ", $posts); 
	
	// Insert the change set, and get its id
	$scheduler_run_id = scheduler_run()['id'];
	sqlInsert(CHANGE_SETS_TABLE, "scheduler_run_id", "{$scheduler_run_id}", (0));
	$change_set_id = sqlSelect("id", CHANGE_SETS_TABLE, "scheduler_run_id = {$scheduler_run_id}", "id desc", (0))[0]['id'];
	if (0) deb("change_sets_utils.saveChangeSet(): change_set_id = ", $change_set_id);
	
	
	// Insert remove requests into changes table
	if ($posts['remove']) {
		foreach ($posts['remove'] as $i=>$remove){
			if ($remove) {
				$assignment_id = $remove;
				$assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$assignment_id}", "", (0))[0];
				saveChange('remove', $assignment['worker_id'], $assignment['shift_id'], $change_set_id, $scheduler_run_id);		
			}
		}
	}

	// Insert add requests into changes table
	if ($posts['add']) {
		foreach ($posts['add'] as $j=>$add){
			if (0) deb("change_sets_utils.saveChangeSet(): add = ", $add);
			if ($add) {
				$worker_id = explode("_to_",$add)[0];
				$shift_id = explode("_to_",$add)[1];
				if (0) deb("change_sets_utils.saveChangeSet(): gonna add worker = $worker_id");
				saveChange('add', $worker_id, $shift_id, $change_set_id, $scheduler_run_id);		
			}
		}
	}

	// Insert move requests into changes table 
	if ($posts['move']) {
		foreach ($posts['move'] as $k=>$move){ 
			if (0) deb("change_sets_utils.saveChangeSet(): move = ", $move);
			if ($move) {
				// Get data on the existing assignment
				$assignment_id = explode("_to_",$move)[0];
				$assignment = sqlSelect("*", ASSIGNMENTS_TABLE, "id={$assignment_id}", "", (0))[0];
				// Move this worker to that shift
				$worker_id = $assignment['worker_id'];
				$from_shift_id = $assignment['shift_id'];
				$to_shift_id = explode("_to_",$move)[1];
				if (0) deb("change_sets_utils.saveChangeSet(): gonna move worker = $worker_id");
				saveChange('remove', $worker_id, $from_shift_id, $change_set_id, $scheduler_run_id);
				saveChange('add', $worker_id, $to_shift_id, $change_set_id, $scheduler_run_id);		
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
				saveChange('remove', $this_worker_id, $this_shift_id, $change_set_id, $scheduler_run_id);		
				saveChange('add', $this_worker_id, $that_shift_id, $change_set_id, $scheduler_run_id);		
				// Move that worker to this shift
				saveChange('remove', $that_worker_id, $that_shift_id, $change_set_id, $scheduler_run_id);		
				saveChange('add', $that_worker_id, $this_shift_id, $change_set_id, $scheduler_run_id);
			}
		}
	}
	
	if (0) deb("change_sets_utils.saveChangeSet(): change_set_id = ", $change_set_id); 

	return $change_set_id;
}


// Construct and store one change 
function saveChange($action, $worker_id, $shift_id, $change_set_id, $scheduler_run_id) {

	$where = "shift_id = {$shift_id}
		and worker_id = {$worker_id}
		and scheduler_run_id = {$scheduler_run_id} 
		and season_id = " . SEASON_ID;
	$existing_assignment = sqlSelect("*", ASSIGNMENTS_TABLE, $where, "", (0), "existing assignment?")[0];
	if ($existing_assignment) {
		$columns = "action, worker_id, shift_id, change_set_id";
		$values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}";
	} else {
		$columns = "action, worker_id, shift_id, change_set_id"; 
		$values = "'{$action}', {$worker_id}, {$shift_id}, {$change_set_id}"; 
	}
	if (0) deb("change_sets_utils.saveChange(): values = ", $values); 
	sqlInsert(CHANGES_TABLE, $columns, $values, (0), "insert one change");
}


// Functions to save changes to assignments based on change sets ...............................

function saveAssignmentBasedOnChangeSet($change_set_id, $posts) {
	
	if (0) deb("change_sets_utils:saveAssignmentBasedOnChangeSet() post = ", $posts);

	// Get ids of confirmed changes from $posts into a query string
	$ok_changes_array = array();
	$ok_change_value = $posts['ok_change_value'];
	unset($posts['ok_change_value']);
	if (0) deb("change_sets_utils:saveAssignmentBasedOnChangeSet() post = ", $posts);
	foreach($posts as $i=>$post) {
		if ($post == $ok_change_value) {
			$ok_changes_array[] = $i;
		}
	}
	$ok_changes_string = implode(", ", $ok_changes_array);
	if (0) deb("change_sets_utils:saveAssignmentBasedOnChangeSet() ok_changes_string = ", $ok_changes_string);
	
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
			if (0) deb("change_sets_utils:saveAssignmentBasedOnChangeSet() change = ", $change);			
			saveAssignmentBasedOnChange($change);
		}	
	} 
	// If the change set contains no ok changes, delete it 
	else 
	{
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0));
	}
}

function saveAssignmentBasedOnChange($change) {	

	$change_set = sqlSelect("*", CHANGE_SETS_TABLE, "id = {$change['change_set_id']}", "", (0), "saveAssignmentBasedOnChange(): scheduler_run")[0];
	// Get existing assignment, if any
	$select = "*";
	$from = ASSIGNMENTS_TABLE;
	$where = "shift_id = {$change['shift_id']}
		and worker_id = {$change['worker_id']}";
	$existing_assignment = sqlSelect($select, $from, $where, "", (0))[0];
	if (0) deb("change_sets_utils.saveAssignmentBasedOnChange(): change['action'] = {$change['action']}");
	
	// A change is always recorded by inserting a new record into assignment_states
	$columns = "id, when_last_changed, latest_change_id, shift_id, worker_id, season_id, scheduler_run_id, generated, exists_now";
	// If assignment record exists, insert a new assignment_state with the existing assignment's id
	if ($existing_assignment) {
		$values = "{$existing_assignment['id']}, '{$change_set['when_saved']}', {$change['id']}, {$change['shift_id']}, {$change['worker_id']}, " . SEASON_ID . ", {$change_set['scheduler_run_id']}, {$existing_assignment['generated']}, " . ($change['action'] == "add" ? 1 : 0);
	} 
	// Else insert a new assignment_state with a new id and generated = 0 (thus creating a new assignment)
	// Note: The action should always be "add" on an assignment whose record doesn't already exist. 
	// Maybe there should be an error trap here in case the UI lets in a bad case.
	else {
		$values = autoIncrementId(ASSIGNMENT_STATES_TABLE) . ", " . "'{$change_set['when_saved']}', {$change['id']}, {$change['shift_id']}, {$change['worker_id']}, " . SEASON_ID . ", {$change_set['scheduler_run_id']}, 0, 1";
	}
	sqlInsert(ASSIGNMENT_STATES_TABLE, $columns, $values, (0), "saveAssignmentBasedOnChange(): add an assignment state");	 				
}

// Delete change sets whose assignment changes were never saved. 
function purgeUnsavedChangeSets() {	

	$sets_to_purge = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id = " . scheduler_run()['id'] . " and when_saved is null", "", (0), "purgeUnsavedChangeSets()");
	foreach ($sets_to_purge as $i=>$set_to_purge) {
		sqlDelete(CHANGES_TABLE, "change_set_id = {$set_to_purge['id']}", (0), "purgeUnsavedChangeSets(): deleting changes in change set");
		sqlDelete(CHANGE_SETS_TABLE, "id = {$set_to_purge['id']}", (0), "purgeUnsavedChangeSets(): deleting change set");
	}
}

// Undo selected change sets
function undoChangeSets($undo_back_to_change_set_id, $post) {

	if (!isset($post['undo'])) return;  // Don't undo changes unless the "undo" button was pushed

	// Get the earliest change set in the series of change sets to undo
	$earliest_change_set = sqlSelect("*", CHANGE_SETS_TABLE, "id = {$undo_back_to_change_set_id}", "", (0), "earliest change set to undo")[0];

	// Get all the change sets to undo
	$select = "*";
	$from = CHANGE_SETS_TABLE;
	$where = "scheduler_run_id = {$earliest_change_set['scheduler_run_id']} 
		and when_saved >= '{$earliest_change_set['when_saved']}'";
	$order_by = "when_saved desc";
	$change_sets_to_undo = sqlSelect($select, $from, $where, $order_by, (0), "change sets to undo"); 
	
	// Undo each selected change set
	foreach($change_sets_to_undo as $i=>$change_set_to_undo) {
		$changes_to_undo = sqlSelect("*", CHANGES_TABLE, "change_set_id = {$change_set_to_undo['id']}", "", (0), "changes to undo");
		// Delete the assignment_states record that was created by each change
		foreach($changes_to_undo as $j=>$change_to_undo) {			
			$from = ASSIGNMENT_STATES_TABLE;
			$where = "
				shift_id = {$change_to_undo['shift_id']}
				and worker_id = {$change_to_undo['worker_id']}
				and when_last_changed = (
					select max(aa.when_last_changed)
					from " . ASSIGNMENT_STATES_TABLE . " as aa
					where aa.id = id
					)";
			sqlDelete($from, $where, (0), "undoChange(): delete action");
			// undoChange($change_to_undo);
		}

		// Delete the change_set that was just undone.
		sqlDelete(CHANGES_TABLE, "change_set_id = {$change_set_to_undo['id']}", (0), "delete changes from undone change set", TRUE);
			// The above explicit delete seems to be needed because database cascading delete sometimes doesn't work.
		sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_to_undo['id']}", (0), "delete undone change set", TRUE);
	}
}

?>