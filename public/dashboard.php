<?php
require_once "start.php";
require_once "dashboard_utils.php";
require_once "change_sets_utils.php";

if (0) deb("dashboard.php: start"); 
if (0) deb("dashboard.php: _POST = ", $_POST);
if (0) deb("dashboard.php: _GET = ", $_GET);
$controls_display = $_GET['controls_display'] ? $_GET['controls_display'] : "show";
$change_markers_display = $_GET['change_markers_display'] ? $_GET['change_markers_display'] : "show";

if ($_POST) {
	if (0) deb("dashboard.php: _POST = ", $_POST);
	$change_set_id = $_POST['change_set_id'];

	// Processing changes from change_set.php 

		// Update assignments table with the changes that user has confirmed 
		if (isset($_POST['confirm'])) {
			if (0) deb("dashboard.php: gonna confirm change_set_id = {$change_set_id}");
			saveAssignmentBasedOnChangeSet($change_set_id, $_POST);
		}

		// Delete change set that user wants to discard
		if (isset($_POST['discard'])) {
			if (0) deb("dashboard.php: gonna discard change_set_id = {$change_set_id}");
			sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0)); 
		}
	
	// Processing changes from change_sets.php
	
		// Undo changes from all change sets including and after the one specified
		if (isset($_POST['undo_back_to_change_set_id'])) {
			$undo_back_to_change_set_id = $_POST['undo_back_to_change_set_id'];
			if (0) deb("dashboard.php: undo_back_to_change_set_id = {$undo_back_to_change_set_id}");
			undoChangeSets($undo_back_to_change_set_id, $_POST);
		}	

	// Processing request to publish the schedule
		if (isset($_POST['publish'])) {
			publishSchedule();
		}
	
	}

// Delete change sets of this scheduler run that were never saved.
purgeUnsavedChangeSets();  

displaySchedule($controls_display, $change_markers_display);

?>