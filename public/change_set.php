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

if (0) deb("change_set.php: POST = ", $_POST); 

if ($_POST) {
	$change_set_id = saveChangeSet($_POST);
}

$headline = renderHeadline("Pending Changes", HOME_LINK . ASSIGNMENTS_LINK); 
$changes_table = renderChangeSet($change_set_id, TRUE);
$ok_change_value = OK_CHANGE_VALUE;
$changes_form = <<<EOHTML
<form action="dashboard.php" method="post">
	{$changes_table}
	<tr><td colspan={$ncols}><h2>&nbsp;&nbsp;&nbsp; <input type="submit" name="confirm" value="Confirm Changes"> <input type="submit" name="discard" value="Discard Changes"></h2> </td><tr>
	<input type="hidden" name="ok_change_value" id="ok_change_value" value="{$ok_change_value}" /> 
	<input type="hidden" name="change_set_id" id="change_set_id" value="{$change_set_id}" />
</form>
EOHTML;

$page = <<<EOHTML
	{$headline}
	{$changes_form}
EOHTML;
print $page;



?>