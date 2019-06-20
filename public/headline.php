<?php

if (0) deb("headline.php: _SERVER['SCRIPT_URI'] = " . $_SERVER['SCRIPT_URI'] . "");
if (0) deb("headline.php: _SERVER['SCRIPT_URL'] = " . $_SERVER['SCRIPT_URL'] . ""); 
if (0) deb("headline.php: _SERVER['QUERY_STRING'] = " . $_SERVER['QUERY_STRING'] . ""); 
if (0) deb("headline.php: _SERVER['HTTP_FORWARDED_REQUEST_URI'] = " . $_SERVER['HTTP_FORWARDED_REQUEST_URI'] . ""); 
if (0) deb("headline.php: _SERVER =", $_SERVER); 
if (0) deb("headline.php: _SESSION =", $_SESSION); 
if (0) deb("headline.php: _REQUEST =", $_REQUEST); 
if (0) deb("headline.php: _REQUEST['backto'] = " . $_REQUEST['backto']); 
if (0) deb("headline.php: _GET =", $_GET); 
if (0) deb("headline.php: _POST =", $_POST);  

define('CRUMB_SEPARATOR', ';');
if (0) deb("headline.php: CRUMB_SEPARATOR = " . CRUMB_SEPARATOR); 
define('CRUMB_SEPARATOR_2', ',');
if (0) deb("headline.php: CRUMB_SEPARATOR_2 = " . CRUMB_SEPARATOR_2);


define('CRUMMY', false); // Iff CRUMMY is true, crumbs will be shown.  This is to get on with other things while crumbs don't work
if (CRUMMY) {
	// The url of the current page (including query string if any)
	if ($_SERVER['SCRIPT_URL'] == "/") 
		define('SCRIPT_URL', $_SERVER['PHP_SELF']);
	else 
		define('SCRIPT_URL', $_SERVER['SCRIPT_URL']);
	if (0) deb("headline.php: SCRIPT_URL = " . SCRIPT_URL); 


	// The URL that accessed this page
	if ($_SERVER['HTTP_FORWARDED_REQUEST_URI'] == "/") 
		$my_crumb = $_SERVER['PHP_SELF'];
	else 
		$my_crumb = $_SERVER['HTTP_FORWARDED_REQUEST_URI'];
	$my_crumb = str_replace(CRUMB_SEPARATOR, CRUMB_SEPARATOR_2, $my_crumb); 
	define('MY_CRUMB', $my_crumb);
	if (0) deb("headline.php: MY_CRUMB = " . MY_CRUMB); 

	// The crumbs to be displayed on this page (previous calling sequence)
	define('HTTP_FORWARDED_REQUEST_URI', $_SERVER['HTTP_FORWARDED_REQUEST_URI']);
	define('QUERY_STRING', $_SERVER['QUERY_STRING']);
	$extra_queries = str_replace("backto=" . $crumbs . "&", "", QUERY_STRING);
	if (0) deb("headline.php: extra_queries = " . $extra_queries);  
	if (0) deb("headline.php: HTTP_FORWARDED_REQUEST_URI = " . HTTP_FORWARDED_REQUEST_URI);
	// $crumbs = $_REQUEST['backto'];
	$crumbs = substr(HTTP_FORWARDED_REQUEST_URI, strpos(HTTP_FORWARDED_REQUEST_URI, "=")+1); 
	// $crumbs = substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], "?backto=")+1); 
	// $crumbs = substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], "backto=")+7); 
	// $crumbs = $_SERVER['QUERY_STRING']; 
	define('CRUMBS', $crumbs);
	if (0) deb("headline.php: CRUMBS = " . CRUMBS); 
	// $crumbs_arr = explode(CRUMB_SEPARATOR, CRUMBS);
	// define ('CRUMBS_ARR', $crumbs_arr);
	define ('CRUMBS_ARR', explode(CRUMB_SEPARATOR, CRUMBS));
	if (0) deb("headline.php: CRUMBS_ARR = ", CRUMBS_ARR); 

	// The crumbs to be shown on any page that the current page is calling 
	if (CRUMBS && MY_CRUMB) $separator = CRUMB_SEPARATOR;
	define('NEXT_CRUMBS', CRUMBS . $separator . MY_CRUMB);
	if (0) deb("headline.php: NEXT_CRUMBS = " . NEXT_CRUMBS);
	// $next_crumbs_arr = explode(CRUMB_SEPARATOR, NEXT_CRUMBS);
	// if (0) deb("headline.php: next_crumbs_arr = ", $next_crumbs_arr); 
	define('NEXT_CRUMBS_ARR', explode(CRUMB_SEPARATOR, NEXT_CRUMBS));
	if (0) deb("headline.php: NEXT_CRUMBS_ARR = ", NEXT_CRUMBS_ARR); 

	// The crumbs to be shown on any page to which the current page is returning 
	define('PREVIOUS_CRUMBS', removeCrumbs(CRUMBS));
	if (0) deb("headline.php: PREVIOUS_CRUMBS = " . PREVIOUS_CRUMBS); 
} else {
	define('MY_CRUMB', "");
	define('CRUMBS', "");
	define('NEXT_CRUMBS', "");
	define('PREVIOUS_CRUMBS', "");
	define('CRUMBS_ARR', "");
} 


