<?php
require_once 'start.php';
require_once "classes/PeopleList.php";
print '<script src="js/report.js"></script>';

if (0) deb("report: _SESSION = ", $_SESSION);
if (0) deb("report: userIsAdmin() = " . userIsAdmin());

require_once('classes/calendar.php');
require_once('participation.php');

//////////////// SAVE CHANGES

if ($_POST) saveLiaisonData($_POST);


//////////////// DISPLAY THE PAGE

$headline = renderHeadline("Our Responses So Far");
if (0) deb("report.php: headline = ", $headline);

$scoreboard_body = renderScoreboard("");
$scoreboard_title = "Scoreboard";
$scoreboard = renderBlockInShowHideWrapper($scoreboard_body, $scoreboard_title, '<h2>', $scoreboard_title . '</h2>');

$workers = renderJobSignups("Job sign-ups", TRUE);

// $non_responders = (!surveyIsClosed() ? renderNonResponders() : "");
$non_responders = renderNonResponders(); 
if (0) deb("report.php: non_responders = ", $non_responders);

$calendar = new Calendar();
$calendar->job_key = (isset($_GET['key']) && is_numeric($_GET['key'])) ? intval($_GET['key']) : 'all';
$calendar->data_key = (isset($_GET['show'])) ? $_GET['show'] : 'all';
$calendar->setIsReport(TRUE);
$cal_string = renderOffersCalendar($calendar);

$comments = (userIsAdmin() ? renderOtherPreferences() : "");

print 
	$headline .
	$scoreboard . '
	<br>' .
	$workers . '
	<br>' .
	$non_responders . '
	<br>' .
	$cal_string . '
	<br>' .
	$comments . '
	</body>
	</html>'
;


//////////////// DATABASE FUNCTIONS

function saveLiaisonData($posts) {
	if (0) deb("report.saveLiaisonData(): posts = ", $posts);

	// Update liaison of worker
	$season_liaisons = $posts['liaisons'];
	if ($season_liaisons) {
		foreach($season_liaisons as $season_liaison) {
			$season_worker_id = explode(".", $season_liaison)[0];
			$liaison_id = explode(".", $season_liaison)[1];
			if (!$liaison_id) $liaison_id = "NULL";
			sqlUpdate(SEASON_WORKERS_TABLE, "liaison_id = " . $liaison_id, "season_id = " . SEASON_ID . " AND id = " . $season_worker_id, (0));
		}	
	}

	// Update suggested actions for worker
	$liaison_actions = $posts['liaison_actions'];	
	if ($liaison_actions) {
		foreach($liaison_actions as $liaison_action) {
			$season_worker_id = explode(".", $liaison_action)[0];
			$liaison_action = explode(".", $liaison_action)[1];
			if ($liaison_action) $liaison_action = "'" . $liaison_action . "'"; else $liaison_action = "NULL";
			sqlUpdate(SEASON_WORKERS_TABLE, "liaison_action = " . $liaison_action . ""	, " season_id = " . SEASON_ID . " AND id = " . $season_worker_id, (0));
		}
	}	

	// Update liaison reports on worker
	if ($posts) {
		foreach($posts as $key=>$post) {
			$text = str_replace("'", "''", $post);
			if (0) deb("report.php: post = ", $post);
			$report_cud = explode (":" , $key)[0];
			if ($report_cud == "create_report" && $text) {
				$columns = "season_worker_id, report, timestamp";
				$values = explode (":" , $key)[1] . ", '" . $text . "', '" . date_format(date_create(), "Y-m-d H:i:s") . "'";
				sqlInsert(LIAISON_REPORTS_TABLE, $columns, $values, (0));
			} elseif ($report_cud == "update_report") {
				$set = "report = '" . $text . "'";
				$where = "id = " . explode (":" , $key)[1];
				sqlUpdate(LIAISON_REPORTS_TABLE, $set, $where, (0));
			} elseif ($report_cud == "delete_report") {
				// $where = "id = " . $post;
				$where = "id = " . explode (":" , $key)[1];
				sqlDelete(LIAISON_REPORTS_TABLE, $where, (0));
			}
		}	
	}
}


//////////////// DISPLAY FUNCTIONS

