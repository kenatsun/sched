<?php
require_once 'start.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
require_once 'participation.php';
require_once 'admin_utils.php';

if (0) deb("index.php: start.");

$season_id = getSeason('id');
$season_name = get_season_name_from_db($season_id);

// Check to see if the database is writable: 
global $db_is_writable;
if (!$db_is_writable) {
	echo '
		<div class="warning">
			ERROR: Database is not writable
		</div>'
	;
}

// Make the page
global $extended;
if (0) deb("index: userIsAdmin() = " . userIsAdmin());
$page .= renderHeadline("Sunward Meals Scheduling - {$season_name} Survey", "");
$extended = (sqlSelect("*", SEASONS_TABLE, "id = " . SEASON_ID, "")[0]['survey_extended']) ? 1 : 0;
$now = time();
if ($now <= DEADLINE || $extended || userIsAdmin()) {
	// If a person has been specified in the request, do their survey
	// else display the list of all respondents as links
	$worker_id = array_get($_GET, 'person');
	if (0) deb("index: person from _GET:", $worker_id);
	if (0) deb("index: _GET:", $_GET);
	if (0) deb("index: NEXT_CRUMBS_IDS: " . NEXT_CRUMBS_IDS);
	if (is_null($worker_id)) {
		if (0) deb("index.php: gonna display home page");
		$page .= render_countdown();
		if ($extended) $page .= "...but briefly re-opened to admit a few last-minute responses!";
		$page .= render_instructions();
		$page .= render_person_menu();
		$page .= render_footer();
		$page .= renderScoreboard("<h3><em>What we've signed up for so far</em></h3>");
		$page .= renderLink("<strong>View the Sign-Ups</strong>", PUBLIC_DIR . makeURI('/report.php', NEXT_CRUMBS_IDS));	
		if (scheduler_run()['id']) $page .= renderLink("<strong>View the Schedule</strong>", PUBLIC_DIR . makeURI('/teams.php', NEXT_CRUMBS_IDS));			
	} else {
		if (0) deb("index.php: gonna display first survey page");
	}
}
else {
	if (0) deb("index.php: survey closed, so gonna display home page without user links");
	$formatted_date = date('r', DEADLINE);
	$page .= render_countdown();
	$page .= renderLink("<strong>View the Sign-Ups</strong>", PUBLIC_DIR . makeURI('/report.php', NEXT_CRUMBS_IDS));		
	$page .= renderLink("<strong>View the Schedule</strong>", PUBLIC_DIR . makeURI('/teams.php', NEXT_CRUMBS_IDS));	
}

print $page;


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function render_instructions() {
	$month_names = get_current_season_months();
	$deadline = date('g:i a l, F j', DEADLINE);
	if (0) deb("index.render_introduction(): deadline = ", $deadline);
	return '
		<br><br>
		<p class="question">To get started, just click on your name in this list:</p>'
;
}

function render_countdown() {
	$r = new Respondents();
	return '
		<div class="special_info">' .
			$r->getTimeRemaining() . '
		</div>'
	;
}

function render_person_menu() {
	$workers = renderPeopleListAsLinks();
	if (empty($workers)) {
		return "<h2>No respondents configured</h2>\n";
	}
	$gold_star = '<img src="/display/images/goldstar02.png" height="12">&nbsp';
	$html = '
		<table  class="workers_list"  style="font-size:11pt">
			<tr>' .
				$workers . '
			</tr>
		</table>' .
		$gold_star . ' marks the wonderful people who have signed up for meals jobs so far
	<br><br>'
	;
	return $html;
}

function render_footer() {
	return $html;
	$html = <<<EOHTML
	<div>
	<p>If you have any questions, concerns, or problems with this questionnaire, please send us a note at <a href="mailto:moremeals@sunward.org?Subject=Help with the Willing Workers Questionnaire" target="_top">moremeals@sunward.org</a>.</p> 
	<p>We <em>do</em> make house calls!  Just ask.</p> 
	</div>
EOHTML;
}


/**
 * Render the list of people as links in order to select their survey.
 */
