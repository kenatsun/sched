<?php
require_once 'classes/survey.php';

$survey = new Survey();
$survey->run();
print $survey->renderSurvey(); 

?>
