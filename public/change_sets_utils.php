<?php

require_once "start.php";

if (0) deb("change_set_utils.php: _POST = ", $_POST);
 
define('OK_CHANGE_VALUE', 'ok_change');
if (0) deb("change_set_utils.php: OK_CHANGE_VALUE = ", OK_CHANGE_VALUE);

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
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px; background-color:white;"><strong>meal date</strong></th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px; background-color:white;"><strong>job</strong></th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px; background-color:white;"><strong>action</strong></th>
			<th style="width:1px; white-space:nowrap; text-align:center; padding:4px; background-color:white;"><strong>worker</strong></th>';
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
				$background_decoration = ADDED_DECORATION;
				$change_icon = ADDED_ICON;
				break;
			case "remove":
				$background_color = REMOVED_COLOR;
				$background_decoration = REMOVED_DECORATION;
				$change_icon = REMOVED_ICON;
				break;
			default:
				$background_color = "White";
				$change_icon = "";
		}
		if (0) deb("change_sets_utils.renderChangeSet(): background_color = ", $background_color); 

		$change_rows .= '
			<tr style="width:1px; white-space:nowrap;">
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $meal_date . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px;">' . $change['job_name'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; ' . $background_color . '">' . $change_icon . ' ' . $change['action'] . '</td>
				<td style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; ' . $background_color . ';">' . $change['worker_name'] . '</td>'; 
		if ($show_ok_checkbox) { 		
			$change_rows .= '
				<td 
					style="width:1px; white-space:nowrap; vertical-align:middle; padding:4px; background:' . $background_color . ';"
				>
					<input 
						type="checkbox" 
						name="' . $change['id'] . '" 
						value="' . $ok_change_value . '" checked
					>
				</td>
				';
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

	if (!$changes) {
		$changes_table = "<br>There are no pending (unsaved) changes right now.";
		if (0) deb("change_sets_utils.renderChangeSet(): no changes");
	}

	return $changes_table;
}


///////////////////////////////////////////////////////////// DATABASE FUNCTIONS

function saveChangeSet($posts) {
	if (0) deb("change_sets_utils.saveChangeSet(): POST = ", $posts); 
	
	// Insert the change set, and get its id
	$scheduler_run_id = scheduler_run()['id'];
	sqlInsert(CHANGE_SETS_TABLE, "scheduler_run_id", "{$scheduler_run_id}", (0));
	$change_set_id = sqlSelect("id", CHANGE_SETS_TABLE, "scheduler_run_id = {$scheduler_run_id}", "id desc", (0))[0]['id'];
	if (0) deb("change_sets_utils.saveChangeSet(): change_set_id = ", $change_set_id);
	
	
	// Insert REMOVE requests into changes table
	// Request syntax: "action:remove this_shift:<shift_id> this_worker:<worker_id>"
	if ($posts['remove']) {
		foreach ($posts['remove'] as $remove){
			if ($remove) {
				$request = parseChangeRequest($remove);
				saveChange('remove', $request['this_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);		
			}
		}
	}

	// Insert ADD requests into changes table
	// Request syntax: "action:add this_shift:<shift_id> this_worker:<worker_id>"
	if ($posts['add']) {
		foreach ($posts['add'] as $j=>$add){
			if ($add) {
				$request = parseChangeRequest($add);
				if (0) deb("change_sets_utils.saveChangeSet(): args_array = ", $args_array);
				saveChange('add', $request['this_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);		
			}
		}
	}

	// Insert MOVEOUT requests into changes table 
	// Request syntax: "action:moveout this_shift:<shift_id> this_worker:<worker_id> that_shift:<other_shift_id>"
	if ($posts['moveout']) {
		foreach ($posts['moveout'] as $moveout){ 
			if (0) deb("change_sets_utils.saveChangeSet(): moveout = ", $moveout);
			if ($moveout) {
				$request = parseChangeRequest($moveout);
				saveChange('remove', $request['this_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);
				// saveChange('add', $request['worker'], $request['to'], $change_set_id, $scheduler_run_id);		
			}
		}
	}

	// Insert MOVEIN requests into changes table 
	// Request syntax: "action:movein this_shift:<shift_id> this_worker:<worker_id> that_shift:<other_shift_id>"
	if ($posts['movein']) {
		foreach ($posts['movein'] as $movein){ 
			if (0) deb("change_sets_utils.saveChangeSet(): movein = ", $movein);
			if ($movein) {
				$request = parseChangeRequest($movein);
				saveChange('add', $request['this_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);
				// saveChange('add', $request['worker'], $request['to'], $change_set_id, $scheduler_run_id);		
			}
		}
	}

	// Insert TRADE requests into changes table 
	// Request syntax: "action:trade this_shift:<shift_id> this_worker:<worker_id> that_shift:<other_shift_id> that_worker:<other worker_id>"
	if ($posts['trade']) {
		foreach ($posts['trade'] as $trade){ 
			if ($trade) {
				$request = parseChangeRequest($trade);
				// Add that worker to this shift
				saveChange('add', $request['that_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);		
				// Remove this worker from this shift
				saveChange('remove', $request['this_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);		

				// // Move this worker to that shift
				// saveChange('add', $request['this_worker'], $request['that_shift'], $change_set_id, $scheduler_run_id);
				// saveChange('remove', $request['this_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);		
				// // Move that worker to this shift
				// saveChange('add', $request['that_worker'], $request['this_shift'], $change_set_id, $scheduler_run_id);		
				// saveChange('remove', $request['that_worker'], $request['that_shift'], $change_set_id, $scheduler_run_id);
			}
		}
	}
	
	if (0) deb("change_sets_utils.saveChangeSet(): change_set_id = ", $change_set_id); 

	return $change_set_id;
}


// Parse one assignment change request into an associative array
function parseChangeRequest($request) {
	$parts = explode(" ", $request);
	if (0) deb("change_sets_utils.parseChangeRequest(): parts = ", $parts);
	foreach($parts as $part) {
		$arr_elt = explode(":", $part);
		$request_array[$arr_elt[0]] = $arr_elt[1];
	}
	if (0) deb("change_sets_utils.parseChangeRequest(): request_array = ", $request_array);
	return $request_array;
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
	
	if (0) deb("change_sets_utils:saveAssignmentBasedOnChangeSet() posts = ", $posts);

	// Get ids of confirmed changes from $posts into a query string
	$ok_changes_array = array();
	$ok_change_value = $posts['ok_change_value'];
	unset($posts['ok_change_value']);
	if (0) deb("change_sets_utils:saveAssignmentBasedOnChangeSet() posts = ", $posts);
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
		$changes = sqlSelect($select, $from, $where, "", (0), "changed assignments to save");
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