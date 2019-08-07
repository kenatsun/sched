<?php

require_once "start.php";
require_once "change_sets_utils.php";


function displaySchedule($controls_display="show", $change_markers_display="show", $edition="") { 
	$season = sqlSelect("*", SEASONS_TABLE, "id = " . SEASON_ID, "", (0))[0]; 
	if (0) deb("dashboard.displaySchedule(): CRUMBS_QUERY = " . CRUMBS_QUERY);
	if (0) deb("dashboard.displaySchedule(): NEXT_CRUMBS_IDS = {NEXT_CRUMBS_IDS}"); 
	$now = date_create();
	$now_f = date_format($now, "Y-m-d");
	$change_request_end_date = $season['change_request_end_date'];
	$change_request_end_date_f = date_format(date_create($change_request_end_date), "l, F jS");
	$scheduling_end_date = $season['scheduling_end_date'];
	$scheduling_end_date_f = date_format(date_create($scheduling_end_date), "l, F jS");

	if (0) deb("dashboard.displaySchedule(): edition = " . $edition);

	if ($edition == "first") {		
		if (0) deb("first");
		$adjective = "Tentative ";
		$subhead = "as of " . date_format($now, "g a F jS");
		$change_requests_line = "Please send change requests by " . $change_request_end_date_f . " to ";
	} elseif ($edition == "revised") {		
		if (0) deb("revised");
		$adjective = "Revised ";
		$subhead = "as of " . date_format($now, "g a F jS");
		$change_requests_line = "Any problems with these changes? <br>Please email them by <u>" . $scheduling_end_date_f . "</u> to ";
	} elseif ($edition == "final") {		
		if (0) deb("final");
		$adjective = "Final ";
		$subhead = date_format($now, "F j, Y");
		$change_requests_line = "Got a scheduling problem you can't solve yourself?  Email ";
	}
	if (0) deb("dashboard.displaySchedule(): adjective = " . $adjective);
	if (0) deb("dashboard.displaySchedule(): edition = " . $edition);
	$crumbs = $edition ? "" : CRUMBS_QUERY;  // Omit breadcrumbs from printable editions
	if (0) deb("dashboard.displaySchedule(): crumbs = " . $crumbs);
	$headline = renderHeadline($adjective . "Sunward Dinner Teams for {$season['name']}", $subhead, 0);
	if ($change_requests_line)	$change_requests_line = '<br><p style="color:blue; font-size:larger"><strong>' . $change_requests_line . '<a href="mailto:moremeals@sunward.org">moremeals@sunward.org</a></strong></p><br>'; 
	$assignments_form = renderAssignmentsForm($controls_display, $change_markers_display);

	if (!array_key_exists("previewonly", $_GET)) $bullpen = '<br>' . renderBullpen();

	$page = <<<EOHTML
		{$headline}
		{$change_requests_line}
		{$change_sets_link}
		{$assignments_form}
		{$bullpen}
EOHTML;
	print $page;
}

