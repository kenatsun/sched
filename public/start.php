<?php
session_start();

require_once 'constants.inc';
require_once 'git_ignored.php';
require_once 'init.php';
require_once 'utils.php';
require_once 'download.php';
require_once 'display/includes/header.php';  // Has to go before constants, globals, & config or its <head> stuff ends up in <body>
require_once 'globals.php';
require_once 'config.php'; 
require_once 'breadcrumbs.php';
require_once 'headline.php'; 
?>