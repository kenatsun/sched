<?php

if (0) deb("headline.php: _SERVER['SCRIPT_URL'] = " . $_SERVER['SCRIPT_URL'] . ""); 
if (0) deb("headline.php: _SERVER['QUERY_STRING'] = " . $_SERVER['QUERY_STRING'] . ""); 
if (0) deb("headline.php: _SERVER =", $_SERVER); 
if (0) deb("headline.php: _SESSION =", $_SESSION); 
if (0) deb("headline.php: _REQUEST =", $_REQUEST); 
if (0) deb("headline.php: _GET =", $_GET); 
if (0) deb("headline.php: _POST =", $_POST); 

// The url of the current page
if ($_SERVER['SCRIPT_URL'] == "/") 
	define('SCRIPT_URL', $_SERVER['PHP_SELF']);
	// define('SCRIPT_URL', "/" . HOME_URL);
else 
	define('SCRIPT_URL', $_SERVER['SCRIPT_URL']);
if (0) deb("headline.php: SCRIPT_URL = " . SCRIPT_URL); 
if (0) deb("headline.php: _SERVER['PHP_SELF'] = " . $_SERVER['PHP_SELF']); 

// The query string received by the current page
define('QUERY_STRING', $_SERVER['QUERY_STRING']);
if (0) deb("headline.php: QUERY_STRING = " . QUERY_STRING); 

// The current breadcrumbs (caller stack of the current page)
$breadcrumbs = $_REQUEST['backto'];
if (0) deb("headline.php: breadcrumbs before removing own link = " . $breadcrumbs); 
$breadcrumbs = str_replace(BACKTO_SEPARATOR . SCRIPT_URL, "", $_REQUEST['backto']);  // Don't add "my" url to "my" breadcrumbs
if (0) deb("headline.php: breadcrumbs after removing own link = ". $breadcrumbs); 
define('BREADCRUMBS', $breadcrumbs);
// define('BREADCRUMBS', $_REQUEST['backto']);
if (0) deb("headline.php: BREADCRUMBS = " . BREADCRUMBS); 

// The breadcrumbs to be shown on any page that the current page is calling 
if (!strpos(BREADCRUMBS, SCRIPT_URL)) {		// Add SCRIPT_URL to BREADCRUMBS to make NEXT_BREADCRUMBS
	if (BREADCRUMBS && SCRIPT_URL) $separator = BACKTO_SEPARATOR;
	define('NEXT_BREADCRUMBS', BREADCRUMBS . $separator . SCRIPT_URL);
} else {																	// Unless it's already there
	define('NEXT_BREADCRUMBS', BREADCRUMBS);
}
if (0) deb("headline.php: NEXT_BREADCRUMBS = " . NEXT_BREADCRUMBS); 
// $next_breadcrumbs = BREADCRUMBS . $separator . $my_url;
// $next_breadcrumbs = str_replace(SCRIPT_URL, "", $next_breadcrumbs);  // Get rid of duplicate breadcrumbs
// if (0) deb("headline.php: next_breadcrumbs =", $next_breadcrumbs);

// The breadcrumbs to be shown on any page to which the current page is returning 
define('PREVIOUS_BREADCRUMBS', removeBreadcrumbs(BREADCRUMBS));
if (0) deb("headline.php: PREVIOUS_BREADCRUMBS = " . PREVIOUS_BREADCRUMBS); 
// $PREVIOUS_BREADCRUMBS = removeBreadcrumbs(BREADCRUMBS);
// if (0) deb("headline.php: PREVIOUS_BREADCRUMBS =", $PREVIOUS_BREADCRUMBS);

// // An array containing the url and the label of the BREADCRUMBS
// define('LABELED_BREADCRUMBS', labelBreadcrumbs(BREADCRUMBS));
// if (0) deb("headline.php: LABELED_BREADCRUMBS =", LABELED_BREADCRUMBS); 
// // $labeled_breadcrumbs = labelBreadcrumbs(BREADCRUMBS);
// // if (0) deb("headline.php: labeled_breadcrumbs =", $labeled_breadcrumbs);


//////////////////////////////////////////////// FUNCTIONS 

