<?php

require_once 'start.php';
require_once 'classes/calendar.php';
require_once 'classes/PeopleList.php'; 
require_once 'classes/OffersList.php';
require_once 'classes/person.php';
require_once 'classes/survey1.php';
require_once 'participation.php';
require_once 'seasons_utils.php';


//////////////////////////////////////////////////////////////// DISPLAY FUNCTIONS

function renderProcessLink($process, $n="") {
	$link = '<p class="summary_report">' . $process['type'] . ' ' . $n . ': <a href="'. $process['href'] . '?parent_process_id=' . $process['process_id'] . '&backto="' . NEXT_BREADCRUMBS . '>' . $process['name'] . '</a></p>';
	if (0) deb("index.renderProcessLink(): link = ", $link);
	return $link;
}


function render_new_season_link($text) {
	$stage = sqlSelect("*", ADMIN_PROCESSES_TABLE, "process_id = " . SET_UP_SEASON_ID . " and season_id = " . SEASON_ID, "", (0), "index.render_new_season_link()")[0];
	// return '<p class="summary_report"><a href="'. $stage['href'] . '?season_id=&parent_process_id=' . SET_UP_SEASON_ID . '">' . $text . '</a></p>';
	return '<p class="summary_report"><a href="/season.php?season_id=&parent_process_id=' . SET_UP_SEASON_ID . '">' . $text . '</a></p>';
}


function render_seasons_link($text) {
	$stage = sqlSelect("*", ADMIN_PROCESSES_TABLE, "process_id = " . SET_UP_SEASON_ID . " and season_id = " . SEASON_ID, "", (0), "index.render_new_season_link()")[0];
	return '<p class="summary_report"><a href="/seasons.php">' . $text . '</a></p>'; 
}

function render_update_admin_tasks_link($text) {
	$stage = sqlSelect("*", ADMIN_PROCESSES_TABLE, "process_id = " . SET_UP_SEASON_ID . " and season_id = " . SEASON_ID, "", (0), "index.render_new_season_link()")[0];
	return '<p class="summary_report"><a href="/admin.php?update_admin_tasks">' . $text . '</a></p>';
}

function generateAdminProcessesForSeason($season_id) {
	
	$process_types = sqlSelect("*", ADMIN_PROCESS_TYPES_TABLE, "", "", (0), "season.generateAdminProcessesForSeason(): process types");
	foreach ($process_types as $i=>$process_type) {
		if (!sqlSelect("*", ADMIN_PROCESSES_TABLE, "season_id = " . $season_id . " and process_id = " . $process_type['id'], "")[0]) {
			$columns = "season_id, process_id";
			$values = "{$season_id}, {$process_type['id']}";
			sqlInsert(ADMIN_PROCESS_STATUSES_TABLE, $columns, $values, (0), "season.generateAdminProcessesForSeason(): insert new process status");
		}
	}
}
 


?>