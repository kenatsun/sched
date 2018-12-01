<?php
require_once 'utils.php';
require_once 'display/includes/header.php';
require_once 'globals.php';
require_once 'auto_assignments/assignments.php';

if (0) deb("run_scheduler_from_web.php: _POST = ", $_POST);

$page .= renderHeadline('Run the Scheduler', HOME_LINK);
$page .= renderParametersForm();
if ($_POST) $page .= renderSchedule($_POST); 
print $page;

function renderParametersForm() {
	$form = '';
	$form .= '<form action="run_scheduler_from_web.php" method="post">'; 
 	$form .= '<br>';
	$form .= '<input type="radio" name="option" value = "h" checked>Trial run - just show results below<br>';
	$form .= '<input type="radio" name="option" value = "d">Write assignments to database<br>';
	$form .= '<input type="radio" name="option" value = "D">Delete all previous assignments for this season, then write assignments to database<br>';
	$form .= '<br>';	
	$form .= '<input type="hidden" name="Hi" value="x"/>'; 
	$form .= '<input type="submit" value="Run It!">'; 
	$form .= '</form>'; 
	$form .= '<br><br>';	
	return $form;
}

$options = array("h"=>"");
function renderSchedule($post) {
	$options = array("h"=>"");
	if ($post['option'] != "h") $options[$post['option']] = "";
	if (0) deb("run_scheduler_from_web.renderSchedule(): options = ", $options);
	return runScheduler($options);
}
?>