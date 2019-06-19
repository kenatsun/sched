<?php
require_once "start.php";
require_once 'classes/survey1.php';

if (0) deb("survey_page_1.php: start"); 
// $worker_id = $_GET['person'];
if (0) deb("survey_page_1.php: _GET['person'] = ", $_GET['person']); 

$page = build_survey($_GET['person']);
print $page;

/////////////////////////////////// FUNCTIONS

function build_survey($worker_id) {
	if (0) deb("index.build_survey: respondent_id = ", $worker_id);
	$survey = new Survey1($worker_id);
	if (0) deb("index.build_survey: survey = ", $survey);
	return $survey->renderOffersList(); 
}

?>