/*
Print a headline for a page
*/
function renderHeadline($text, $breadcrumbs_str="", $subhead="", $show_admin_link=1) {
	// global $labeled_breadcrumbs;	
	if (0) deb ("headline.renderHeadline(): text =", $text);
	if (0) deb ("headline.renderHeadline(): breadcrumbs_str =", $breadcrumbs_str);
	if (0) deb ("headline.renderHeadline(): labeled_breadcrumbs =", $labeled_breadcrumbs);
	$td_style = 'background-color:white;';
	$labeled_breadcrumbs = labelBreadcrumbs($breadcrumbs_str);
	if ($labeled_breadcrumbs) {
		foreach($labeled_breadcrumbs as $i=>$labeled_breadcrumb) {
			$breadcrumbs_list .= '&nbsp;&nbsp;<a  href="'. $labeled_breadcrumb['url'] . '">' . $labeled_breadcrumb['label'] . '</a>'; 
		}
		$breadcrumbs_display = '
			<tr style="font-size:10pt; font-style:italic">
				<td colspan="2" style="text-align:right; ' . $td_style . '"><<<<< &nbsp;&nbsp;go back to:' . $breadcrumbs_list . '</td>
			</tr>';
		if (0) deb ("headline.renderHeadline(): breadcrumbs_display =", $breadcrumbs_display);
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
	if (userIsAdmin()) $admin_notice =
		'
		<div style=' . $color . '><p>
		<form method="post" action="' . $_SERVER['PHP_SELF'] . '">
			<input type="hidden" name="sign_in_as_guest" value="1">
			<p><strong>You are signed into this session as an admin. </strong>
			<input type="submit" value="Sign in as a plain old ordinary user.">
		</form>
		</p><br></div>';
	if (0) deb ("headline.renderHeadline(): next_breadcrumbs =", $next_breadcrumbs);
	if (userIsAdmin() && $show_admin_link) $admin_link =  
		'<div style=' . $color . '>
			<a style=' . $color . ' href="/admin.php?backto=' . $next_breadcrumbs . '"><strong>Open the Admin Dashboard</strong></a>
		</div>
		<br>';

	if (0) deb ("headline.renderHeadline(): userIsAdmin() =", userIsAdmin()); 
	if (0) deb ("headline.renderHeadline(): admin_notice =", $admin_notice);
		
	return <<<EOHTML
	{$instance_notice}
	{$admin_notice}
	{$admin_link}
	<table>
		{$breadcrumbs_display}
		<tr>
			<td style="$td_style"><img src={$community_logo}></td>
			{$headline}	
		</tr>
	</table>
EOHTML;
}


// BREADCRUMBS FUNCTIONS - start ----------------------------------------------


function addBreadcrumb($crumbs="", $crumb_to_add="") {
	if ($crumbs && $crumb_to_add) $crumbs .= BACKTO_SEPARATOR;
	$crumbs .= $crumb_to_add;
	if (0) deb("headline.addBreadcrumb(): crumbs after add = {$crumbs}");
	return $crumbs; 
}

function removeBreadcrumbs($crumbs="", $n=1) {
	// Remove the rightmost $n breadcrumbs from a breadcrumbs string
	if (0) deb("headline.removeBreadcrumbs(): crumbs before removing {$n} = {$crumbs}");
	for ($i=1; $i<=$n; $i++) {
		if (strrpos($crumbs, ",")) {
			$crumbs = substr($crumbs, 0, $i);
		} else {
			$crumbs = "";
		}
	}
	if (0) deb("headline.removeBreadcrumbs(): crumbs after removing {$n} = {$crumbs}");
	return $crumbs;
}

// function getBreadcrumbs() {
	// // if (0) deb("headline.getBreadcrumbs(): _POST = ", $_POST);
	// // if (0) deb("headline.getBreadcrumbs(): _GET = ", $_GET);
	// // $crumbs = "";
	// // if (array_key_exists('backto', $_GET)) $crumbs = $_GET['backto'];
	// // elseif (array_key_exists('backto', $_POST))	$crumbs = $_POST['backto'];
	// $crumbs = $_REQUEST['backto'];
	// if (0) deb("headline.getBreadcrumbs(): crumbs = {$crumbs}");
	// return $crumbs;
// }

// ---------- constants for breadcrumbs ---------

define('HOME_LINK', 'Home,index.php'); 
define('SIGNUPS_LINK', 'Manage&nbsp;Seasons,seasons.php'); 
define('SURVEY_LINK', 'Manage&nbsp;Survey,survey_steps.php');  
define('ASSIGNMENTS_LINK', 'Refine&nbsp;the&nbsp;Schedule,dashboard.php'); 
define('CHANGE_SETS_LINK', 'Change&nbsp;Sets,change_sets.php');
define('ADMIN_LINK', 'Admin&nbsp;Dashboard,admin.php');
define('CREATE_SCHEDULE_LINK', 'Create&nbsp;the&nbsp;Schedule,schedule_steps.php');


function labelBreadcrumbs($breadcrumbs_str) { 
	// Returns the breadcrumbs in $breadcrumbs_str,
	// if it appears in the $breadcrumb_labels array",
	// in an array containing the 'url' and the 'label' to be displayed in the breadcrumbs list.
	
	$breadcrumb_labels = array(
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
	$labeled_breadcrumbs = array();
	
	if (0) deb ("headline.labelBreadcrumbs(): breadcrumbs_str =", $breadcrumbs_str);
	if ($breadcrumbs_str) {
		$breadcrumbs_arr = explode(BACKTO_SEPARATOR, $breadcrumbs_str);
		if (0) deb ("headline.labelBreadcrumbs(): breadcrumbs_arr =", $breadcrumbs_arr);
		foreach($breadcrumbs_arr as $i=>$breadcrumb) {
			if ($breadcrumb_labels[$breadcrumb]) {
				$labeled_breadcrumb = array (
					'url' => $breadcrumb . "?backto=" . $previous_urls,
					'label' => $breadcrumb_labels[$breadcrumb]
				);
			$labeled_breadcrumbs[] = $labeled_breadcrumb;
			if (0) deb ("headline.labelBreadcrumbs(): labeled_breadcrumb =", $labeled_breadcrumb);
			$previous_urls = addBreadcrumb($previous_urls, $breadcrumb);
			}
		}
	}
	if (0) deb ("headline.labelBreadcrumbs(): labeled_breadcrumbs =", $labeled_breadcrumbs);
	return $labeled_breadcrumbs; 
}

// BREADCRUMBS FUNCTIONS - end ----------------------------------------------




?>