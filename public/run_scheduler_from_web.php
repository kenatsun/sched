<?php
require_once 'start.php';

// session_start();

// require_once 'utils.php';
// require_once 'globals.php';
// require_once 'display/includes/header.php';
require_once 'auto_assignments/assignments.php';

if (0) deb("run_scheduler_from_web.php: _POST = ", $_POST);
if (0) deb("run_scheduler_from_web.php: _GET = ", $_GET);

// $back_to_link = backTo($_GET, $_POST);

// if (0) deb("run_scheduler_from_web.php: back_to = {$back_to}");

$page .= renderHeadline('Run the Scheduler', BREADCRUMBS, "", 0);
// $page .= renderHeadline('Run the Scheduler', HOME_LINK . ADMIN_LINK . $back_to_link , "", 0);
$page .= renderParametersForm($back_to);
if ($_POST) $page .= renderSchedule($_POST); 
print $page;

function renderParametersForm($back_to) { 
	if (0) deb("run_scheduler_from_web.renderParametersForm(): back_to = {$back_to}");
	$form = '';
	$form .= '<form action="run_scheduler_from_web.php?backto="' . BREADCRUMBS . ' method="post">'; 
 	$form .= '<br>';
	$form .= '<input type="hidden" name="backto" value = "' . BREADCRUMBS . '">';
	$form .= '<input type="radio" name="option" value = "h" checked>Trial run - just show the results below<br>';
	if ($back_to_link == CREATE_SCHEDULE_LINK) {
		// $form .= '<input type="radio" name="option" value = "d">Write assignments to database<br>';
		$form .= '<input type="radio" name="option" value = "D">Write assignments to database (replacing any existing assignments for this season)<br>';
	}
	$form .= '<br>';	
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