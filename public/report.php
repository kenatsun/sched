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

function saveLiaisonData($post) {
	if (1) deb("report.saveLiaisonData(): post = ", $post);
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
	$from = SEASON_WORKER_TABLE . " as sw, " . AUTH_USER_TABLE . " as w";
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
	<table style="table-layout:auto; width:100%"><tr><td style="background:Yellow">
		<table style="table-layout:auto; width:100%" border="1" cellspacing="3">
			<tr>' .
				$header .
				$rows . '
			</tr>
		</table>
	</td></tr></table>'
	;
	
	$section_title = 'Other Preferences';
	$out = renderBlockInShowHideWrapper($block, $section_title, '<h2>', $section_title . '</h2>');

	return $out;
}


function renderJobSignups($section_title=NULL, $include_details) {
	$jobs = getJobs();
	if (0) deb("report.renderJobSignups(): renderJobSignups(): getJobs():", $jobs);

	$person_table = AUTH_USER_TABLE;
	$offers_table = OFFERS_TABLE;
	$jobs_table = SURVEY_JOB_TABLE;
	$season_worker_table = SEASON_WORKER_TABLE;
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
		sw.liaison_action
		";
	$from = "{$person_table} as p, 
		{$jobs_table} as j,
		{$season_worker_table} as sw
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
		$from = LIAISONS_OF_WORKERS_TABLE;
		$where = "season_worker_id = " . seasonWorkerId($signup['person_id'], SEASON_ID);
		$liaison = sqlSelect($select, $from, $where, "", (0))[0];
		
		// Add elements to the $signups array for the liaison
		$signups[$index]['liaison_of_worker_id'] = $liaison['id'];
		$signups[$index]['season_liaison_id'] = $liaison['season_liaison_id'];
		$signups[$index]['liaison_name'] = ($liaison ? $liaison['liaison_first_name'] . " " . $liaison['liaison_last_name'] : "");
		if (0) deb ("report.renderJobSignups(): signup =", $signup);
	}
	if (0) deb ("report.renderJobSignups(): signups =", $signups);
	
	// Get the available liaisons for this season
	$select = "l.id as l_id,
		sl.id as sl_id,
		l.first_name || ' ' || l.last_name as l_name";
	$from = SEASON_LIAISONS_TABLE . " as sl, " .
		AUTH_USER_TABLE . " as l";
	$where = "sl.season_id = " . SEASON_ID . 
		" AND l.id = sl.worker_id";
	$order_by = "l.first_name, l.last_name";
	$season_liaisons = sqlSelect($select, $from, $where, $order_by, (0));

	// Make header rows for the table
	$job_names_header = '<tr style="text-align:center;"><th rowspan="2"></th>';
	// $data_types_header = '<tr style="text-align:center;"><th></th>';	
	$job_names_header .= '<th style="text-align:center;" rowspan="2">when took survey</th>';
	foreach($jobs as $index=>$job) {		
		if (0) deb ("report.renderJobSignups(): job['description']) = {$job['description']}");
		$job_names_header .= '<th colspan="' . (UserIsAdmin() && $include_details ? 3 : 1) . '" style="text-align:center;">' . $job['description'] . "</th>";
		$data_types_header .= '<th style="text-align:center;">signups</th>';
		if (userIsAdmin() && $include_details) {
				$data_types_header .= '<th style="text-align:center;">assigned</th>';
				$data_types_header .= '<th style="text-align:center;">available</th>'; 
			}
	}
	$job_names_header .= '
		<th style="text-align:center; background:white;" colspan="3">
			<input type="button" value="Edit Liaison Info" onclick="toggleMode(\'edit\')"> 
			<input type="submit" name="edit_control" value="Save Changes" onclick="toggleMode(\'view\')" style="display:none"> 
			<input type="reset" name="edit_control" value="Cancel Changes" onclick="toggleMode(\'view\')" style="display:none">
		</th>';
	$data_types_header .= '<th style="text-align:center;">liaison</th>';
	$data_types_header .= '<th style="text-align:center;">liaison action</th>';
	$data_types_header .= '<th style="text-align:center;">liaison reports</th>'; 

	$job_names_header .= "</tr>";
	$data_types_header .= "</tr>";
	// if ($include_details==FALSE) $data_types_header = NULL;
	if (0) deb ("report.renderJobSignups(): job_names_header =", $job_names_header); 
	
	// Render data rows
	$responders_count = 0;
	$prev_person_id = 0;
	$signup_rows = '';
	$n_jobs = sqlSelect("count(*) as n_jobs", SURVEY_JOB_TABLE, "season_id = " . SEASON_ID, "", (0))[0]['n_jobs'];
	$job_n = 1;
	foreach($signups as $signup) {
		if (0) deb ("report.renderJobSignups(): signup['job_id']) = {$signup['job_id']}");
		if (0) deb ("report.renderJobSignups(): signup) =", $signup);
		if (0) deb ("report.renderJobSignups(): availability_index) = " . $availability_index);
		
		// If this is a new person, start a new row
		if ($signup['person_id'] != $prev_person_id) {
			if ($prev_person_id != "") $signup_rows .= "</tr>";
			$signup_rows .= '<tr>';

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

		// If all jobs have been rendered, render the remaining columns
		if ($job_n == $n_jobs) {		
			
			// $view_or_edit = "view";
			// $signup_rows .= '<input type="hidden" id="view_or_edit" value="' . $view_or_edit . '">;

			// Render the liaison's name
			$signup_rows .= '<td>';
			$signup_rows .= $signup['liaison_name'];
			$signup_rows .= '<span name="edit_control"  style="display:none">';
			if (userIsAdmin()) {
				if ($signup['liaison_name']) $signup_rows .= '<br>';
				$signup_rows .= '<select style="font-size:9pt;" name="sw_sl[]">';
				$current_sl_id = $signup['season_liaison_id'];
				$none_selected = (!$current_sl_id) ? ' selected ' : '';
				$signup_rows .= '<option value="" ' . $none_selected . '></option>';
				foreach($season_liaisons as $season_liaison) {
					$this_selected = ($current_sl_id == $season_liaison['sl_id']) ? ' selected ' : '';
					$signup_rows .= '<option value="' . $signup['season_worker_id'] . '.' . $season_liaison['sl_id'] . '" ' . $this_selected . '>' . $season_liaison['l_name'] . '</option>';
				}  
				$signup_rows .= '</select>';
			$signup_rows .= '</span>';

				
			}
			$signup_rows .= '</td>';

			
			// Render the liaison action
			$signup_rows .= '<td>' . $signup['liaison_action'] . '</td>';
			
			// Render the liaison contacts
			$reports = sqlSelect("*", LIAISON_REPORTS_TABLE, "liaison_of_worker_id = " . $signup['liaison_of_worker_id'], "timestamp asc", (0));
			if ($reports) { 
				$report_table = '<table>';
				$report_rows = '';
				foreach($reports as $report) {
					$when = date_format(date_create($report['timestamp']), "D n/j ga");
					// if ($report_rows) $hr = '<tr><td><hr></td></tr>'; else $hr = '';
					if ($report_rows) $report_rows .= '<tr><td><hr></td></tr>';
					$report_rows .= '
						<tr>
							<td><span style="font-style: italic;">' . $when . ':</span> ' . $report['report'] . '</td>
						</tr>';
				}
				$report_table .= $report_rows;
				$report_table .= '</table>';
			} else {
				$report_table = '';				
			}
			$signup_rows .= '<td>' . $report_table . '</td>';
			
			$job_n = 1;
		} else {
			$job_n++;
		}
	}
	$signup_rows .= '</tr>'; 

	$block = '
	<form id="liaisons_form" name="liaisons_form" action="' . makeURI("report.php", CRUMBS_IDS) . '" method="post">
		<table><tr><td style="background:Yellow">
			<table border="1" cellspacing="3">
				<tr>' .
					$job_names_header .
					$data_types_header .
					$needed_row .
					$totals_row .
					$shortfall_row .
					$signup_rows . ' 
				</tr>
			</table>
		</td></tr></table>
	</form>' .
	$responders_count . ' people have responded.'
	;
	
	$out = renderBlockInShowHideWrapper($block, $section_title, '<h2>', $section_title . '</h2>', "block");
	
	if (0) deb ("report.renderJobSignups(): out =", $out);
	return $out;
}
?>