///////////////////////////////////////// old version:

// // The query string received by the current page
// define('QUERY_STRING', $_SERVER['QUERY_STRING']);
// if (0) deb("headline.php: QUERY_STRING = " . QUERY_STRING); 

// // The current crumbs (caller stack of the current page)
// $crumbs = $_REQUEST['backto'];
// if (0) deb("headline.php: crumbs before removing own link = " . $crumbs); 
// $crumbs = str_replace(CRUMB_SEPARATOR . SCRIPT_URL, "", $_REQUEST['backto']);  // Don't add "my" url to "my" crumbs
// if (0) deb("headline.php: crumbs after removing own link = ". $crumbs); 
// define('CRUMBS', $crumbs);
// // define('CRUMBS', $_REQUEST['backto']);
// if (0) deb("headline.php: CRUMBS = " . CRUMBS); 

// // The crumbs to be shown on any page that the current page is calling 
// // $extra_queries = str_replace("backto=" . CRUMBS . "&", "", QUERY_STRING);
// // if (0) deb("headline.php: extra_queries = " . $extra_queries); 

// // $my_crumb = ($extra_queries) ? SCRIPT_URL . "?" . $extra_queries : SCRIPT_URL;
// $my_crumb = SCRIPT_URL;
// if (!strpos(CRUMBS, $my_crumb)) {		// Add my crumb to CRUMBS to make NEXT_CRUMBS
	// if (CRUMBS && $my_crumb) $separator = CRUMB_SEPARATOR;
	// define('NEXT_CRUMBS', CRUMBS . $separator . $my_crumb);
// } else {																	// Unless it's already there
	// define('NEXT_CRUMBS', $my_crumb);
// }
// if (0) deb("headline.php: NEXT_CRUMBS = " . NEXT_CRUMBS); 

// // The crumbs to be shown on any page to which the current page is returning 
// define('PREVIOUS_CRUMBS', removeCrumbs(CRUMBS));
// if (0) deb("headline.php: PREVIOUS_CRUMBS = " . PREVIOUS_CRUMBS); 


//////////////////////////////////////////////// FUNCTIONS 

/*
Print a headline for a page
*/
function renderHeadline($text, $crumbs_str="", $subhead="", $show_admin_link=1) {
	if (0) deb ("headline.renderHeadline(): text =", $text);
	if (0) deb ("headline.renderHeadline(): crumbs_str =", $crumbs_str);
	if (0) deb ("headline.renderHeadline(): labeled_crumbs =", $labeled_crumbs);
	$td_style = 'background-color:white;';
	$labeled_crumbs = labelCrumbs($crumbs_str);
	if ($labeled_crumbs) {
		foreach($labeled_crumbs as $i=>$labeled_crumb) {
			$crumbs_list .= '&nbsp;&nbsp;<a  href="'. $labeled_crumb['url'] . '">' . $labeled_crumb['label'] . '</a>'; 
		}
		$crumbs_display = '
			<tr style="font-size:10pt; font-style:italic">
				<td colspan="2" style="text-align:right; ' . $td_style . '"><<<<< &nbsp;&nbsp;go back to:' . $crumbs_list . '</td>
			</tr>';
		if (0) deb ("headline.renderHeadline(): crumbs_display =", $crumbs_display);
	}

	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');

	if ($subhead) {
		$headline = '<td style="$td_style" class="headline">' . $text;
		$headline .= '<br><span style="font-size:18px">' . $subhead . '</span>';
	} 
	else {
		$headline = '<td style="$td_style" class="headline">' . $text . '</td>';
	}
	
	$instance = INSTANCE;
	$database = DATABASE;
	$color = '"color:red"';
	$instance_notice = ($instance ? "<p style={$color}><strong>You're looking at the {$instance} instance.  Database is {$database}.</strong></p>" : "");
	if ($instance_notice && !userIsAdmin()) $instance_notice .= "<br>";
	// $end_session_label = "End this session and start a new one.";
	// $sign_in_as_guest_label = "Sign in as a guest";
	if (userIsAdmin() && !$_GET['printable'] == 1) $admin_notice =
		'
		<div style=' . $color . '><p>
		<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
			<input type="hidden" name="sign_in_as_guest" value="1">
			<p><strong>You are signed into this session as an admin. </strong>
			<input type="submit" value="Sign in as a plain old ordinary user.">
		</form>
		</p><br></div>';
	if (0) deb ("headline.renderHeadline(): NEXT_CRUMBS = " . NEXT_CRUMBS);
	if (userIsAdmin() && $show_admin_link) $admin_link =  
		'<div style=' . $color . '>
			<a style=' . $color . ' href="'. makeURI("/admin.php", NEXT_CRUMBS) . '"><strong>Open the Admin Dashboard</strong></a>
		</div>
		<br>';

	if (0) deb ("headline.renderHeadline(): userIsAdmin() =", userIsAdmin()); 
	if (0) deb ("headline.renderHeadline(): admin_notice =", $admin_notice);
		
	return <<<EOHTML
	{$instance_notice}
	{$admin_notice}
	{$admin_link}
	<table>
		{$crumbs_display}
		<tr>
			<td style="$td_style"><img src={$community_logo}></td>
			{$headline}	
		</tr>
	</table>
EOHTML;
}


