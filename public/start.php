<?php

session_start();

require_once 'constants.inc';
require_once 'git_ignored.php';
require_once 'globals.php';
require_once 'utils.php';
require_once 'config.php'; 
require_once 'headline.php';
require_once 'display/includes/header.php';

determineUserStatus();

?>