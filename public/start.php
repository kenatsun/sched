<?php
session_start();

require_once 'constants.inc';
require_once 'git_ignored.php';
require_once 'init.php';
require_once 'utils.php';
require_once 'display/includes/header.php';  // Has to go before constants, globals, & config or its <head> stuff ends up in <body>
require_once 'globals.php';
if (0) deb("start.php.before req config.php");
require_once 'config.php'; 
if (0) deb("start.php.before req breadcrumbs.php");
require_once 'breadcrumbs.php';
if (0) deb("start.php.before req headline.php");
require_once 'headline.php'; 
require_once 'download.php';

// since("start"); // Start the performance timer
if (1) deb("start.php: end");

?>