// CRUMBS FUNCTIONS - start ----------------------------------------------


function addCrumb($crumbs="", $crumb_to_add="") {
	if ($crumbs && $crumb_to_add) $crumbs .= CRUMB_SEPARATOR;
	$crumbs .= $crumb_to_add;
	if (0) deb("headline.addCrumb(): crumbs after add = {$crumbs}");
	return $crumbs; 
}

function removeCrumbs($crumbs="", $n=1) {
	// Remove the rightmost $n crumbs from a crumbs string
	if (0) deb("headline.removeCrumbs(): crumbs before removing {$n} = {$crumbs}");
	for ($i=1; $i<=$n; $i++) {
		if (strrpos($crumbs, ",")) {
			$crumbs = substr($crumbs, 0, $i);
		} else {
			$crumbs = "";
		}
	}
	if (0) deb("headline.removeCrumbs(): crumbs after removing {$n} = {$crumbs}");
	return $crumbs;
}


function labelCrumbs($crumbs_str) { 
	// Returns the crumbs in $crumbs_str,
	// if it appears in the $crumb_labels array",
	// in an array containing the 'url' and the 'label' to be displayed in the crumbs list.
	
	$crumb_labels = array(
		'/' => 'Home',
		'/index.php' => 'Home',
		'/seasons.php' => 'Manage Seasons',
		'/season.php' => 'Manage Season',
		'/survey_steps.php' => 'Manage Survey',
		'/dashboard.php' => 'Refine the Schedule', 
		'/change_sets.php' => 'Undo Changes',
		'/admin.php' => 'Admin Dashboard',
		'/schedule_steps.php' => 'Create Schedule',
		'/survey_page_1.php' => 'Signups',
		'/survey_page_2.php' => 'Preferences'
	);
	$labeled_crumbs = array();
	
	if (0) deb ("headline.labelCrumbs(): crumbs_str =", $crumbs_str);
	if ($crumbs_str) {
		// $crumbs_arr = explode(CRUMB_SEPARATOR, $crumbs_str);
		$crumbs_arr = CRUMBS_ARR;
		if (0) deb ("headline.labelCrumbs(): CRUMBS_ARR =", CRUMBS_ARR);
		if (0) deb ("headline.labelCrumbs(): crumbs_arr =", $crumbs_arr);
		if ($crumbs_arr) {
			foreach($crumbs_arr as $i=>$crumb) {
				$crumb_url = explode("?", $crumb, 2)[0];
				if (!$crumb_url) $crumb_url = $crumb;
				if (0) deb ("headline.labelCrumbs(): crumb_url =", $crumb_url);
				if ($crumb_labels[$crumb_url]) {
					$labeled_crumb = array (
						'url' => $crumb,
						'label' => $crumb_labels[$crumb_url]
					);
				}
				$labeled_crumbs[] = $labeled_crumb; 
			}
		}
	}
	if (0) deb ("headline.labelCrumbs(): labeled_crumbs =", $labeled_crumbs);
	return $labeled_crumbs; 
}

// ---------- constants for crumbs ---------

define('HOME_LINK', 'Home,index.php'); 
define('SIGNUPS_LINK', 'Manage&nbsp;Seasons,seasons.php'); 
define('SURVEY_LINK', 'Manage&nbsp;Survey,survey_steps.php');  
define('ASSIGNMENTS_LINK', 'Refine&nbsp;the&nbsp;Schedule,dashboard.php'); 
define('CHANGE_SETS_LINK', 'Change&nbsp;Sets,change_sets.php');
define('ADMIN_LINK', 'Admin&nbsp;Dashboard,admin.php');
define('CREATE_SCHEDULE_LINK', 'Create&nbsp;the&nbsp;Schedule,schedule_steps.php');

// CRUMBS FUNCTIONS - end ----------------------------------------------




?>