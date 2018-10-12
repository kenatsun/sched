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

$scheduler_run_id = scheduler_run()['id'];
if (0) deb("change_sets.php: scheduler_run_id = {$scheduler_run_id}");

// Delete change sets of this scheduler run that were never saved.
purgeUnsavedChangeSets();

// Link back to dashboard
$dashboard_link = '
	<p class="summary_report"><strong><a href="dashboard.php">View Dashboard</a></strong></p>
';

$headline = renderHeadline("Saved Change Sets (newest first)"); 
$change_sets = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id = {$scheduler_run_id}", "when_saved desc", (0));

// $change_sets_table = '<table style="table-layout:auto; width:1px; white-space: nowrap;">';
$change_sets_table = '<table style="table-layout:auto; width:1px; border-spacing: 0px; border-style: solid; border-width: 1px; border-color:LightGray;" >'; 
// Render change sets table header
$change_sets_table .= '
	<tr style="border-style: solid; border-width: 1px;">
		<th style="border-style: solid; border-width: 1px; text-align:center; border-color:LightGray; padding:8px; white-space: nowrap; font-weight: bold; font-size: 150%;">change set</th>
		<th style="border-style: solid; border-width: 1px; text-align:center; border-color:LightGray; padding:8px; white-space: nowrap; font-weight: bold; font-size: 150%;">saved at</th>
		<th style="border-style: solid; border-width: 1px; text-align:center; border-color:LightGray; padding:8px; white-space: nowrap; font-weight: bold; font-size: 150%;">undo?</th>
	</tr>';
foreach($change_sets as $i=>$change_set) {
	$dt = new DateTime($change_set['when_saved']);
	$saved_date = $dt->format('F j');
	$saved_time = $dt->format('g:i a');
	$change_sets_table .= '
		<tr>
			<td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px;">' . renderChangeSet($change_set['id'], FALSE) . '</td>
			<td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; white-space: nowrap;">' . $saved_date . '<br>' . $saved_time . '</td>
			<td style="border-style: solid; border-width: 1px; vertical-align:middle; border-color:LightGray; padding:8px; text-align: center"><input type="radio" name="undo_back_to_change_set_id" value="' . $change_set['id'] . '"></td>
		</tr>';
}

// $changes_table .= "<h4>Changes saved at {$change_set['when_saved']}</h4>";
// $change_set_table = renderChangeSet($change_set['id'], FALSE);
// $changes_table = "";
// foreach($change_sets as $i=>$change_set) {
	// $changes_table .= "<h3>Changes saved at {$change_set['when_saved']}</h3>";
	// $changes_table .= renderChangeSet($change_set['id'], FALSE);	
// }

$change_sets_table .= '</table>';

$change_sets_form = <<<EOHTML
	<form action="dashboard.php" method="post">
		{$change_sets_table}
		<input type="submit" name="undo" value="Undo Changes"> <input type="submit" name="no_undo" value="Don't Undo Changes">
	</form>	
EOHTML;
	
		// <input type="hidden" name="ok_change_value" id="ok_change_value" value="{$ok_change_value}" /> 
		// <input type="hidden" name="change_set_id" id="change_set_id" value="{$change_set_id}" />

$page = <<<EOHTML
	{$headline}
	{$change_sets_form}
	{$dashboard_link}
EOHTML;
print $page;


?>