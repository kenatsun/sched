<?php
require_once 'start.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
require_once 'participation.php';
require_once 'seasons_utils.php';
require_once 'admin_utils.php';

if (0) deb("index.php: start.");

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
		$page .= render_job_signups("<h3><em>What we've signed up for so far</em></h3>", FALSE);
		$page .= renderLink("<strong>View the Sign-Ups</strong>", PUBLIC_DIR . makeURI('/report.php', NEXT_CRUMBS_IDS));	
		$page .= renderLink("<strong>View the Schedule</strong>", PUBLIC_DIR . makeURI('/dashboard.php', NEXT_CRUMBS_IDS));			
	} else {
		if (0) deb("index.php: gonna display first survey page");
		// $page = build_survey($worker_id);
	}
}
else {
	if (0) deb("index.php: survey closed, so gonna display home page without user links");
	$formatted_date = date('r', DEADLINE);
	$page .= render_countdown();
	$page .= renderLink("<strong>View the Sign-Ups</strong>", PUBLIC_DIR . makeURI('/report.php', NEXT_CRUMBS_IDS));		
	$page .= renderLink("<strong>View the Schedule</strong>", PUBLIC_DIR . makeURI('/dashboard.php', NEXT_CRUMBS_IDS));	
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
		{$gold_star} marks the wonderful people who have signed up for meals jobs so far
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
	// $responder_ids = getResponders(); 
	// if (0) deb("index.renderPeopleListAsLinks(): responder_ids =", $responder_ids); 
	$gold_star = '&nbsp<img src="/display/images/goldstar02.png" height="12">';
	$white_star = '&nbsp<img src="/display/images/whitestar02.png" height="12">';

	foreach($workers as $worker) {
		if ($worker['first_name'] && $worker['last_name']) $space = " ";
		$worker['name'] = $worker['first_name'] . $space . $worker['last_name'];
		$where = "season_id = {$season_id} and worker_id = " . $worker['id'];
		$responded = sqlSelect("worker_id", SEASON_WORKER_TABLE, $where . " and first_response_timestamp is not null", "", (0))[0];
		if (0) deb("index.getPeopleAsLinks: responded", $responded); 
		// Count the number of jobs the worker has offered to do
		// $offers_count = sqlSelect("offers_count", SEASON_WORKER_TABLE, $where . " and offers_count > 0", "", (0))[0];
		if (0) deb("index.getPeopleAsLinks: offers_count", $offers_count); 
		
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
		// $responded = (in_array($worker['id'], $responder_ids) ? $gold_star : "");	
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


// /**
 // * @param[in] $worker_id string person's id from the _GET array
 // */
// function build_survey($worker_id) {
	// if (0) deb("index.build_survey: respondent_id = ", $worker_id);
	// $survey = new Survey1($worker_id);
	// if (0) deb("index.build_survey: survey = ", $survey);
	// return $survey->renderOffersList();
// }
?>