function renderOffersCalendar($calendar) {
	$worker_dates = $calendar->getWorkerShiftPrefs();
	$selector_html = $calendar->renderDisplaySelectors($calendar->job_key, $calendar->data_key);
	$calendar_body = $calendar->renderCalendar(NULL, $worker_dates);
	$section_title = (surveyIsClosed() ? "Calendar" : "When we can work");

	$block = '
		<ul>' . $selector_html . '</ul>' .
			$calendar_body . '
		<ul>' . $selector_html . '</ul>'
	;
	$out = renderBlockInShowHideWrapper($block, $section_title, '<h2>', $section_title . '</h2>');
	return $out;
}

function renderNonResponders() {
	// $non_responders = getNonResponders();
	$select = "first_name || ' ' || last_name as name";
	$from = SEASON_WORKERS_TABLE . " as sw, " . AUTH_USER_TABLE . " as w";
	$where = "sw.worker_id = w.id and season_id = " . SEASON_ID . " and sw.first_response_timestamp is null";
	// $where = "sw.worker_id = w.id and season_id = " . SEASON_ID . " and (sw.first_response_timestamp is null or sw.first_response_timestamp = '')";
	$order_by = "first_name, last_name";
	$non_responders = sqlSelect($select, $from, $where, $order_by, (0));
	
	if (0) deb("report.renderNonResponders: non_responders =", $non_responders);
	foreach($non_responders as $non_responder) {
		$non_responder_names .= "<tr><td>{$non_responder['name']}</td></tr>
			";
	}
	// $non_responders_headline = "Who hasn't responded yet";
	// $html_before = '<h2>';
	// $html_after = ' Who hasn\'t responded yet</h2>';
	$non_responders_count = count($non_responders);	
	$block = '
		<table><tr><td style="background:Yellow">
			<table border="1" cellspacing="3">' .
				$non_responder_names . '
			</table>
		</td></tr></table>' .
		$non_responders_count . ' people haven\'t responded.'
	;

	$section_title = 'Who hasn\'t responded yet';
	$out = renderBlockInShowHideWrapper($block, $section_title, '<h2>', $section_title . '</h2>');
	// $out = renderBlockInShowHideWrapper($block, 'non_responders', '<h2>', 'Who hasn\'t responded yet</h2>');

	if (0) deb("report.renderNonResponders: out =", $out);
	return $out;
}

function renderOtherPreferences() {
	
	// Get the worker comments from database
	$season_id = SEASON_ID;
	$select = "w.first_name || ' ' || w.last_name as worker_name, c.avoids, c.prefers, c.clean_after_self, c.comments";
	$from = SCHEDULE_COMMENTS_TABLE . " as c, " . 
		AUTH_USER_TABLE . " as w";
	$where = "c.worker_id = w.id and c.season_id = {$season_id}";
	$order_by = "w.first_name, w.last_name";
	$workers = sqlSelect($select, $from, $where, $order_by);
	if (0) deb ("report.renderOtherPreferences(): comments =", $workers); 

	$attributes = array(
		"avoids" => "avoid scheduling with", 
		"prefers" => "prefer scheduling with", 
		"clean_after_self" => "clean after cooking?",
		"comments" => "comments",
	);
	if (0) deb("report.renderOtherPreferences(): attributes = ", $attributes);

	// Make header row for the table
	$header = '<tr style="text-align:center;"><th></th>';
	foreach($attributes as $key=>$label) {		
		if (0) deb ("report.renderOtherPreferences(): attribute = $key, label = $label");
		$header .= '<th style="text-align:center;">' . $label . "</th>";
	}
	$header .= "</tr>";
	if (0) deb ("report.renderOtherPreferences(): header =", $header); 

	// Make data rows
	$prev_person_id = 0;
	$rows = '';
	foreach($workers as $worker) {
		// If this is a new person, start a new row
		if ($signup['person_id'] != $prev_person_id) {
			if ($prev_person_id != "") $rows .= "</tr><tr>";
			$rows .= "
			<tr>
				<td>{value['worker_name']}</td>";
			$prev_person_id = $value['person_id'];
			if (0) deb ("report.renderOtherPreferences(): key = $key, value = $value");
		}		
		if (0) deb ("report.renderOtherPreferences(): signup['job_id']) = {$value['job_id']}");
		if (0) deb ("report.renderOtherPreferences(): key = $key, value = $value");
		if (0) deb ("report.renderOtherPreferences(): availability_index) = $availability_index");
		
		// Render the value of each attribute (including worker name)
		foreach	($worker as $key=>$value) {
			$rows .= " <td>{$value}</td>";
		}
		$rows .= "</tr>";
	}
	$block = '
	<!-- signup rows table 1 -->
	<table style="table-layout:auto; width:100%"><tr><td style="background:Yellow">
		<!-- signup rows table 2 -->
		<table style="table-layout:auto; width:100%" border="1" cellspacing="3"> 
			<tr>' .
				$header .
				$rows . '
			</tr>
		</table> 
		<!-- signup rows table 2 / --> 
	</td></tr></table> 
	<!-- signup rows table 1 / -->'
	;
	
	$section_title = 'Other Preferences';
	$out = renderBlockInShowHideWrapper($block, $section_title, '<h2>', $section_title . '</h2>');

	return $out;
}


