<?php
session_start();

require_once 'utils.php';
require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';

require_once 'display/includes/header.php';
require_once 'participation.php';
require_once 'seasons_utils.php';
require_once 'admin_utils.php';

if (0) deb("index.php: start");

$season_id = getSeason('id');
$season_name = get_season_name_from_db($season_id);

// Check to see if the database is writable: 
global $db_is_writable;
if (!$db_is_writable) {
	echo <<<EOHTML
		<div class="warning">
			ERROR: Database is not writable
		</div>
EOHTML;
}

// Make the page
global $extended;
if (0) deb("index: userIsAdmin() = " . userIsAdmin());
$page = '';
$page .= renderHeadline("Sunward Meals Scheduling - {$season_name} Survey");
// $page .= render_headline();
$extended = (sqlSelect("*", SEASONS_TABLE, "id = " . SEASON_ID, "")[0]['survey_extended']) ? 1 : 0;
$now = time();
if ($now <= DEADLINE || $extended || userIsAdmin()) {
	// If a person has been specified in the request, do their survey
	// else display the list of all respondents as links
	$worker_id = array_get($_GET, 'person');
	if (0) deb("index: person from array_GET:", $worker_id);
	if (0) deb("index: array dollarsign_GET:", $_GET);
	if (is_null($worker_id)) {
		$page .= render_countdown();
		if ($extended) $page .= "...but briefly re-opened to admit a few last-minute responses!";
		$page .= render_instructions();
		$page .= render_person_menu();
		$page .= render_footer();
		$page .= render_job_signups("<h3><em>What we've signed up for so far</em></h3>", FALSE);
		$page .= render_report_link("<strong>View the Sign-Ups</strong>");	
		$page .= render_schedule_link("<strong>View the Schedule</strong>");			
	} else {
		$page = build_survey($worker_id);
	}
}
else {
	$formatted_date = date('r', DEADLINE);
	$page .= render_countdown();
	$page .= render_report_link("View the schedule");		
	$page .= render_schedule_link("View the Assignments");	
}

print $page;


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function render_instructions() {
	$month_names = get_current_season_months();
	$deadline = date('g:i a l, F j', DEADLINE);
	if (0) deb("index.render_introduction(): deadline = ", $deadline);
	return <<<EOHTML
<p>  
<br>
<p class="question">To get started, just click on your name in this list:</p>
EOHTML;
}

function render_countdown() {
	$r = new Respondents();
	return <<<EOHTML
		<div class="special_info">
			{$r->getTimeRemaining()}
		</div>
EOHTML;
}

function render_person_menu() {
	$workers = renderPeopleListAsLinks();
	if (empty($workers)) {
		return "<h2>No respondents configured</h2>\n";
	}
	$gold_star = '<img src="/display/images/goldstar02.png" height="12">&nbsp';
	$html = <<<EOHTML
		<table  class="workers_list"  style="font-size:11pt">
			<tr>
			{$workers}
			</tr>
		</table>
		{$gold_star} marks the wonderful people who have responded to the questionnaire so far
	<br><br>
EOHTML;
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

function render_job_signups($headline, $render_details) {
	$signups = renderJobSignups($headline, $render_details);
	$html = <<<EOHTML
	<div>
	{$signups}
	</div>
EOHTML;
	return $html;
}

function render_report_link($text) {
	$dir = PUBLIC_DIR;
	return '<p class="summary_report"><a href="'. PUBLIC_DIR . '/report.php">' . $text . '</a></p>';
}

// function render_scheduler_link($text) {
	// return '<p class="summary_report"><a href="'. PUBLIC_DIR . '/run_scheduler_from_web.php">' . $text . '</a></p>';
// }

function render_schedule_link($text) {
	$dir = BASE_DIR;
	return '<p class="summary_report"><a href="'. PUBLIC_DIR . '/dashboard.php">' . $text . '</a></p>';
}


/**
 * Render the list of people as links in order to select their survey.
 */
function renderPeopleListAsLinks() {
	// $dir = BASE_DIR;
	$workers_table = AUTH_USER_TABLE;
	$season_workers_table = SEASON_WORKER_TABLE;
	$season_id = SEASON_ID;
	// $list = new PeopleList();
	// $workers = $list->getPeople();
	$from = "{$workers_table} as w, {$season_workers_table} as sw";
	$where = "sw.worker_id = w.id and sw.season_id = {$season_id}";
	$order_by = "w.first_name, w.last_name";
	$workers = sqlSelect("w.*", $from, $where, $order_by, (0), "index.renderPeopleListAsLinks(): workers =");
	if (0) deb("index.getPeopleAsLinks: respondents:", $workers); 
	$lines = '';
	$count = 0;
	$signups_table = OFFERS_TABLE;
	$responder_ids = getResponders(); 
	if (0) deb("index.renderPeopleListAsLinks(): responder_ids =", $responder_ids); 
	$gold_star = '&nbsp<img src="/display/images/goldstar02.png" height="12">';

	foreach($workers as $worker) {

		if ($worker['first_name'] && $worker['last_name']) $space = " ";
		$worker['name'] = $worker['first_name'] . $space . $worker['last_name'];
		$responded = (in_array($worker['id'], $responder_ids) ? $gold_star : "");	
		$line = <<<EOHTML
			<li><a href="/index.php?person={$worker["id"]}">{$worker["name"]}</a>{$responded}</li>
EOHTML;
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


/**
 * @param[in] $worker_id string person's id from the _GET array
 */
function build_survey($worker_id) {
	if (0) deb("index.build_survey: respondent_id = ", $worker_id);
	$survey = new Survey1($worker_id);
	if (0) deb("index.build_survey: survey = ", $survey);
	return $survey->renderOffersList();
}
?>
