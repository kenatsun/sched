<?php
require_once "globals.php";
require_once "utils.php";
require_once "classes/person.php";
require_once "classes/survey.php";
require_once "git_ignored.php";

function finishSurvey($survey, $person_id) {
	if (0) deb("finish.finishSurvey(): survey:", $survey);
	if (0) deb("finish.finishSurvey(): _POST =", $_POST);
	$dir = BASE_DIR;
	$person = new Person($person_id);	
	$person_email = $person->email;
	$person_name = $person->name;
	$person_first_name = $person->first_name;
	$community = COMMUNITY;
	$timestamp = date("F j, Y, g:i a");
	$summary_text = "";
	$deadline = date('g:ia l, F j', DEADLINE);
	$sending_email = (($_POST['send_email'] == 'yes' && !SKIP_EMAIL) || $person_email == 'ken@sunward.org') ? 1 : 0; 
	$email_coming = ($sending_email) ? "<p>We're also sending you an email containing this info (to {$person_email}), for your reference." : "";
	if (0) deb("finish.finishSurvey(): email_coming =", $email_coming);
		
	if (!$survey == NULL) {
		$insufficient_prefs_msg = $survey->insufficient_prefs_msg;
		$summary_text .= renderJobOffers($survey, $person);
		$summary_text .= renderShiftPreferences($survey);
		$summary_text .= renderCleanAfterCook($survey);
		$summary_text .= renderCoworkers($survey);
		$summary_text .= renderComments($survey);
		if (0) deb("finish.displayResultsPage(): worker:", $worker);
		if (0) deb("finish.displayResultsPage(): summary_text:", $summary_text);
	} else {
		$insufficient_prefs_msg = '';
	}
	$headline = renderHeadline("Thank you, {$person_name}!", HOME_LINK . SIGNUPS_LINK . $person_id); // . PREFS_LINK . $person_id);
	$out = <<<EOHTML
	{$headline}
<p>Dear {$person_first_name} ~
<p>Thanks for completing your {$community} meals scheduling survey!
<p>The preferences you have expressed (as of {$timestamp}) are shown below.
{$email_coming}
<p>~ The Sunward More Meals Committee (Suzanne, Ken, Mark & Ed)</p>

{$summary_text}
<br>
<p>You may <a href="{$dir}/index.php?person={$person->id}">change your responses</a>
	or <a href="{$dir}/index.php">take the survey for another person</a> at any time until {$deadline}, 
	when the survey closes.</p>
EOHTML;
	if (0) deb("finish.displayResultsPage(): worker = ", $worker);
	if (0) deb("finish.displayResultsPage(): summary = ", $summary);
	if (0) deb("finish.php: SKIP_EMAIL = ", SKIP_EMAIL);
	if ($sending_email) sendEmail($person_id, $summary_text, $insufficient_prefs_msg);
	print $out; 
}

function renderJobOffers($survey, $person) {
	$season_name = get_season_name_from_db(SEASON_ID);
	if (0) deb("finish.renderJobOffers(): shifts_summary = ", $survey->shifts_summary);
	return "
<br>
<h3>Your Job Sign-Ups:</h3>{$survey->shifts_summary}
";
}

/**
 * Render a notification message when the data have been saved.
 * @return string HTML of the notification message.
 */
function renderShiftPreferences($survey) {

	if (0) deb("finish.renderShiftPreferences(): survey->worker_id = $survey->worker_id");
	if (0) deb("finish.renderShiftPreferences(): survey = ", $survey);
	if (0) deb("finish.renderShiftPreferences(): survey->summary = ", $survey->summary);

	if (!empty($survey->summary)) {
		if (0) deb("finish.renderShiftPreferences(): summary = ", $survey->summary);
		if (0) deb("finish.renderShiftPreferences(): count(summary):", count($survey->summary));
		$num_jobs = count($survey->summary);
		if (0) deb("finish.renderShiftPreferences(): num_jobs:", $num_jobs);
		$pref_num = 0;
		
		// Get all jobs this worker has offered to do
		$select = "distinct j.*";
		$from = SCHEDULE_SHIFTS_TABLE . " as s,
			" . SCHEDULE_PREFS_TABLE . " as p,
			" . SURVEY_JOB_TABLE . " as j";
		$where = "worker_id = {$survey->worker_id}
			and s.id = p.shift_id
			and s.job_id = j.id
			and j.season_id = " . SEASON_ID;
		$order_by = "j.display_order asc";
		$jobs = sqlSelect($select, $from, $where, $order_by, (0), "finish.renderShiftPreferences(): jobs");
		
		foreach($jobs as $job) {
			// Get all shift_prefs of this worker for this job
			$select = "p.*, 
				m.date,
				j.display_order,
				j.description as job_name,
				j.id as job_id";
			$from = SCHEDULE_SHIFTS_TABLE . " as s,
				" . SCHEDULE_PREFS_TABLE . " as p,
				" . MEALS_TABLE . " as m,
				" . SURVEY_JOB_TABLE . " as j";
			$where = "worker_id = {$survey->worker_id}
				and s.meal_id = m.id
				and s.id = p.shift_id
				and s.job_id = j.id
				and j.id = {$job['id']}";
			$order_by = "m.date asc, p.pref desc";
			$shift_prefs = sqlSelect($select, $from, $where, $order_by, (0), "finish.renderShiftPreferences(): shift_prefs");
			
			$previous_month = date_format(date_create($shift_prefs[0]['date']), "M");
			// $shift_pref_lines = "";
			foreach($shift_prefs as $shift_pref) {
				$pref_num++;
				$class = ($pref_num == $num_jobs ? 'summary_listing_last' : 'summary_listing');
				$date_ob = date_create($shift_pref['date']);
				if (0) deb("finish.renderShiftPreferences(): job_num = $job_id, info = ", $date_job_prefs);
				if (0) deb("finish.renderShiftPreferences(): job_num, class:", $pref_num . ', ' . $class);
				$job_name = $shift_pref['job_name'];
				$month = date_format($date_ob, "M");
				if ($month !== $previous_month) $shift_pref_lines .= "\n<br>";
				$previous_month = $month;
				$date = date_format($date_ob, "D M j");
				$pref = $shift_pref['pref'];
				$pref_name = sqlSelect("name", SHIFT_PREF_NAMES_TABLE, "level = {$pref}", "", (0))[0]['name'];
				$shift_pref_line = "{$date} : {$pref_name}";
				if ($pref_name == "prefer" || $pref_name == "ok") $shift_pref_line = "<strong>" . $shift_pref_line . "</strong>";
				$shift_pref_lines .= $shift_pref_line . "\n<br>";
			}
			$summary_text .= "<td class=\"{$class}\">
				\n<p><strong>{$job_name}</strong></p>
				\n{$shift_pref_lines}</td>";
			$shift_pref_lines = "";
		}

		$summary_text = <<<EOHTML

<h3>Your Date Preferences:</h3><table class="pref_listing" style="font-size: 11pt;"> <tr>
		{$summary_text} </tr></table>
EOHTML;
	}
	if (0) deb("finish.renderShiftPreferences: summary_text:", $summary_text);
	return $summary_text;
}