function renderJobSignups($section_title=NULL, $include_details=true) {
	$include_details = false;  // TEMPORARY
	$jobs = getJobs();
	if (0) deb("report.renderJobSignups(): renderJobSignups(): getJobs():", $jobs);

	$person_table = AUTH_USER_TABLE;
	$offers_table = OFFERS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$season_workers_table = SEASON_WORKERS_TABLE;
	$season_liaisons_table = SEASON_LIAISONS_TABLE;
	$season_id = SEASON_ID;
	$select = "p.id as person_id, 
		p.first_name, 
		p.last_name, 
		o.instances, 
		j.id as job_id, 
		j.description,
		j.season_id as season_id,
		sw.id as season_worker_id,
		sw.last_response_timestamp,
		sw.liaison_action,
		sw.liaison_id
		";
	$from = "{$person_table} as p, 
		{$jobs_table} as j,
		{$season_workers_table} as sw
		LEFT JOIN {$offers_table} as o
			ON p.id = o.worker_id AND j.id = o.job_id AND j.season_id = o.season_id
		";
	$where = "j.season_id = {$season_id}
		AND sw.worker_id = p.id
		AND sw.season_id = {$season_id}
		";
	$order_by = "p.first_name, 
		p.last_name, 
		j.display_order";
	$signups = sqlSelect($select, $from, $where, $order_by, (0));
	if (0) deb ("report.renderJobSignups(): signups =", $signups); 
	
	foreach($signups as $index=>$signup) {
		$select = "*";
		$from = AUTH_USER_TABLE;
		$where = "id = " . $signup['liaison_id'];
		$liaison = sqlSelect($select, $from, $where, "", (0))[0];
		
		// Add liaison_name element to the signups array for the liaison
		$signups[$index]['liaison_name'] = ($liaison ? $liaison['liaison_first_name'] . " " . $liaison['liaison_last_name'] : "");
		if (0) deb ("report.renderJobSignups(): signup =", $signup);
	}
	if (0) deb ("report.renderJobSignups(): signups =", $signups);
	
	// Get the available liaisons for this season
	$select = 
		"l.id as id,
		l.first_name || ' ' || l.last_name as name";
	$from = 
		SEASON_LIAISONS_TABLE . " as sl, " .
		AUTH_USER_TABLE . " as l";
	$where = 
		"sl.season_id = " . SEASON_ID . 
		" AND l.id = sl.worker_id";
	$order_by = "l.first_name, l.last_name";
	$season_liaisons = sqlSelect($select, $from, $where, $order_by, (0));

	// Get the available liaison actions for this season
	$select = "action, display_order";
	$from = LIAISON_ACTIONS_LOV;
	$where = "active = 1";
	$order_by = "display_order";
	$liaison_actions = sqlSelect($select, $from, $where, $order_by, (0));

	// Make header rows for the table
	$job_names_header = '
		<!-- jobnames header tr -->
		<tr style="text-align:center;"> 
			<th style="text-align:center;" rowspan="2">
				worker
			</th>';
	$data_types_header = '
		<tr style="text-align:center;"> 
		<!-- datatypes header tr -->';	
	$job_names_header .= '<th style="text-align:center;" rowspan="2">when took survey</th>';
	foreach($jobs as $index=>$job) {		
		if (0) deb ("report.renderJobSignups(): job['description']) = {$job['description']}");
		if (UserIsAdmin() && $include_details) {
			$job_names_header .= '<th colspan="3" style="text-align:center;">' . $job['description'] . '</th>';
			$data_types_header .= '<th style="text-align:center;">assigned</th>';
			$data_types_header .= '<th style="text-align:center;">available</th>'; 
		} else {
			$job_names_header .= '<th rowspan="2" style="text-align:center;">' . $job['description'] . ' signups</th>';			
		}
		// $data_types_header .= '<th style="text-align:center;">signups</th>';
		// if (userIsAdmin() && $include_details) {
			// $data_types_header .= '<th style="text-align:center;">assigned</th>';
			// $data_types_header .= '<th style="text-align:center;">available</th>'; 
		// }
	}
	if (userIsAdmin()) {
		$job_names_header .= '
			<th style="text-align:center;" colspan="3">
				<input type="button" name="view_element" id="edit_liaisons_th" value="Edit Liaison Info" onclick="toggleMode(\'edit\')">  
				<input type="submit" name="edit_element" id="save_changes_th" value="Save Changes" onclick="toggleMode(\'view\')" style="display:none"> 
				<input type="reset" name="edit_element" id="cancel_changes_th" value="Cancel Changes" onclick="toggleMode(\'view\')" style="display:none">
			</th>';
		$data_types_header .= '<th style="text-align:center;">liaison</th>';
		$data_types_header .= '<th style="text-align:center;">suggested action</th>';
		$data_types_header .= '<th style="text-align:center;">liaison reports</th>'; 
	} else {
		$job_names_header .= '<th style="text-align:center;" rowspan="2">liaison</th>';
	}
	

	$job_names_header .= "
		</tr> 
		<!-- jobnames header tr / -->";
	$data_types_header .= "
		</tr> 
		<!-- datatypes header tr / -->";
	$header = $job_names_header . $data_types_header;

	if (0) deb ("report.renderJobSignups(): job_names_header =", $job_names_header); 
	
	// Render data rows
	$responders_count = 0;
	$prev_person_id = 0;
	$signup_rows = '';
	$n_jobs = sqlSelect("count(*) as n_jobs", SURVEY_JOB_TABLE, "season_id = " . SEASON_ID, "", (0))[0]['n_jobs'];
	$job_n = 1;
	$header_interval = 24;
	$row_n = 1;
	foreach($signups as $signup) {
		if (0) deb ("report.renderJobSignups(): signup['job_id']) = {$signup['job_id']}");
		if (0) deb ("report.renderJobSignups(): signup) =", $signup);
		if (0) deb ("report.renderJobSignups(): availability_index) = " . $availability_index);
		
		// If this is a new person, start a new row
		if ($signup['person_id'] != $prev_person_id) {
			
			if ($prev_person_id != "") $signup_rows .= '
				</tr>  
				<!-- signup rows tr / -->';
			$signup_rows .= '
				<tr> 
				<!-- signup rows tr -->';

			// Render another header every header_interval rows
			if ($row_n == $header_interval) {
				$signup_rows .= $header;
				$row_n = 1;
			} else {
				$row_n++;
			}
			
			// Render name
			$signup_rows .= '<td>' . $signup['first_name'] . ' ' . $signup['last_name'] . '</td>';

			// Render signup timestamp
			$last_response_timestamp = $signup['last_response_timestamp'];
			$when_took_survey = ($last_response_timestamp ? date_format(date_create($last_response_timestamp), "D n/j ga") : "");
			$signup_rows .= '<td>' . $when_took_survey . '</td>';
			
			if ($last_response_timestamp) $responders_count++;
			$prev_person_id = $signup['person_id'];
		}
			
		// Render the number of times this person will do this job
		if (0) deb("report.renderJobSignups(): signup['person_id'] =? prev_person_id) AFTER =", $signup['person_id'] . "=?" . $prev_person_id);
		$person_signups_for_job = ($signup['instances'] > 0 ? $signup['instances'] : '');
		$signup_rows .= "
			<td>{$person_signups_for_job}</td>";

		// Increment the total number of signups for this job
		if (0) deb ("report.renderJobSignups(): signup['job_id']) =", $signup['job_id']);
		$job = array_search($signup['job_id'], array_column($jobs, 'job_id'));
		$jobs[$job]['signups'] += $signup['instances'];
		if (0) deb ("report.renderJobSignups(): jobs[job]['signups'] =", $jobs[$job]['signups']);

		if (userIsAdmin() && $include_details) {
			// Render the number of times this person is available for this job (= signups-assignments)
			$assignments = getJobAssignments(NULL, $signup['job_id'], $signup['person_id']);
			$assignments_count = count($assignments);
			$available_count = $signup['instances'] - $assignments_count;
			$available_count = ($available_count > 0 ? $available_count : '');
			$assignments_count = ($assignments_count > 0 ? $assignments_count : '');
			$available_background = ($available_count != '' ? 'style="background:lightpink;" ' : '');
			$signup_rows .= "
				<td>{$assignments_count}</td>";
			$signup_rows .= "
				<td {$available_background}>{$available_count}</td>";
		}		

		// If all jobs have been rendered, render the remaining columns (including edit controls)
		if ($job_n == $n_jobs) {		
			
			// Render the liaison
			$signup_rows .= '<td>';
			$current_liaison = sqlSelect("id, first_name || ' ' || last_name as name", AUTH_USER_TABLE, "id = " . $signup['liaison_id'], "", (0))[0];			
			$signup_rows .= $current_liaison['name'];
			$signup_rows .= '
				<!-- liaisons_span -->
				<span name="edit_element"  id="liaisons_span" style="display:none"> ';
			if (userIsAdmin()) {
				if (0) deb ("report.renderJobSignups(): signup['season_worker_id'] =" . $signup['season_worker_id']);
				if ($current_liaison) $signup_rows .= '<br>';
				$signup_rows .= '<select style="font-size:9pt;" name="liaisons[]">';
				$none_selected = (!$current_liaison) ? ' selected ' : '';
				$signup_rows .= '<option value="' . $signup['season_worker_id'] . '.' . '" ' . $none_selected . '></option>';
				foreach($season_liaisons as $season_liaison) {
					$liaison = sqlSelect("first_name || ' ' || last_name as name", AUTH_USER_TABLE, "id = " . $season_liaison['id'], "", (0))[0];			
					if (0) deb ("report.renderJobSignups(): liaison['name'] =" . $liaison['name']);
					$this_selected = ($current_liaison['id'] == $season_liaison['id']) ? ' selected ' : '';
					$signup_rows .= '<option value="' . $signup['season_worker_id'] . '.' . $season_liaison['id'] . '" ' . $this_selected . '>' . $liaison['name'] . '</option>';
				}  
				$signup_rows .= '</select>';
				$signup_rows .= '
					<!-- liaisons_span -->
					</span> ';	
			}
			$signup_rows .= '</td>';

			// Render admin-only columns
			if (userIsAdmin()) {
			
				// Render the liaison action
				$signup_rows .= '
					<!-- liaison_actions_cell_td -->
					<td> ';
				$current_action = $signup['liaison_action'];			
				$signup_rows .= $current_action;
				$signup_rows .= '
					<!-- liaison_actions_span -->
					<span name="edit_element" id="liaison_actions_span" style="display:none"> ';
				if (0) deb ("report.renderJobSignups(): signup['season_worker_id'] =" . $signup['season_worker_id']);
				if ($current_action) $signup_rows .= '<br>';
				$signup_rows .= '<select style="font-size:9pt;" name="liaison_actions[]">';
				$none_selected = (!$current_action) ? ' selected ' : '';
				$signup_rows .= '<option value="' . $signup['season_worker_id'] . '.' . '" ' . $none_selected . '></option>';
				foreach($liaison_actions as $liaison_action) {
					if (0) deb ("report.renderJobSignups(): liaison_action['name'] =" . $liaison_action['name']);
					$this_selected = ($current_action == $liaison_action['action']) ? ' selected ' : '';
					$signup_rows .= '<option value="' . $signup['season_worker_id'] . '.' . $liaison_action['action'] . '" ' . $this_selected . '>' . $liaison_action['action'] . '</option>';
				}  
				$signup_rows .= '</select>';
				$signup_rows .= '</span> 
					<!-- liaison_actions_span / -->';	
				$signup_rows .= '</td> 
					<!-- liaison_actions_cell_td / -->';
			
				// Render the liaison reports
				$reports = sqlSelect("*", LIAISON_REPORTS_TABLE, "season_worker_id = " . $signup['season_worker_id'], "timestamp asc", (0));
				$signup_rows .= '
					<!-- liaison_reports_cell_td -->
					<td> ';
				if ($reports) { 
					$report_table = '<table> 
					<!-- liaison_report_table -->';
					$report_rows = '';
					foreach($reports as $report) {
						$when = date_format(date_create($report['timestamp']), "D n/j ga");
						
						// Render the report
						if ($report_rows) $report_rows .= '<tr><td><hr></td></tr>';
						$report_rows .= '
							<div name="view_element">';
						$report_rows .= '
								<tr>
									<td>
										<span style="font-style: italic;">' . $when . ':</span> ' . $report['report'];
						// $report_rows .= '
								// <tr>
									// <td>
										// <span style="font-style: italic;">' . $when . ':</span> ' . $report['report'] . '
									// </td>
								// </tr>';
	
						// Render control to delete this report
						$report_rows .= '<input type="checkbox" name="delete_report:' . $report['id'] . '" >delete me';
						$report_rows .= '
									</td>
								</tr>						
							</div>';
							
						// Render report update field
						$report_rows .= '
							<!-- liaison report update delete span -->
							<div name="edit_element" id="liaison_report_' . $report['id'] . '_span" style="display:none">';
						$report_rows .= '
								<!-- liaison_report_input_tr -->
								<tr> 
									<!-- liaison_report_input_td -->
									<td> 
										<!-- liaison_report_input -->
										<input 
											type="textarea"
											style="font-size:9pt; width:300px; resize:both; wrap:soft; overflow:auto;"										  
											name="update_report:' . $report['id']	. '"									
											value="' . $report['report'] . '"> 
											<!-- liaison_report_input / -->
									</td> 
									<!-- liaison_report_input_td / -->
								</tr> 
								<!-- liaison_report_input_tr / -->'
						;

						$report_rows .= '
							</div> 
							<!-- liaison report update delete span / -->';
					} 

					// Render field to add a new report
					$report_rows .= '
						<!-- liaison_report_add_span -->
						<span name="edit_element" id="liaison_report_' . $report['id'] . '_add_span" style="display:none">';
					$report_rows .= '
							<!-- liaison_report_input_add_tr -->
							<tr> 
								<!-- liaison_report_input_add_td -->
								<td> 
									<!-- liaison_report_add_input -->
									<input 
										type="textarea"
										style="font-size:9pt; width:300px; resize:both; wrap:soft; overflow:auto;"										  
										name="create_report:' . $signup['season_worker_id']	. '"									
										value=""> 
									<!-- liaison_report_add_input / -->
								</td> 
								<!-- liaison_report_input_add_td / -->
							</tr> 
							<!-- liaison_report_input_add_tr / -->';
					$report_rows .= '
						</span> 
						<!-- liaison_report_add_span / -->'; 
						
					
					
					// Assign rows to the table
					$report_table .= $report_rows;
					$report_table .= '
						</table> 
						<!-- liaison_report_table / -->';
					
					// Assign table to rows (could do this w/o a $report_table variable)
					$signup_rows .= $report_table;
				}
				$signup_rows .= '</td> 
				<!-- liaison_reports_cell_td / -->';
			} 			
			$job_n = 1;
		} else {
			$job_n++;
		} 
	} // signups loop
	$signup_rows .= '</tr>'; 

	$block = '
	<form id="liaisons_form" name="liaisons_form" action="' . makeURI("report.php", CRUMBS_IDS) . '" method="post">
		<!-- signup rows table 1 -->
		<table><tr><td style="background:Yellow"> 
			<!-- signup rows table 2 -->
			<table border="1" cellspacing="3"> 
				<!-- signup rows tr -->
				<tr>  ' .
					$header .
					$signup_rows . ' 
				</tr> 
				<!-- signup rows tr / -->
			</table> 
			<!-- signup rows table 2 / -->
		</td></tr></table> 
		<!-- signup rows table 1 / -->
	</form>' .
	$responders_count . ' people have responded.'
	;
	
	$out = renderBlockInShowHideWrapper($block, $section_title, '<h2>', $section_title . '</h2>', "block");
	
	if (0) deb ("report.renderJobSignups(): out =", $out);
	return $out;
}
?>
