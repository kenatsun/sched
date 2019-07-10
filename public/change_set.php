<?php

require_once "start.php";
require_once "change_sets_utils.php";


// Process request to save the change set
if (isset($_POST['save'])) {
	$change_set_id = saveChangeSet($_POST);
}

// Render the page
if (0) deb("change_set.php: change_set_id = $change_set_id");
if (0) deb("change_set.php: PREVIOUS_CRUMBS_IDS = " . PREVIOUS_CRUMBS_IDS . " CRUMBS_IDS = " . CRUMBS_IDS);
$headline = renderHeadline("Pending Changes");  
$changes_table = renderChangeSet($change_set_id, TRUE);
$ok_change_value = OK_CHANGE_VALUE;
$changes_form = '
	<form action="' . makeURI("dashboard.php", PREVIOUS_CRUMBS_IDS) . '" method="post">'
		. $changes_table .
		'<tr><td colspan={$ncols}><h2>&nbsp;&nbsp;&nbsp; <input type="submit" name="confirm" value="Confirm Changes"> <input type="submit" name="discard" value="Discard Changes"></h2> </td><tr>
		<input type="hidden" name="ok_change_value" id="ok_change_value" value="' . $ok_change_value . '"/> 
		<input type="hidden" name="change_set_id" id="change_set_id" value="' . $change_set_id. '" />
	</form>';

$page = <<<EOHTML
	{$headline}
	{$changes_form}
EOHTML;

print $page;

?>