function renderCleanAfterCook($survey) {
	if ($survey->worker_is_both_cook_and_cleaner()) {
		$clean_after_self = $survey->requests['clean_after_self'];
		if (0) deb("finish.renderCleanAfterCook(): clean_after_self:", $clean_after_self);
		return "
<h3>Clean after Cooking?</h3>
$clean_after_self
		";
	} else {
		return;
	}
}

function renderCoworkers($survey) {
	$out = '';
	if ($survey->avoid_list) {
		$out .= "
<h3>You Avoid Scheduling with:</h3>
{$survey->avoid_list}
";
	}
	if ($survey->prefer_list) {
		$out .= "
<h3>You Prefer Scheduling with:</h3>
{$survey->prefer_list}
";
	}
	return $out;
}

function renderComments($survey) {
	$comments = $survey->requests['comments'];
	if (0) deb("finish.renderCleanAfterCook(): comments:", $comments);
	if (!$comments == '') {
		return "	
\n<h3>Your comments for the More Meals Committee:</h3>
<pre>$comments</pre>
";
	} else {
		return;
	}
}


/**
 * Send an email message with the results.
 * @param[in] content string the summary results.
 */
function sendEmail($worker_id, $content, $insufficient_prefs_msg) {
	$person = new Person($worker_id);	
	$person_email = $person->email;
	$person_name = $person->name;
	$person_first_name = $person->first_name;
	$community = COMMUNITY;
	$timestamp = date("F j, Y, g:i a");
	
	$instance = INSTANCE;
	$database = DATABASE;
	$instance_label = (INSTANCE ? "\nThis is from the {$instance} instance.  Database = {$database}\n" : "");
	if (0) deb("finish.sendEmail(): person:", $person);
	if (0) deb("finish.sendEmail(): person_email:", $person_email);
	if (0) deb("finish.sendEmail(): content:", $content . "<p>END</p>");
	$content = strip_tags($content);  // Strip HTML tags
	if (0) deb("finish.sendEmail(): tagless content: {$content}", "<p>END</p>");
	// $content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $content);  // Remove empty lines
	// if (0) deb("finish.sendEmail(): empty_lineless_content: {$content}", "<p>END</p>");
	$email_body = "Dear {$person_name} ~\n\n" .
		"Thanks for completing the {$community} meals scheduling questionnaire!\n\n";
	if (!$content == "") {
		$email_body .= "Here are the preferences you have expressed as of "  . $timestamp . ":" . $content;
	} else {
		$email_body .= "You have told us that you won't be doing any meal jobs this season.\n";	
	}
	$deadline = date('g:ia l, F j', DEADLINE);
	$url = getSurveyURL();
	$email_body .= "\nYou may revise your responses to this questionnaire at any time until $deadline, when the survey closes, by going to {$url}.\n\n" .
	"~ The Sunward More Meals Committee (Suzanne, Ken, Mark & Ed)
	{$instance_label}";
	if (0) deb("finish.sendEmail: SKIP_EMAIL = ", SKIP_EMAIL);
	// if (SENDING_EMAIL) {
	// if (($_POST['send_email'] == 'yes' && !SKIP_EMAIL) || $person_email == 'ken@sunward.org') {
	// if (!SKIP_EMAIL || $person_email == 'ken@sunward.org') {
		$sent = mail($person_email,
			'Meal Scheduling Survey preferences saved on ' . $timestamp,
			$email_body,
			'From: moremeals@sunward.org');

		if (!$sent) {
			error_log("Unable to send email to: $to");
		}

		// if user is under pref level, then send warning email
		if (!is_null($insufficient_prefs_msg)) {
			$sent = mail('ken@sunward.org',
				'Meal Scheduling Survey preferences saved under limit at ' . $timestamp,
				$person_name . "\n" . strip_tags($content . "\n" .
					$insufficient_prefs_msg),
				'From: moremeals@sunward.org');
		}
	// }
	// return $sent;
}

?>