function renderPeopleListAsLinks() {
	$workers_table = AUTH_USER_TABLE;
	$season_workers_table = SEASON_WORKER_TABLE;
	$season_id = SEASON_ID;
	$from = "{$workers_table} as w, {$season_workers_table} as sw";
	$where = "sw.worker_id = w.id and sw.season_id = {$season_id}";
	$order_by = "w.first_name, w.last_name";
	$workers = sqlSelect("w.*", $from, $where, $order_by, (0), "index.renderPeopleListAsLinks(): workers =");
	if (0) deb("index.getPeopleAsLinks: respondents:", $workers); 
	$lines = '';
	$count = 0;
	$signups_table = OFFERS_TABLE;
	$gold_star = '&nbsp<img src="/display/images/goldstar02.png" height="12">';
	$white_star = '&nbsp<img src="/display/images/whitestar02.png" height="12">';

	foreach($workers as $worker) {
		if ($worker['first_name'] && $worker['last_name']) $space = " ";
		$worker['name'] = $worker['first_name'] . $space . $worker['last_name'];
		$where = "season_id = {$season_id} and worker_id = " . $worker['id'];
		$responded = sqlSelect("worker_id", SEASON_WORKER_TABLE, $where . " and first_response_timestamp is not null", "", (0))[0];
		if (0) deb("index.getPeopleAsLinks: responded", $responded); 

		// Count the number of jobs the worker has offered to do		
		if ($responded) { 
			$select = "sum(instances) as offers_count";
			$from = OFFERS_TABLE;
			$offers_count = sqlSelect($select, $from, $where, "", (0))[0]['offers_count'];
			if (0) deb("index.getPeopleAsLinks: responded", $responded); 
			if (0) deb("index.getPeopleAsLinks: offers_count", $offers_count); 
			if ($offers_count) {
				$medals = (userIsAdmin()) ? $gold_star . " " . $offers_count : $gold_star; 
			}
			else $medals = $white_star;	
		} else {
			$responded = 0;
			$offers_count = 0;
			$medals = "";
		}
		$line = '<li><a href="' . makeURI("survey_page_1.php", NEXT_CRUMBS_IDS, 'person='. $worker["id"]) . '">' . $worker["name"] . '</a>' . $medals . '</li>';
		$lines .= $line;
		if (0) deb("index.getPeopleAsLinks: html line:", $line);

		$count++;
		if (($count % 12) == 0) {
			if (0) deb("PeopleList.renderPeopleListAsLinks(): html lines:", $lines);
			$out .= "<td><ol>{$lines}</ol></td>\n";
			$lines = '';
		}
		if (0) deb("PeopleList.renderPeopleListAsLinks(): person-as-link:", $worker);
	}

	if ($lines != '') {
		$out .= "<td><ol>{$lines}</ol></td>\n";
	}

	if (0) deb("PeopleList.renderPeopleListAsLinks(): out:", $out);
	return $out;
}


