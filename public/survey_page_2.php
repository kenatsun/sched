<?php

require_once 'start.php';
require_once 'classes/person.php';
require_once 'classes/survey.php';

$season_name = get_season_name_from_db($season_id);

global $dbh;

resetPostedVariable(); // Not sure this is needed 
if (0) deb("process_survey1: POST =", $_POST);
if (0) deb("process_survey1: GET =", $_GET);
$person_id = $_REQUEST['person'];
$respondent = getSurveyRespondent($person_id);
if (0) deb("process_survey1: respondent =", $respondent);
$shifts_offered_count = saveOffers($respondent, $offers);
if (0) deb("process_survey1: shifts_offered_count =", $shifts_offered_count);
displayNextPage($respondent, $shifts_offered_count);

////////////////////////////////////////// FUNCTIONS

function getSurveyRespondent($person_id) {
	if (0) deb("survey_page_2.getSurveyRespondent(): POST =", $_POST);
	if (0) deb("survey_page_2.getSurveyRespondent(): POST[id] =", $_POST['person']);
	if (0) deb("survey_page_2.getSurveyRespondent(): POST[username] =", $_POST['username']);
	// $person_id = $_REQUEST['person'];
	// if ($_GET) {
		// $person_id = $_GET['person'];
	// }
	// elseif ($_POST) {
		// $person_id = $_POST['person'];
	// }
	if (0) deb("survey_page_2.getSurveyRespondent(): person_id = " . $person_id);
	$respondent = new Person($person_id);
	if (0) deb("survey_page_2.getSurveyRespondent(): respondent =", $respondent);
	return $respondent;
}

function resetPostedVariable() {
	// is this a posting? save that and delete from POST.
	if (isset($_POST['posted'])) {
		unset($_POST['posted']);
	}
}

function getOffers() {
	foreach($_POST as $job_id=>$offer) {
		if ($job_id == 'username' || $job_id == 'person' || $job_id == 'posted') continue;
		$offer = normalizeOffer($offer);
		if (0) deb("survey_page_2.getOffers(): job_id = offer: ", $job_id." = ".$offer);
		if (0) deb("survey_page_2.getOffers(): job_name = offer: ", get_job_name($job_id)." = ".$offer);
		$offers[$job_id] = $offer;
	}
	if (0) deb("survey_page_2.getOffers(): offers:", $offers);
	return offers;
}

/**
 * Save the stated offers.
 */
function saveOffers($respondent, $offers) {
	$season_id = SEASON_ID;
	$offers_table = OFFERS_TABLE;
	$shifts_offered_count = 0;  // total number of shifts this person has offered to do (across all jobs)

	global $dbh;
	foreach($_POST as $job_id=>$offer) {
		if (!is_numeric($job_id)) continue;
		$job_name = get_job_name($job_id);
		$offer = normalizeOffer($offer);
		if (0) deb("survey_page_2.saveOffers(): job_name=offer", $job_name." = ".$offer);
		$sql = "
			REPLACE INTO {$offers_table} (worker_id, job_id, season_id, instances, type) 
				VALUES({$respondent->id}, {$job_id}, {$season_id}, {$offer}, 'a')";
		if (0) deb("survey_page_2.saveOffers(): SQL:", $sql);
		$success = $dbh->exec($sql);
		if (0) deb("survey_page_2.saveOffers(): success?", $success);
		if ($success) {
			$shifts_offered_count += $offer;
		}
	}
	if (0) deb("survey_page_2.saveOffers(): Shift count:", $shifts_offered_count);
	return $shifts_offered_count;
}

function normalizeOffer($offer) {
	// All offers except positive integers are treated as 0, meaning "I don't want to do this job"
	if ($offer == NULL || $offer < 0 || !is_numeric($offer)) $offer = 0;
	// Round real number offers to integers
	if (is_float($offer)) $offer = round($is_offer);
	return $offer;
}

function displayNextPage($respondent, $shifts_offered_count) {
	if (0) deb("survey_page_2.displayNextPage(): _POST =", $_POST);
	if (0) deb("survey_page_2.displayNextPage(): _GET =", $_GET);
	if ($shifts_offered_count > 0 || !$_POST) {
		displayPreferencesSurvey($respondent);
	} else { 
		displayNoShiftsOfferedPage($respondent);
	}
}

function displayPreferencesSurvey($respondent) {
	$_GET['person'] = $respondent->username;  // survey.php is looking for username in the 'person' hidden input field
	if (0) deb("survey_page_2.displayPreferencesSurvey(): GET =", $_GET);
	if (0) deb("survey_page_2.displayPreferencesSurvey(): respondent", $respondent);
	$survey = new Survey();
	$survey->setWorker($respondent->username, $respondent->id, $respondent->first_name, $respondent->last_name);
	print $survey->renderSurvey();
}

function displayNoShiftsOfferedPage($respondent) {
	finishSurvey(NULL, $respondent->id);
}

?>