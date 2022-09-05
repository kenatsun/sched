<?php

/*
Render a headline for a page
*/
function renderHeadline($text, $subhead="", $show_admin_dashboard_link=1) {
	if (0) deb ("headline.renderHeadline(): text =", $text);
	if (0) deb ("headline.renderHeadline(): crumbs_str = '" . $crumbs_str . "'");
	if (0) deb ("headline.renderHeadline(): labeled_crumbs =", $labeled_crumbs);

	$community_logo = (COMMUNITY == "Sunward" ? '/display/images/sunward_logo.png' : '/display/images/great_oak_logo.png');
	$new_status .= userIsAdmin() ? "guest" : "admin";
	if (0) deb ("headline.renderHeadline(): REQUEST_QUERY_STRING = " . REQUEST_QUERY_STRING);
	$community_logo_form = '
		<form name="community_logo_form" id="community_logo_form" action="' . makeURI(SCRIPT_URL, "", REQUEST_QUERY_STRING) . '" method="post">
			<input type="image" name="submit" src="' . $community_logo . '" alt="Sunward icon" >
			<input type="hidden" id="sign_in_as" name="sign_in_as" value="' . $new_status . '"  >
		</form>';
	
	$td_style = 'background-color:white;'; 

	$color = 'color:red;';
	
	// Render instance notice (for development instance only)
	$instance = INSTANCE;
	$database = DATABASE;
	$instance_notice = $instance && !$_GET['printable'] == 1 ? '<p style="' . $color . '"><strong>You\'re looking at the '. $instance . ' instance.&nbsp;&nbsp;Database is ' . $database . '.</strong></p>' : '';

	// Render admin notice (shown iff user is admin)
	if (userIsAdmin() && !$_GET['printable'] == 1) {
		$admin_notice = '<form name="admin_notice_form" method="post" action="' . makeURI(SCRIPT_URL, "", REQUEST_QUERY_STRING) . '">';
		$admin_notice .= ' 
			<div style="' . $color . '">
				<p>
					<strong>
						You\'re signed into this session as admin ' . currentAdmin()['name'] . '.&nbsp;&nbsp;<input type="submit" value="sign out">
					</strong>
				</p>
			</div>
			<input type="hidden" id="sign_out_admin" name="sign_out_admin">';
		$admin_notice .= '</form>'; 
	}
	if (0) deb ("headline.renderHeadline(): NEXT_CRUMBS_IDS = " . NEXT_CRUMBS_IDS);
	if (userIsAdmin() && $show_admin_dashboard_link) $admin_dashboard_link =  
		'<br>
		<div style="' . $color . '">
			<a style="' . $color . '" href="'. makeURI("/admin.php", NEXT_CRUMBS_IDS) . '"><strong>Open the Admin Dashboard</strong></a>
		</div>
		';


	// Render breadcrumbs display
	// $caller_url = $_REQUEST['caller_url'];
	$breadcrumbs = renderBreadcrumbs($_SERVER['PHP_SELF'], $_REQUEST['caller_url']);
	// if (CRUMBS_QUERY) $breadcrumbs = CRUMBS_QUERY;  // Disabled because breadcrumbs.php is disabled
	if ($breadcrumbs) {
		$crumbs_display = '
		<tr style="font-size:10pt; font-style:italic;">
			<td colspan="2" style="text-align:right; ' . $td_style . '">&lt;&lt;&lt;&lt;&lt; &nbsp;&nbsp;go back to:' . $breadcrumbs . '</td>
		</tr>';
	}
	
	if (1) deb ("headline.renderHeadline(): crumbs_display =", $crumbs_display);

	// Render headline
	if ($subhead) {
		$headline = '<td style="' . $td_style. '" class="headline">' . $text; 
		$headline .= '<br><span style="font-size:18px">' . $subhead . '</span>';
	} 
	else {
		$headline = '<td style="' . $td_style. '" class="headline">' . $text . '</td>';
	}

	if (0) deb ("headline.renderHeadline(): userIsAdmin() =", userIsAdmin()); 
	if (0) deb ("headline.renderHeadline(): admin_notice =", $admin_notice);
		
	$headline = 
		$instance_notice .
		$admin_notice .
		$admin_dashboard_link .
		'<br>
		<table>' .
			$crumbs_display .
			'<tr>
				<td style="' . $td_style . '">' . $community_logo_form . '</td>' . $headline .	 
			'</tr>
		</table>'
	;
	
	return $headline;
}


?>