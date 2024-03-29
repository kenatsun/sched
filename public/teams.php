<?php
require_once "start.php";
if (0) deb("teams.php: after req start.php"); 
require_once "teams_utils.php";
if (0) deb("teams.php: after req teams_utils.php"); 
require_once "change_sets_utils.php";
if (0) deb("teams.php: after req change_sets_utils.php"); 
print '<script src="js/teams.js"></script>';

if (0) deb(">>>>> teams.php: start"); 
if (0) deb("teams.php: _POST = ", $_POST);
if (0) deb("teams.php: _GET = ", $_GET);
$controls_display = $_GET['controls_display'] ? $_GET['controls_display'] : "show";
$change_markers_display = $_GET['change_markers_display'] ? $_GET['change_markers_display'] : "show";
$edition = $_GET['edition'] ? $_GET['edition'] : "";

if ($_POST) {
	if (0) deb("teams.php: _POST = ", $_POST);
	$change_set_id = $_POST['change_set_id'];

	// Processing changes from change_set.php  

		// Update assignments table with the changes that user has confirmed 
		if (isset($_POST['confirm'])) {
			if (0) deb("teams.php: gonna confirm change_set_id = {$change_set_id}");
			saveAssignmentBasedOnChangeSet($change_set_id, $_POST);
		}

		// Delete change set that user wants to discard
		if (isset($_POST['discard'])) {
			if (0) deb("teams.php: gonna discard change_set_id = {$change_set_id}");
			sqlDelete(CHANGE_SETS_TABLE, "id = {$change_set_id}", (0)); 
		}
	
	// Processing changes from change_sets.php
	
		// Undo changes from all change sets including and after the one specified
		if (isset($_POST['undo_back_to_change_set_id'])) {
			$undo_back_to_change_set_id = $_POST['undo_back_to_change_set_id'];
			if (0) deb("teams.php: undo_back_to_change_set_id = {$undo_back_to_change_set_id}");
			undoChangeSets($undo_back_to_change_set_id, $_POST);
		}	

	// Processing request to publish the schedule
		if (isset($_POST['publish'])) {
			publishSchedule();
		}
	
	}

// Delete change sets of this scheduler run that were never saved.
purgeUnsavedChangeSets();  

if (0) deb("teams.php: before displaySchedule()" . since("before displaySchedule()")); 

displaySchedule($controls_display, $change_markers_display, $edition);

if (0) deb("<<<<< teams.php: end" . since()); 

?>