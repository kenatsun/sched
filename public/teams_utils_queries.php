<?php

function populateStashTables($season_id) {

	$table = "stash_possible_shifts_for_workers";
	sqlDelete($table, "", (0), "renderAssignmentsForm(): emptying stash_possible_shifts_for_workers");
	$values = "
	SELECT *   
	FROM possible_shifts_for_workers
	WHERE season_id = " . $season_id
	;
	if (0) deb("before INSERT stash_possible_shifts_for_workers query" . since("before INSERT stash_possible_shifts_for_workers query"));
	sqlInsert($table, null, $values, (0), "renderAssignmentsForm(): populating stash_possible_shifts_for_workers");
	if (0) deb("after INSERT stash_possible_shifts_for_workers query" . since("after INSERT stash_possible_shifts_for_workers query"));

	$table = "stash_open_offers_count";
	sqlDelete($table, "", (0), "renderAssignmentsForm(): emptying stash_open_offers_count");
	$values = "
	SELECT *
	FROM open_offers_count
	WHERE season_id = " . $season_id
	;
	if (0) deb("before INSERT stash_open_offers_count" . since("before INSERT stash_open_offers_count"));
	sqlInsert($table, null, $values, (0), "renderAssignmentsForm(): popping stash_open_offers_count");
	if (0) deb("after INSERT stash_open_offers_count" . since("after INSERT stash_open_offers_count"));

	$table = "stash_swaps";
	sqlDelete($table, "", (0), "renderAssignmentsForm(): emptying stash_swaps");
	$values = "
	SELECT *   
	FROM swaps
	WHERE season_id = " . $season_id
	;
	if (0) deb("before INSERT stash_swaps query" . since("before INSERT stash_swaps query"));
	sqlInsert($table, null, $values, (0), "renderAssignmentsForm(): populating stash_swaps");
	if (0) deb("after INSERT stash_swaps query" . since("after INSERT stash_swaps query"));

}


function getPossibleShiftsForWorker($worker_id, $job_id, $omit_avoiders=TRUE) {
	if (0) deb("getPossibleShiftsForWorker(): start" . since("getPossibleShiftsForWorker(): start"));
	$season_id = SEASON_ID;
	$select = "shift_id, 
			worker,
			meal_date,
			job_id, 
			job_name";
	$from = "stash_possible_shifts_for_workers";
	$where = "worker_id = {$worker_id}    -- This worker.
			and season_id = {$season_id}  -- This season.
			and job_id = {$job_id}        -- This job type.";
	$order_by = "shift_id asc";
	if (0) deb(">> start MOVE OUT query" . since("start MOVE OUT query"));
	$possible_shifts = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleShiftsForWorker()");
	if (0) deb("<< end MOVE OUT query" . since("end MOVE OUT query"));
	if (0) deb("teams.php:getPossibleShiftsForWorker(): shift_id = " . $shift_id . "\npossible_shifts = ", $possible_shifts);
	if (0) deb("getPossibleShiftsForWorker(): end" . since("getPossibleShiftsForWorker(): end"));

	return $possible_shifts;
}


function getPossibleTradesForShift($worker_id, $shift_id, $job_id) {
	$select = 
		"that_worker_id as worker_id,
		that_worker_current_shift_id as shift_id,
		that_worker_current_assignment_id as assignment_id,
		job_id as job_id,
		that_worker_name as worker_name,
		that_worker_current_meal_date as meal_date";
	$from = "stash_swaps";
	$where = "this_worker_id = {$worker_id}
		and this_worker_current_shift_id = {$shift_id}
		and job_id = {$job_id}";
	$order_by = "shift_id asc";
	if (0) deb(">> start TRADE query" . since("start TRADE query"));
	$possible_trades = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleTradesForShift()"); 
	if (0) deb("<< end TRADE query" . since("end TRADE query"));
	if (0) deb("teams.php:getPossibleTradesForShift(): shift_id = " . $shift_id . "\n possible_trades = ", $possible_trades);
	
	return $possible_trades;  
}