function renderAssignmentsForm($controls_display="show", $change_markers_display="show") {	
	$jobs_table = SURVEY_JOB_TABLE;
	$shifts_table = SCHEDULE_SHIFTS_TABLE;
	$workers_table = AUTH_USER_TABLE;
	$assignments_table = ASSIGNMENTS_TABLE;
	$changes_table = CHANGES_TABLE;
	$change_sets_table = CHANGE_SETS_TABLE;
	$meals_table = MEALS_TABLE;
	$season_id = SEASON_ID;
	
	if (0) deb("dashboard.php.renderAssignmentsForm(): change_markers_display =", $change_markers_display);
	$jobs = getJobs();
	if (0) deb("dashboard.php.renderAssignmentsForm(): jobs = ", $jobs);
	if (0) deb("dashboard.php.renderAssignmentsForm(): controls_display = ", $controls_display);

	// Get id of the most recent scheduler run
	$scheduler_run_id = scheduler_run()['id'];
	if (0) deb("dashboard.php.renderAssignmentsForm(): scheduler_run_id = ", $scheduler_run_id); 
	if (!$scheduler_run_id) {
		return "
			<p>The Scheduler hasn't been run for this season yet,<br>
			so there are no assignments to show at this time.</p>";
	}
	

	$select = " DISTINCT m.date as meal_date, m.id";
	$from = "{$jobs_table} j, {$shifts_table} s, {$meals_table} m";
	$where = "s.job_id = j.id
		and m.id = s.meal_id
		and j.season_id = {$season_id}
		and (m.skip_indicator = 0 or m.skip_indicator is null)";
	$order_by = "m.date asc";
	$meals = sqlSelect($select, $from, $where, $order_by, (0), "dashboard.renderAssignmentsForm(): get assignments for season");
	if (0) deb("dashboard.php.renderAssignmentsForm(): meals = ", $meals); 
	
	// Make the table header row
	$ncols = 1;
	$header_row .= '<tr style="background-color:' . HEADER_COLOR . '">
		<th><strong>meal date</th>';
	foreach($jobs as $index=>$job){
		$header_row .= "<th><strong>{$job['description']}</strong></th>";
		++$ncols;
	}
	$header_row .= "</tr>";
	if (0) deb("dashboard.php.renderAssignmentsForm(): header_row =", $header_row);

	// Make the actions rows
	if (userIsAdmin()) {
		// if (0) deb("dashboard.php.renderAssignmentsForm(): controls_display =", $controls_display);
		if (0) deb("dashboard.php.renderAssignmentsForm(): change_markers_display =", $change_markers_display);

		// Make publish row, if there are any saved changes
		$change_sets = sqlSelect("*", CHANGE_SETS_TABLE, "scheduler_run_id = " . $scheduler_run_id . " and published = 0", "", (0))[0];
		if ($change_sets && ($change_markers_display == "show")) {
			if ($controls_display == "show") {
				$publish_buttons = '
					&nbsp;&nbsp;
					<span name="publish_buttons" style="text-align:left;">
						<input type="submit" id="publish" name="publish" onclick="setFormAction(\'assignments_form\',\'' . makeURI("publish.php", NEXT_CRUMBS_IDS) . '\')" value="Publish changes (after review)"> 
						&nbsp;&nbsp;
						<input type="submit" id="undo" name="undo" onclick="setFormAction(\'assignments_form\',\'' . makeURI("change_sets.php", NEXT_CRUMBS_IDS) . '\')" value="Undo changes (after review)"> 
					</span>
				'; 
			}
			if (0) deb("dashboard.php: NEXT_CRUMBS_IDS = ", NEXT_CRUMBS_IDS); 
			if (0) deb("dashboard.php: publish_buttons = ", $publish_buttons); 
			$publish_legend = '
				&nbsp;&nbsp;
				<span style="font-size:11pt; text-align:right;">
					<span style="color:black">change markers </span>
					<span style="' . ADDED_COLOR . '">&nbsp;&nbsp;' . ADDED_ICON . ' 
						<span style="' . ADDED_DECORATION . '">worker added to job</span>
						&nbsp;&nbsp;
					</span>
					&nbsp;&nbsp;
					<span style="' . REMOVED_COLOR . '">&nbsp;&nbsp;' . REMOVED_ICON . ' 
						<span style="' . REMOVED_DECORATION . '">worker removed from job</span>
						&nbsp;&nbsp;
					</span>
				</span>';		
			if (0) deb("dashboard.php.renderAssignmentsForm(): publish_legend =", $publish_legend);

			if ($publish_legend || $publish_buttons) $publish_actions_row = '<tr><td style="background-color:White; padding:2px 0px 2px 0px; text-align:center" colspan=' . $ncols . '>' . $publish_buttons . $publish_legend . '</td></tr>';
		} 
		
		// Make save row
		$save_buttons = '
			&nbsp;&nbsp;<span style="text-align:left;">
				<input 
					type="submit" 
					id="save" 
					name="save" 
					onclick="
						setFormAction(\'assignments_form\',\'' . makeURI("change_set.php", NEXT_CRUMBS_IDS) . '\');
						disableValuelessInputs();
						" 
					value="Save these changes (after review)"> 
				<input 
					type="reset" 
					id="cancel" 
					name="cancel" 
					onclick="resetFormDisplayAfterDiscard()" 
					value="Discard these changes"> 
			</span>&nbsp;&nbsp;
		';
		$save_legend = '
			&nbsp;&nbsp;<span style="font-size:11pt; text-align:right;">
				<span style="color:black; background-color:' . CHANGED_BACKGROUND_COLOR . ';">unsaved changes have ' . CHANGED_BACKGROUND_COLOR . ' background</span>
			&nbsp;&nbsp;</span></span>';
		if (0) deb("dashboard.php.renderAssignmentsForm(): save_legend =", $save_legend);

		if ($save_legend || $save_buttons) $save_actions_row = '<tr name="save_actions_row" style="display:none"><td style="background-color:' . CHANGED_BACKGROUND_COLOR . '; padding:2px 0px 2px 0px; text-align:center;" colspan=' . $ncols . '>' . $save_buttons . $save_legend . '</td></tr>'; 

		}		
	$previous_meal_month = 0;	
	if (0) deb("dashboard.renderAssignmentsForm(): publish_actions_row (first time) = ", $publish_actions_row);
	$meal_rows .= $publish_actions_row . $save_actions_row . $header_row;
		
	// Make the table row for each meal
	$nrows = 0;
	$save_button_interval = 3;
	foreach($meals as $i=>$meal) {
		if (0) deb("dashboard.renderAssignmentsForm() meal['meal_date'] = {$meal['meal_date']}");
		$date_ob = new DateTime($meal['meal_date']);
		$meal['meal_day_name'] = $date_ob->format('l');
		$meal_month = $date_ob->format('m');
		$meal_date = $date_ob->format('F j'); 
		if (0) deb("dashboard.renderAssignmentsForm() meal_month = {$meal_month}");
		if (0) deb("dashboard.php.renderAssignmentsForm(): day name = {$meal['meal_day_name']}, date = {$meal['meal_date']}");

		if ($nrows == $save_button_interval && userIsAdmin() && $controls_display == "show") {
			if (0) deb("dashboard.renderAssignmentsForm(): publish_actions_row (repeat) = ", $publish_actions_row);
			$meal_rows .= $publish_actions_row . $save_actions_row . $header_row;
			// $meal_rows .= $actions_row . $header_row;
			$nrows = 1;
		} else {
			++$nrows;
		}
		$previous_meal_month = $meal_month;
	
		// Make the worker cell for each job in this row 
		$shift_cells = "";
		foreach($jobs as $i=>$job){
			
			if (0) deb("dashboard.php.renderAssignmentsForm(): job_id = {$job['job_id']}, meal_id = {$meal['id']}");
			
			// Get the id of this shift (i.e. this job for this meal)
			$select = "s.id as id";
			$from = "{$shifts_table} as s,
				{$meals_table} as m";
			$where = "s.job_id = {$job['job_id']}
				and s.meal_id = m.id
				and m.id = {$meal['id']}"; 
			$order_by = "";
			$shifts = sqlSelect($select, $from, $where, $order_by, (0), "dashboard.renderAssignmentsForm()"); 
			$shift_id = $shifts[0]['id'];
			if (0) deb("dashboard.php.renderAssignmentsForm(): shift_id = {$shift_id}");

			// Find the worker(s) doing this shift (i.e. this job for this meal)
			$select = "w.username as worker_name, 
				w.id as worker_id,  
				a.id as assignment_id,  
				a.latest_change_id,
				a.when_last_changed,
				a.generated,
				a.exists_now"; 
			$from = "{$workers_table} as w, 
				{$assignments_table} as a";
			$where = "a.worker_id = w.id
				and a.shift_id = {$shift_id}";
			$order_by = "worker_name";
			$assignments = sqlSelect($select, $from, $where, $order_by, (0), "renderAssignmentsForm(): assignments in shift");
			if (0) deb("dashboard.php.renderAssignmentsForm(): assignments = ", $assignments);
			
			// Make the embedded table listing the workers & controls for this shift cell
			$shift_cell = "";
			$shift_cell .= '<td><table>';
			if (SHOW_IDS) $shift_cell .= '<tr><td>shift #' . $shift_id . '</td></tr>';
			$slots_to_fill = $job['workers_per_shift'];
			if (0) deb("dashboard.php.renderAssignmentsForm(): job = {$job['description']}, slots_to_fill = $slots_to_fill"); 
			foreach($assignments as $w_index=>$assignment) {
				$worker_id = $assignment['worker_id'];
				if (SHOW_IDS) $wkr_id = ' (#' . $worker_id . '), assmt #' . $assignment['assignment_id']; 

				if (0) deb("dashboard.php.renderAssignmentsForm(): assignment = ", $assignment);
				$exists_now = $assignment['exists_now']; 
				$has_changed = ($assignment['generated'] != $exists_now ? 1 : 0);
				if (0) deb("dashboard.php.renderAssignmentsForm(): exists_now = {$exists_now}, has_changed = {$has_changed}, assm_id = {$assignment['assignment_id']}");		
				
				// If assignment's status changed since generation, make a change marker
				if ($has_changed && $change_markers_display == "show") {
					// Get data about the latest change
					$select = "s.when_saved as when_saved, s.id as id";
					$from = CHANGES_TABLE . " as c, " . CHANGE_SETS_TABLE . " as s";
					$where = "c.id = {$assignment['latest_change_id']}
						and s.id = c.change_set_id";
					$latest_change_set = sqlSelect($select, $from, $where, "", (0), "latest change set")[0];
					if (0) deb("dashboard.php.renderAssignmentsForm(): latest_change_set = ", $latest_change_set); 
					
					if (SHOW_IDS) $chg_id = '<br>(chg set #' . $latest_change_set['id'] . ')';
					$change_marker = formatted_date($latest_change_set['when_saved'], "M j g:ia") . $chg_id;
					if (0) deb("dashboard.php.renderAssignmentsForm(): change_marker = {$change_marker}"); 
					
					// If assignment exists now, make an "added" marker	 
					if ($exists_now) {
						$assignment_color = ADDED_COLOR;
						$assignment_decoration = ADDED_DECORATION;
						$assignment_icon = ADDED_ICON;
						$change_marker = ' - added ' . $change_marker; 
					} 
					// Else assignment doesn't exist now, so make a "removed" marker
					else { 
						$assignment_color = REMOVED_COLOR;
						$assignment_decoration = REMOVED_DECORATION;
						$assignment_icon = REMOVED_ICON;
						$change_marker = ' - removed ' . $change_marker; 						
					}
				}
				// Else assignment's status is the same as at generation, so don't make a change marker
				else {
					$assignment_color = "White"; 
					$assignment_decoration = ""; 
					$assignment_icon = "";
					$change_marker = "";					
				}
				
				// Render the assignment if it exists and/or has been removed since generation
				// Don't show an assignment that was not generated and doesn't currently exist
				if ($exists_now || ($has_changed && $change_markers_display == "show")) {
					$shift_cell .= '<tr><td style="' . $assignment_color . '"><strong>' . $assignment_icon . '&nbsp;<span style="' . $assignment_decoration . '">' . $assignment['worker_name'] . '</span></strong>' . $wkr_id . $change_marker; 
					if ($exists_now) --$slots_to_fill;
				}
				
				if (userIsAdmin() && $controls_display == "show") {
					// Display controls that would REMOVE worker from shift, unless worker has been removed already
					if ($exists_now) {	
						$action = "remove";
						$tr_id = 'tr_' . $action . '.' . $shift_id . '.' . $worker_id; 
						$shift_cell .= '<table>';
						$shift_cell .= '
							<tr 
								id="tr.' . inputId($action, $shift_id, $worker_id) . '"
								name="change_control_tr"
								style="background-color:white">
								<td 
									style="text-align: right; background-color:rgba(0,0,0,0);">remove
								</td>
								<td style="background-color:rgba(0,0,0,0);">
									<input ' .
										inputAtts("checkbox", $action, $shift_id, $worker_id) . '
										style="margin-left:0px;" 
									>
								</td>
							</tr>';

						// Figure out which shifts this worker could be MOVEd OUT to
						$possible_shifts = getPossibleShiftsForWorker($worker_id, $job['job_id'], TRUE);
						if (0) deb("dashboard.php.renderAssignmentsForm(): worker = {$assignment['worker_name']} {$worker_id}, possible_shifts = ", $possible_shifts); 
						
						// Display the possible shifts in a dropdown box 
						if ($possible_shifts) {
							$action = "moveout";
							$id = 'action:' . $action . ' this_shift:' . $shift_id . ' this_worker:' . $worker_id; 
							if (0) deb("dashboard.php.renderAssignmentsForm(): 'remove' id = " . $id); 

							$shift_cell .= '
								<tr 
									id="tr.' . inputId($action, $shift_id, $worker_id) . '" 
									name="change_control_tr"
									style="background-color:white">
									<td 
										style="text-align: right; background-color:rgba(0,0,0,0);">move out</td>
									<td  
										style="background-color:rgba(0,0,0,0);">
										<select ' .
											inputAtts("select", $action, $shift_id, $worker_id) . '
										>';							
							$shift_cell .= '
								<option ' .
									inputAtts("no_change_option", $action, $shift_id) . '>
								</option>'; 

							foreach($possible_shifts as $s_index=>$possible_shift) {
								$meal_date_ob = new DateTime($possible_shift['meal_date']);
								$shift_cell .= '
									<option ' .
										inputAtts("option", $action, $shift_id, $worker_id, $possible_shift['shift_id']) . '
										style="font-size: 9pt" ' . 
									'>' .
										'to ' . formattedDateForOption($possible_shift['meal_date']) . 
									'</option>' 
									;
							}
							$shift_cell .= "</select></td></tr>"; 
						} 
						
						// Get possible TRADEs into this shift for this worker
						$possible_trades = getPossibleTradesForShift($worker_id, $shift_id, $job['job_id']);
						if (0) deb("dashboard.php.renderAssignmentsForm(): worker = {$assignment['worker_name']} {$worker_id}, possible_trades = ", $possible_trades); 

						// Display the possible trades in a dropdown box
						if ($possible_trades) {
							$action = "trade";
							$id = 'action:' . $action . ' this_shift:' . $shift_id . ' this_worker:' . $worker_id; 
							$shift_cell .= '
								<tr 
									id="tr.' . inputId($action, $shift_id, $worker_id) . '" 
									name="change_control_tr"
									style="background-color:white">
									<td 
										style="text-align: right; 
										background-color:rgba(0,0,0,0);">trade to
									</td>
									<td 
										style="background-color:rgba(0,0,0,0);">
										<select ' .
											inputAtts("select", $action, $shift_id, $worker_id) . '
										>';
							$shift_cell .= '
								<option ' .
									inputAtts("no_change_option", $action, $shift_id, $worker_id) . '> 
								</option>';
							foreach($possible_trades as $t_index=>$possible_trade) {
								$meal_date_ob = new DateTime($possible_trade['meal_date']);
								$shift_cell .= '
									<option ' .
											inputAtts("option", $action, $shift_id, $worker_id, $possible_trade['shift_id'], $possible_trade['worker_id']) .
									'>' .
										formattedDateForOption($possible_trade['meal_date']) . ' for ' . $possible_trade['worker_name'] . '
									</option>';
							}
							$shift_cell .= '</select></td></tr>';
						}
						$shift_cell .= '</td></tr>';
						$shift_cell .= "</table>";
					}
				}
			}
			
			if (userIsAdmin() && $controls_display == "show") { 
				$shift_cell .= '<table>';

				// Figure out which workers could be MOVEd INto this shift from another shift
				$possible_move_ins = getPossibleMovesIntoShift($shift_id);
				if (0) deb("dashboard.php.renderAssignmentsForm(): meal_date = {$meal['meal_date']}, possible_move_ins = ", $possible_move_ins); 

				// Display the movable workers in a dropdown box
				if ($possible_move_ins) {
					$action = "movein";
					$id = 'action:' . $action . ' this_shift:' . $shift_id; 
					$shift_cell .= '<hr>';
					$shift_cell .= '
						<tr 
							id="tr.' . inputId($action, $shift_id) . '"  
							name="change_control_tr"
							style="background-color:white;">
							<td 
								style="text-align: right; background-color:rgba(0,0,0,0);">
									move in  
							</td>
							<td 
								style="background-color:rgba(0,0,0,0);">
								<select ' .
									inputAtts("select", $action, $shift_id) . '
								>';
					$shift_cell .= '
						<option ' .
							inputAtts("no_change_option", $action, $shift_id) . '> 
						</option>';
					foreach($possible_move_ins as $w_index=>$possible_move_in) {
						if (0) deb("dashboard.php.renderAssignmentsForm(): meal_date = {$meal['meal_date']}, possible_move_in = ", $possible_move_in);
						$shift_cell .= '
							<option 
								style="background-color:' . $color . ';" ' .
								inputAtts("option", $action, $shift_id, "", $possible_move_in['shift_id'], $possible_move_in['worker_id']) . '
							>'
								. $possible_move_in['worker_name'] . ' from ' . formattedDateForOption($possible_move_in['meal_date']) . '
							</option>';
					}
					$shift_cell .= '</select></td></tr>';
				}

				// Figure out which workers could be ADDed to this shift
				$available_workers = getAvailableWorkersForShift($shift_id, FALSE, TRUE);
				if (0) deb("dashboard.php.renderAssignmentsForm(): meal_date = {$meal['meal_date']}, available_workers = ", $available_workers); 

				// Display the available workers in a dropdown box
				if ($available_workers) {
					$action = "add";
					$id = 'action:' . $action . ' this_shift:' . $shift_id; 
					if (!$possible_move_ins) $shift_cell .= '<hr>';
					$shift_cell .= '
						<tr 
							id="tr.' . inputId($action, $shift_id) . '" 
							name="change_control_tr"
							style="background-color:white">
							<td 
								style="text-align: right; background-color:rgba(0,0,0,0);">
									add  
							</td>
							<td 
								style="background-color:rgba(0,0,0,0);">
								<select ' . 
									inputAtts("select", $action, $shift_id) . '
							>';
					$shift_cell .= '
						<option ' .
							inputAtts("no_change_option", $action, $shift_id) . '> 
						</option>';
					foreach($available_workers as $w_index=>$available_worker) {
						$color = $available_worker['open_offers_count'] > 0 ? "LightGreen" : "LightGray";
						$shift_cell .= '
							<option 
								style="background-color:' . $color . ';" ' .
								inputAtts("option", $action, $shift_id, "", "", $available_worker['worker_id']) .
							'>'
								. $available_worker['worker_name'] . ' (' . $available_worker['open_offers_count'] . ')
							</option>';
					}
					$shift_cell .= '</select></td></tr>';
				}
				$shift_cell .= '</table>';
			}

			if (0) deb("dashboard.php.renderAssignmentsForm(): job = {$job['description']}, slots_to_fill = $slots_to_fill"); 
			if ($slots_to_fill > 0) {
				$job_name = ($slots_to_fill > 1) ? $job['description'] . "s" : $job['description'];
				$shift_cell .= '<tr><td style="font-weight:bold; text-transform:uppercase; text-align:center; background:black; color:white;">' . $slots_to_fill . ' ' . $job_name . ' needed!</td></tr>';
			}
			$shift_cell .= '</table></td>';
			$shift_cell .= '</div>';
			if (0) deb("dashboard.php.renderAssignmentsForm(): shift_cell = ", $shift_cell);
			$shift_cells .= $shift_cell;
		}

		$meal_row = '
		<tr>
			<td style="background-color:' . HEADER_COLOR . ';"><strong><big>' . $meal_date . '</big></strong><br>' . $meal['meal_day_name'] . '</td>' . 
			$shift_cells .
		'</tr>';
		$meal_rows .= $meal_row; 
	}
	$meal_rows .= $actions_row;
		
	$assignments_table = '
		<table style="table-layout:auto; width:100%" style="background:Yellow">
			<table style="table-layout:auto; width:100%" border="1" cellspacing="3">' .
			$meal_rows . 
			'</table>
		</table>
		';

	$assignments_form = 
		$assignments_form_headline . 
		'<form id="assignments_form" name="assignments_form" action="' . makeURI("dashboard.php", CRUMBS_IDS) . '" method="post">' . 
		$assignments_table .
		'<input type="hidden" name="scheduler_run_id" id="scheduler_run_id" value="{$scheduler_run_id}" />
		<input type="hidden" name="change_count" id="change_count" value="0" />
		<input type="hidden" id="unchanged_background_color" value="' . UNCHANGED_BACKGROUND_COLOR . '" />
		<input type="hidden" id="changed_background_color" value="' . CHANGED_BACKGROUND_COLOR . '" />
		</form>';
	return $assignments_form;
}


//////////////////////////////////////////////////////////////// Supporting functions 

function inputAtts($type, $action="", $this_shift_id="", $this_worker_id="", $that_shift_id="", $that_worker_id="") {
		
	if ($action) {
		$custom_atts .= ' data-ac="' . $action . '" ';
		$value_att .= 'action:' . $action;
	}
	if ($this_shift_id) {
		$custom_atts .= ' data-s1="' . $this_shift_id . '" ';
		$value_att .= ' this_shift:' . $this_shift_id;
	}
	if ($this_worker_id) {
		$custom_atts .= ' data-w1="' . $this_worker_id . '" ';
		$value_att .= ' this_worker:' . $this_worker_id;
	}
	if ($that_shift_id) {
		$custom_atts .= ' data-s2="' . $that_shift_id . '" ';
		$value_att .= ' that_shift:' . $that_shift_id;
	}
	if ($that_worker_id) {
		$custom_atts .= ' data-w2="' . $that_worker_id . '" '; 
		$value_att .= ' that_worker:' . $that_worker_id;
	}	
	
	$id_att = inputId($action, $this_shift_id, $this_worker_id, $that_shift_id, $that_worker_id);
	$standard_atts = ' id="' . $id_att . '"';
	
	switch ($type) {
		case "checkbox":
			$standard_atts .= ' value="' . $value_att . '" ';
			$standard_atts .= 
				' type="' . $type . '" ' . 
				' name="' . $action . '[]" ' . 
				' class="control_for_shift_' . $this_shift_id . '" ' .
				' onclick="updateChangeControlDisplay(\'' . $id_att . '\')"';
			break;
		case "select":
			$standard_atts .= 
				' type="' . $type . '" ' . 
				' name="' . $action . '[]" ' . 
				' class="control_for_shift_' . $this_shift_id . '" ' . 
				' onchange ="updateChangeControlDisplay(\'' . $id_att . '\')"' .
				' style="font-size: 9pt" ';  
			break;
		case "option":
			$standard_atts .= ' value="' . $value_att . '" ';
			break; 
		case "no_change_option":
			$standard_atts .= ' value="" ';
			$standard_atts .= ' selected ';
			break; 
	}

	$atts .= $custom_atts;
	$atts .= $standard_atts;	
	return $atts;
}

function inputId($action="", $this_shift_id="", $this_worker_id="", $that_shift_id="", $that_worker_id="") {
	if ($action) $id .= $action;
	if ($this_shift_id) $id .= '.' . $this_shift_id;
	if ($this_worker_id) $id .= '.' . $this_worker_id;
	if ($that_shift_id) $id .= '.' . $that_shift_id;
	if ($that_worker_id) $id .= '.' . $that_worker_id;
	if (0) deb("dashboard_utils.php.trId(): tr_id = $id");
	return $id; 
}


function getAvailableWorkersForShift($shift_id, $addable_only=FALSE, $omit_avoiders=TRUE) { 
	$season_id = SEASON_ID; 
	$select = "p.worker_id, 
			p.worker as worker_name,
			p.job_name, 
			o.open_offers_count";
	$from = "possible_shifts_for_workers p, open_offers_count o";
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
				from open_offers_count as oo
				where oo.open_offers_count > 0			-- who have more offers than assignments
					and oo.job_id = p.job_id			-- to do this job
					and oo.season_id = {$season_id}		-- in this season.
		)";
	$order_by = "open_offers_count desc, pref desc, worker asc";
	$available_workers = sqlSelect($select, $from, $where, $order_by, (0), "available_workers");
	if (0) deb("dashboard.php:getPossibleShiftsForWorker() available_workers for shift {$shift_id} (from view) = ", $available_workers);

	return $available_workers;
}

