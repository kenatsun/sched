<?php
/*
Populate the coworker_requests table from the  avoids and prefers columns of the existing work_prefs table
The latter is assumed to contain the definitive version of these requests,
so it's OK to first delete everything from coworker_preferences then reconstruct it completely.
*/

require_once 'constants.inc';
require_once 'globals.php';
require_once 'utils.php';

$work_prefs_table = SCHEDULE_COMMENTS_TABLE;
$coworker_requests_table = SCHEDULE_COWORKER_REQUESTS_TABLE;
$workers_table = AUTH_USER_TABLE;
$season_id = SEASON_ID;

if (1) deb("*********************************************************************");

$select = "p.worker_id, p.avoids, p.prefers, p.season_id";
$from = "{$work_prefs_table} as p";
$where = "";
$order_by = "p.season_id, p.worker_id";
$work_prefs = sqlSelect($select, $from, $where, $order_by, (0));

if (1) {  // write existing requests to debug
	$select = "w.username as requester, r.request, c.username as coworker, w.id as r_id, c.id as c_id, r.season_id as season";
	$from = "{$coworker_requests_table} as r, {$workers_table} as w, {$workers_table} as c";
	$where = "r.requester_id = w.id and r.coworker_id = c.id";
	$order_by = "r.request, w.username";
	$requests = sqlSelect($select, $from, $where, $order_by, (0));
	deb("survey.saveRequests(): coworker_requests before update = ", $requests);
}
	
// Delete existing coworker_requests.
$rows_affected = sqlDelete("{$coworker_requests_table}", "", (0));
if (0) deb("survey.saveRequests(): request rows deleted = {$rows_affected}");

foreach ($work_prefs as $k=>$work_pref) {
	$coworkers = explode(",", $work_pref['avoids']);
	$request = 'avoid';
	$requester_id = $work_pref['worker_id'];
	foreach ($coworkers as $ckey=>$coworker) {
		// Get the id of the coworker
		$coworker_id = sqlSelect("id", "{$workers_table} as w", "w.username = '{$coworker}'", "", (0));
		// Insert the coworker request
		$table = $coworker_requests_table;
		$columns = "request, requester_id, coworker_id, season_id";
		$values = "'{$request}', {$requester_id}, {$coworker_id[0]['id']}, {$work_pref['season_id']}";
		$rows_affected = sqlInsert($table, $columns, $values, (0));
		if (0) deb("survey.saveRequests(): request rows inserted = ", $rows_affected);
	}
	
	$request = 'prefer';
	$requester_id = $work_pref['worker_id'];
	foreach ($coworkers as $ckey=>$coworker) {
		// Get the id of the coworker
		$coworker_id = sqlSelect("id", "{$workers_table} as w", "w.username = '{$coworker}'", "", (0));
		// Insert the coworker request
		$table = $coworker_requests_table;
		$columns = "request, requester_id, coworker_id, season_id";
		$values = "'{$request}', {$requester_id}, {$coworker_id[0]['id']}, {$work_pref['season_id']}";
		$rows_affected = sqlInsert($table, $columns, $values, (0));
		if (0) deb("survey.saveRequests(): request rows inserted = ", $rows_affected);
	}

	$prefers = explode(",", $work_pref['prefers']);
	
}

if (1) {  // write existing requests to debug
	$select = "w.username as requester, r.request, c.username as coworker, w.id as r_id, c.id as c_id, r.season_id as season";
	$from = "{$coworker_requests_table} as r, {$workers_table} as w, {$workers_table} as c";
	$where = "r.requester_id = w.id and r.coworker_id = c.id";
	$order_by = "r.request, w.username";
	$requests = sqlSelect($select, $from, $where, $order_by, (0));
	deb("survey.saveRequests(): coworker_requests after update = ", $requests);
}

?>