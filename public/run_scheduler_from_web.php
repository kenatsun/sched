<?php
require_once 'utils.php';
require_once 'display/includes/header.php';
require_once 'globals.php';
require_once 'auto_assignments/assignments.php';

if (0) deb("run_scheduler_from_web.php: _POST = ", $_POST);
if (0) deb("run_scheduler_from_web.php: _GET = ", $_GET);

if (array_key_exists('previewonly', $_GET) || array_key_exists('previewonly', $_POST)) $preview_only = "?previewonly=";

$page .= renderHeadline('Run the Scheduler', HOME_LINK . ADMIN_LINK, "", 0);
$page .= renderParametersForm();
if ($_POST) $page .= renderSchedule($_POST); 
print $page;

function renderParametersForm() {
	$form = '';
	$form .= '<form action="run_scheduler_from_web.php' . $preview_only . '" method="post">'; 
 	$form .= '<br>';
	$form .= '<input type="radio" name="option" value = "h" checked>Trial run - just show the results below<br>';
	if (!$preview_only) {
		// $form .= '<input type="radio" name="option" value = "d">Write assignments to database<br>';
		$form .= '<input type="radio" name="option" value = "D">Write assignments to database (replacing any existing assignments for this season)<br>';
	}
	$form .= '<br>';	
	// $form .= '<input type="hidden" name="Hi" value="x"/>'; 
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