function getPossibleShiftsForWorker($worker_id, $job_id, $omit_avoiders=TRUE) {
	$season_id = SEASON_ID;
	$select = "shift_id, 
			worker,
			meal_date,
			job_id, 
			job_name";
	$from = "possible_shifts_for_workers";
	$where = "worker_id = {$worker_id}    -- This worker.
			and season_id = {$season_id}  -- This season.
			and job_id = {$job_id}        -- This job type.";
	$order_by = "shift_id asc";
	$possible_shifts = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleShiftsForWorker()");
	if (0) deb("dashboard.php:getPossibleShiftsForWorker() possible_shifts = ", $possible_shifts);

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
	$from = "swaps";
	$where = "this_worker_id = {$worker_id}
		and this_worker_current_shift_id = {$shift_id}
		and job_id = {$job_id}";
	$order_by = "shift_id asc";
	$possible_trades = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleTradesForShift()"); 
	if (0) deb("dashboard.php:getPossibleTradesForShift() possible_trades = ", $possible_trades);
	
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
		possible_shifts_for_workers p,
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
	";
	$order_by = "shift_id asc";
	$possible_trades = sqlSelect($select, $from, $where, $order_by, (0), "getPossibleTradesForShift()"); 
	if (0) deb("dashboard.php:getPossibleMovesIntoShift() possible_trades = ", $possible_trades);
	
	return $possible_trades;
}


function formattedDateForOption($date) {
	$date_ob = date_create($date);
	$formatted_date = date_format($date_ob, 'D M j');
	return $formatted_date;
}

function publishSchedule() {
	$scheduler_run_id = scheduler_run()['id'];	
	$assignments = sqlSelect("*", ASSIGNMENTS_TABLE, "scheduler_run_id = " . $scheduler_run_id, "", (0), "change_sets_utils.publishSchedule()");
	foreach($assignments as $assignment) {
		// Mark assignments that currently exist as having been generated
		if ($assignment['exists_now']) {
			sqlUpdate(ASSIGNMENT_STATES_TABLE, "generated = 1", "id = " . $assignment['id'], (0));
			if (0) deb("change_sets_utils.publishSchedule(): make this assignment permanent:", $assignment);			
		}
		else {
			if (0) deb("change_sets_utils.publishSchedule(): delete this assignment:", $assignment);
			sqlDelete(ASSIGNMENT_STATES_TABLE, "id = " . $assignment['id'], (0));
		}
	}

	sqlUpdate(CHANGE_SETS_TABLE, "published = 1", "scheduler_run_id = " . $scheduler_run_id . " and published = 0", (0));
	displaySchedule();
}


?>