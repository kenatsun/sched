<?php

session_start();

require_once 'utils.php';
require_once 'git_ignored.php';
require_once 'constants.inc';
require_once 'globals.php';
require_once 'config.php';
require_once 'headline.php';
require_once 'display/includes/header.php';

determineUserStatus();

// if (0) deb("start.php: _SERVER['SCRIPT_URL'] = " . $_SERVER['SCRIPT_URL'] . ""); 
// if (0) deb("start.php: _SERVER['QUERY_STRING'] = " . $_SERVER['QUERY_STRING'] . ""); 
// if (0) deb("start.php: _SERVER =", $_SERVER); 
// if (0) deb("start.php: _SESSION =", $_SESSION); 
// if (0) deb("start.php: _REQUEST =", $_REQUEST); 
// if (0) deb("start.php: _GET =", $_GET); 
// if (0) deb("start.php: _POST =", $_POST); 

?>