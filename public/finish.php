<?php
require_once "globals.php";
require_once "utils.php";
require_once "classes/person.php";
require_once "classes/survey.php";

function finishSurvey($survey, $person_id) {
	if (0) deb("finish.displayResultsPage(): survey:", $survey);
	$dir = BASE_DIR;
	$person = new Person($person_id);	
	$person_email = $person->email;
	$person_name = $person->name;
	$person_first_name = $person->first_name;
	$community = COMMUNITY;
	$timestamp = date("F j, Y, g:i a");
	$summary_text = "";
	$deadline = date('g:ia l, F j', DEADLINE);
	if (!$survey == NULL) {
		$insufficient_prefs_msg = $survey->insufficient_prefs_msg;
		$summary_text .= renderJobOffers($survey, $person);
		$summary_text .= renderDatePreferences($survey);
		$summary_text .= renderCleanAfterCook($survey);
		$summary_text .= renderCoworkers($survey);
		$summary_text .= renderComments($survey);
		if (0) deb("finish.displayResultsPage(): worker:", $worker);
		if (0) deb("finish.displayResultsPage(): summary_text:", $summary_text);
	} else {
		$insufficient_prefs_msg = '';
		// $summary_text .= "Rats.";	
	}
	$headline = renderHeadline("Thank you, {$person_name}!");
	$out = <<<EOHTML
	{$headline}
<p>Dear {$person_first_name} ~
<p>Thanks for completing your {$community} meals scheduling questionnaire!
<p>The preferences you have expressed (as of {$timestamp}) are shown below.
<p>We're also sending you an email containing this info (to {$person_email}), for your reference.
<p>~ The Sunward More Meals Committee (Suzanne, Ken, Mark & Ed)</p>

{$summary_text}
<br>
<p>You may <a href="{$dir}/index.php?person={$person->id}">change your responses</a>
	or <a href="{$dir}/index.php">take the survey for another person</a> at any time until {$deadline}, 
	when the survey closes.</p>
EOHTML;
	print $out;
	if (0) deb("finish.displayResultsPage(): worker = ", $worker);
	if (0) deb("finish.displayResultsPage(): summary = ", $summary);
	sendEmail($person_id, $summary_text, $insufficient_prefs_msg);
}

function renderJobOffers($survey, $person) {
	$season_name = get_season_name_from_db(SEASON_ID);
	return "
<br>
<h3>Your Job Sign-Ups:</h3>
{$survey->shifts_summary}
";
}

/**
 * Render a notification message when the data have been saved.
 * @return string HTML of the notification message.
 */
function renderDatePreferences($survey) {
	$summary_text = '';
	$dates = '';

	if (!empty($survey->summary)) {
		if (0) deb("finish.renderDatePreferences(): summary:", $survey->summary);
		if (0) deb("finish.renderDatePreferences(): count(summary):", count($survey->summary));
		$num_jobs = count($survey->summary);
		if (0) deb("finish.renderDatePreferences(): num_jobs:", $num_jobs);
		$job_num = 0;
		
		foreach($survey->summary as $job_id=>$info) {
			$job_num++;
			$class = ($job_num == $num_jobs ? 'summary_listing_last' : 'summary_listing');
			if (0) deb("finish.renderDatePreferences(): job_num, class:", $job_num . ', ' . $class);
		
			$job_name = get_job_name($job_id);
			$dates = implode("\n<br>", $info); 
			
			$summary_text .= "
<td class=\"{$class}\">
<p><strong>{$job_name}</strong></p>
{$dates}
</td>
";
		}

		$summary_text = <<<EOHTML
<div>
<h3>Your Date Preferences:</h3>
	<table class="pref_listing" style="font-size: 11pt;"> 
		<tr>
		{$summary_text}
		</tr>
	</table>
</div>
EOHTML;
	}
	if (0) deb("finish.renderDatePreferences: summary_text:", $summary_text);
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
<h3>Comments for the More Meals Committee:</h3>
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
	if (0) deb("survey.sendEmail(): person:", $person);
	if (0) deb("survey.sendEmail(): person_email:", $person_email);
	if (0) deb("survey.sendEmail(): content:", $content . "<p>END</p>");
	$tagless_content = strip_tags($content);
	$empty_lineless_content = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $tagless_content);
	$trimmed_content = trim($empty_lineless_content, " ");
	if (0) deb("survey.sendEmail(): tagless content: {$tagless_content}", "<p>END</p>");
	if (0) deb("survey.sendEmail(): empty_lineless_content: {$empty_lineless_content}", "<p>END</p>");
	if (0) deb("survey.sendEmail(): trimmed_content: {$trimmed_content}", "<p>END</p>");
	$email_body = "Dear {$person_name} ~\n\n" .
		"Thanks for completing the {$community} meals scheduling questionnaire!\n\n";
	if (!$content == "") {
		$email_body .= "Here are the preferences you have expressed as of "  . $timestamp . ":\n" . $empty_lineless_content;
	} else {
		$email_body .= "You have told us that you won't be doing any meal jobs this season.\n";	
	}
	$deadline = date('g:ia l, F j', DEADLINE);
	$url = getSurveyURL();
	$email_body .= "\nYou may revise your responses to this questionnaire at any time until $deadline, when the survey closes, by going to {$url}.\n\n" .
	"~ The Sunward More Meals Committee (Suzanne, Ken, Mark & Ed)";
	if (!SKIP_EMAIL || $person_email == 'ken@sunward.org') {
		$sent = mail($person_email,
			'Meal Scheduling Survey preferences saved at ' . $timestamp,
			$email_body,
			'From: moremeals@sunward.org');

		if (!$sent) {
			error_log("Unable to send email to: $to");
		}

		// if user is under pref level, then send warning email
		if (!is_null($insufficient_prefs_msg)) {
			$sent = mail('willie@gocoho.org',
				'Meal Scheduling Survey preferences saved under limit at ' . $timestamp,
				$person_name . "\n" . strip_tags($content . "\n" .
					$this->insufficient_prefs_msg),
				'From: moremeals@sunward.org');
		}
	}
	return $sent;
}

?>