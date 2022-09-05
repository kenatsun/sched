<?php
require_once 'start.php';
require_once 'auto_assignments/assignments.php';

if (0) deb("run_scheduler_from_web.php: _POST = ", $_POST);
if (0) deb("run_scheduler_from_web.php: _GET = ", $_GET);
if (0) deb("run_scheduler_from_web.php: array_key_exists(previewonly, _GET?: )" . array_key_exists("previewonly", $_GET));
if (array_key_exists("previewonly", $_GET)) $preview_only = "&previewonly=";
// if (0) deb("run_scheduler_from_web.php: !array_key_exists(previewonly=, _GET?: )" . !array_key_exists("previewonly=", $_GET));
$page .= renderHeadline('Run the Scheduler', "", 1);
$page .= renderParametersForm($preview_only);
if ($_POST) $page .= renderSchedule($_POST); 
print $page;

function renderParametersForm($preview_only) { 
	$form .= '<form action="' . makeURI('run_scheduler_from_web.php', CRUMBS_IDS, 'caller_url=' . $_REQUEST['caller_url'] . $preview_only) . '" method="post">'; 
 	$form .= '<br>';
	$form .= '<input type="radio" name="option" value = "h" checked>Trial run - just show the results below<br>';
	if (!$preview_only) {
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