function getPossibleMovesIntoShift($shift_id) {

	$select = "
		p.worker as worker_name,
		p.worker_id as worker_id,
		m.date as meal_date,
		a.shift_id as shift_id
		";
	$from = "
		stash_possible_shifts_for_workers p,
		assignments a,
		shifts s, 
		meals m
		";
	$where = "
		a.worker_id = p.worker_id
		and a.season_id = p.season_id
		and s.job_id = p.job_id
		and a.exists_now = 1
		and p.shift_id = " . $shift_id . "
		and s.id = a.shift_id
		and m.id = s.meal_id
		and m.skip_indicator = 0
	";
		// and m.date >= '" . date("Y-m-d") . "'
	$order_by = "shift_id asc";
	if (0) deb(">> start MOVE IN query" . since("start MOVE IN query"));
	$possible_moveins = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleMovesIntoShift()"); 
	if (0) deb("<< end MOVE IN query" . since("end MOVE IN query"));
	if (0) deb("teams.php:getPossibleMovesIntoShift(): shift_id = " . $shift_id . "\n possible_moveins = ", $possible_moveins); 
	
	return $possible_moveins;
}


function getPossibleAddsIntoShift($shift_id, $addable_only=FALSE, $omit_avoiders=TRUE) { 
	$season_id = SEASON_ID; 
	$select = "p.worker_id, 
			p.worker as worker_name,
			p.job_name, 
			o.open_offers_count";
	$from = "stash_possible_shifts_for_workers p, stash_open_offers_count o";
	$where = "p.shift_id = {$shift_id}      	-- This shift.
			and p.season_id = {$season_id}  	-- This season.
			and o.worker_id = p.worker_id		-- The open offers of this worker
			and o.job_id = p.job_id				-- to do this job
			and o.season_id = {$season_id}		-- in this season.";
	// Optionally, include only workers who have more offers than assignments to do this job.
	if ($addable_only) $where = $where . "
			-- Include only workers who have more offers than assignments to do this job
			and p.worker_id in (						-- This worker is in
				select oo.worker_id 					-- the set of workers		
				from stash_open_offers_count as oo
				where oo.open_offers_count > 0			-- who have more offers than assignments
					and oo.job_id = p.job_id			-- to do this job
					and oo.season_id = {$season_id}		-- in this season.
		)";
	$order_by = "open_offers_count desc, pref desc, worker asc";
	if (0) deb(">> start ADD query" . since("start ADD query"));
	$possible_adds = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleAddsIntoShift()");
	if (0) deb("<< end ADD query" . since("end ADD query"));
	if (0) deb("teams.php:getPossibleAddsIntoShift(): shift_id = " . $shift_id . "\npossible_adds = ", $possible_adds); 
	// }
	if (0) deb("getPossibleAddsIntoShift(): query end" . since("getPossibleAddsIntoShift(): query end"));

	return $possible_adds;
}


function renderBullpen() {
	$td_style = ' style="border:1px shadow;"'; 
	$jobs = sqlSelect("*", SURVEY_JOB_TABLE, "season_id = " . SEASON_ID, "display_order asc");
	$num_jobs = count($jobs);
	$title_row = '<tr><td style="text-align:center; font-weight:bold; font-size:12pt; border:1px shadow; background-color:' . HEADER_COLOR . ';" colspan="' . $num_jobs . '">The Bullpen</td></tr>';
	$header_row = '<tr>';
	$bullpen_row = '<tr>';
	foreach ($jobs as $job) {
		$num_available = 0;
		$select = "*";
		$from = "stash_open_offers_count";
		$where = "job_id = {$job['id']}
			and open_offers_count > 0";
		$order_by = "open_offers_count desc, worker_name asc";
		$workers = sqlSelect($select, $from, $where, $order_by, (0), "utils:renderBullpen()"); 
		$bullpen_for_job = "";
		foreach ($workers as $worker) {
			if ($bullpen_for_job) $bullpen_for_job .= "<br>";
			$bullpen_for_job .= $worker['worker_name'] . " (" . $worker['open_offers_count'] . ")";
			$num_available += $worker['open_offers_count'];
		}
		if (!$num_available) $num_available = "0"; 
		if (0) deb("utils.renderBullpen(): job = ", $job);
		$header_row .= '<th ' . $td_style . '>' . $job['description'] . ' (' . $num_available . ')</th>'; 
		if (0) deb("utils.renderBullpen(): header_row = ", $header_row);
		$bullpen_row .= '<td ' . $td_style . '>' . $bullpen_for_job . '</td>'; 
	}
	$bullpen_row .= '</tr>';
	$header_row .= '</tr>';
	// return '<table style="width:75%; border-collapse:collapse;" >' . $title_row . $header_row . $bullpen_row . '</table>';
	return '<table style="width:75%;" border="1">' . $title_row . $header_row . $bullpen_row . '</table>';
}



?>