// function renderScoreboard($section_title=NULL) {
	// $jobs = getJobs();
	// if (0) deb("index.php: renderScoreboard(): getJobs():", $jobs);

	// $person_table = AUTH_USER_TABLE;
	// $offers_table = OFFERS_TABLE;
	// $jobs_table = SURVEY_JOB_TABLE;
	// $season_id = SEASON_ID;
	// $select = "p.id as person_id, 
		// p.first_name, 
		// p.last_name, 
		// o.instances, 
		// j.id as job_id, 
		// j.description";
	// $from = "{$person_table} as p, 
		// {$offers_table} as o, 
		// {$jobs_table} as j";
	// $where = "p.id = o.worker_id 
		// and o.job_id = j.id 
		// and j.season_id = {$season_id}";
	// $order_by = "p.first_name, p.last_name, j.display_order";
	// $signups = sqlSelect($select, $from, $where, $order_by);
	// if (0) deb ("index.renderScoreboard(): signups =", $signups);

	// // $signups = getJobSignups();
	// // if (0) deb("report.renderScoreboard(): getJobSignups() returns:", $signups);

	// // Make header rows for the table
	// $job_names_header = '<tr style="text-align:center;"><th></th>';
	// $data_types_header = '<tr style="text-align:center;"><th></th>';
	// foreach($jobs as $index=>$job) {		
		// if (0) deb ("report.renderScoreboard(): job['description']) = {$job['description']}");
		// $job_names_header .= '<th colspan="1" style="text-align:center;">' . $job['description'] . "</th>";
		// $data_types_header .= '<th style="text-align:center;">signups</th>';
	// }
	// $job_names_header .= "</tr>";
	// $data_types_header .= "</tr>";
	// if (0) deb ("index.renderScoreboard(): job_names_header =", $job_names_header); 
	
	// // Make data rows
	// $responders_count = 0;
	// $prev_person_id = 0;
	// $signup_rows = '';
	// foreach($signups as $index=>$signup) {
		// // If this is a new person, start a new row & print name in first column
		// if ($signup['person_id'] != $prev_person_id) {
			// if ($prev_person_id != "") $signup_rows .= "</tr>";
			// $signup_rows .= "
			// <tr>
				// <td>{$signup['first_name']} {$signup['last_name']}</td>";
			// $prev_person_id = $signup['person_id'];
			// $responders_count++;
		// }
		
		// if (0) deb ("index.renderScoreboard(): signup['job_id']) = {$signup['job_id']}");
		// if (0) deb ("index.renderScoreboard(): signup) =", $signup);
		// if (0) deb ("index.renderScoreboard(): availability_index) = $availability_index");
			
		// // Render the number of times this person will do this job
		// if (0) deb("index.renderScoreboard(): signup['person_id'] =? prev_person_id) AFTER =", $signup['person_id'] . "=?" . $prev_person_id);
		// $person_signups_for_job = ($signup['instances'] > 0 ? $signup['instances'] : '');
		// $signup_rows .= "
			// <td>{$person_signups_for_job}</td>";

		// // Increment the total number of signups for this job
		// if (0) deb ("index.renderScoreboard(): signup['job_id']) =", $signup['job_id']);
		// $job = array_search($signup['job_id'], array_column($jobs, 'job_id'));
		// $jobs[$job]['signups'] += $signup['instances'];
		// if (0) deb ("index.renderScoreboard(): jobs[job]['signups'] =", $jobs[$job]['signups']);

	// }
	// $signup_rows .= "</tr>";

	// $meals_in_season = sqlSelect("count(id) as id", MEALS_TABLE, "skip_indicator = 0 and season_id = " . SEASON_ID, (0))[0]['id'];
	// // Render a row showing total jobs to fill for each job
	// if (0) deb("utils.renderScoreboard(): meals_in_season = ", $meals_in_season);
	// $needed_row = "<tr>
		// <td {$background}><strong>jobs to fill</strong></td>";
	// foreach($jobs as $index=>$job) {
		// if (0) deb("utils.renderScoreboard(): job['instances'] = ", $job['instances']);
		// $shifts_count = $meals_in_season * $job['workers_per_shift'];
		// $needed_row .= "<td {$background}><strong>" . $shifts_count . "</strong></td>";
	// }
	// $needed_row .= "</tr>";

	// // Render a row showing total signups for each job
	// $background = ' style="background:white;" ';
	// $totals_row = "<tr>
		// <td {$background}><strong>signups so far</strong></td>";
	// foreach($jobs as $index=>$job) {
		// $totals_row .= "<td {$background}><strong>{$job['signups']}</strong></td>";
	// }
	// $totals_row .= "</tr>";
	
	// // Render a row showing total signups shortfall for each job
	// $shortfall_row = "<tr>
		// <td {$background}><strong>signups still needed</strong></td>";
	// foreach($jobs as $index=>$job) {
		// $shifts_count = $meals_in_season * $job['workers_per_shift'];
		// $shortfall = $shifts_count - $job['signups'];
		// if ($shortfall == 0) $shortfall = '';
		// $shortfall_row .= "<td {$background}><strong>{$shortfall}</strong></td>";
	// }
	// $shortfall_row .= "</tr>";

	// $out = 
		// $section_title . '	
		// <div>
			// <table><tr><td style="background:Yellow">
				// <table border="1" cellspacing="3">
					// <tr>' .
						// $job_names_header .
						// $needed_row .
						// $totals_row .
						// $shortfall_row . '
					// </tr>
				// </table>
			// </td></tr></table> ' .
			// $responders_count . ' people have responded.
		// </div>'
	// ;
	// if (0) deb ("index.renderScoreboard(): out =", $out);
	// return $out;
// }

?>
