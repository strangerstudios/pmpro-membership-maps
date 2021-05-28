<?php
/**
 * Plugin Name: Paid Memberships Pro - Membership Maps Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/membership-maps/
 * Description: Display a map of members or for a single member's profile.
 * Version: 0.2
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-membership-maps
 * Domain Path: /languages
 */

function pmpromm_shortcode( $atts ){

	extract(shortcode_atts(array(
		'height' 		=> '400', //Uses px
		'width'			=> '100', //Uses %
		'zoom'			=> apply_filters( 'pmpromm_default_zoom_level', '8' ),
		'map_id'			=> '1',
		'infowindow_width' 	=> '300', //We'll always use px for this
		'levels'		=> false,
		//Using same fields as member directory
		'link' 			=> true,
		'avatar_size' 	=> '64',
		'show_avatar'	=> true,
		'show_email' 	=> true,
		'show_level' 	=> true,
		'show_startdate' => true,
		'avatar_align' 	=> NULL,
		'fields' 		=> NULL
	), $atts));

	$marker_attributes = apply_filters( 'pmpromm_marker_attributes', array(
		'link' 				=> $link,
		'avatar_size'		=> $avatar_size,
		'show_avatar'		=> $show_avatar,
		'show_email'		=> $show_email,
		'show_level'		=> $show_level,
		'show_startdate'	=> $show_startdate,
		'avatar_align'		=> $avatar_align,
		'fields'			=> $fields
	) );

	$notice = apply_filters( 'pmpromm_default_map_notice', __( 'This map could not be loaded. Please ensure that you have entered your Google Maps API Key and that there are no JavaScript errors on the page.', 'pmpro-membership-maps' ) );

	$start = apply_filters( 'pmpromm_load_markers_start', 0, $levels, $marker_attributes );
	$limit = apply_filters( 'pmpromm_load_markers_limit', 100, $levels, $marker_attributes );
	//Get the marker data
	$marker_data = pmpromm_load_marker_data( $levels, $marker_attributes, $start, $limit );

	$api_key = pmpro_getOption( 'pmpromm_api_key' );

	$libraries = apply_filters( 'pmpromm_google_maps_libraries', array() );

	wp_enqueue_script( 'jquery' );

	wp_enqueue_script( 'pmpro-membership-maps-google-maps', add_query_arg( array( 'key' => $api_key, 'libraries' => implode( ",", $libraries ), 'v' => '3', 'style' => trim( 
			preg_replace( "/\s+/", "", str_replace( " ", "", apply_filters( 'pmpromm_map_styles', '', $map_id ) ) )
		) ), 'https://maps.googleapis.com/maps/api/js' ) );

	wp_register_script( 'pmpro-membership-maps-javascript', plugins_url( 'js/user.js', __FILE__ ) );

	wp_enqueue_style( 'pmpro-membership-maps-styling', plugins_url( 'css/user.css', __FILE__ ) );

	/**
	 * Setup defaults for the map. We're passing through the map_id attribute
	 * to allow developers to differentiate maps. 
	 */

	$map_styles = apply_filters( 'pmpromm_map_styles', '', $map_id );
	$map_styles = str_replace( " ", "", $map_styles );
	$map_styles = preg_replace( "/\n+/", "", $map_styles );
	$map_styles = preg_replace( "/\s+/", "", $map_styles );

	wp_localize_script( 'pmpro-membership-maps-javascript', 'pmpromm_vars', array(
		'default_start' => apply_filters( 'pmpromm_default_map_start', array( 'lat' => -34.397, 'lng' => 150.644 ), $map_id ),
		'override_first_marker_location' => apply_filters( 'pmpromm_override_first_marker', '__return_false', $map_id ),
		'infowindow_width' => $infowindow_width,
		'marker_data' => $marker_data,
		'zoom_level' => $zoom,
		'infowindow_classes' => pmpromm_get_element_class( 'pmpromm_infowindow' ),
		'map_styles' => $map_styles		
	) );


	wp_enqueue_script( 'pmpro-membership-maps-javascript' );

	return "<div id='pmpromm_map' class='pmpromm_map pmpro_map_id_".$map_id."' style='height: ".$height."px; width: ".$width."%;'>".$notice."</div>";

}
add_shortcode( 'pmpro_membership_maps', 'pmpromm_shortcode' );

function pmpromm_load_marker_data( $levels = false, $marker_attributes = array(), $start = 0, $limit = 100, $s = "", $pn = false, $order_by = false, $order = false, $end = false ){
	/**
	 * We're adding in support for $pn, $order_by, $order and $end to allow the pmpro_membership_maps_sql_parts
	 * to be used in the same function as one would filter the Member Directory filter pmpromm_sql
	 * Some of these variables are ignored in the query
	 */

	global $wpdb;

	$sql_parts = array();

	$sql_parts['SELECT'] = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, u.user_nicename, u.display_name, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, m.name as membership, umf.meta_value as first_name, uml.meta_value as last_name, umlat.meta_value as lat, umlng.meta_value as lng FROM $wpdb->users u ";

	$sql_parts['JOIN'] = "
	LEFT JOIN $wpdb->usermeta umh ON umh.meta_key = 'pmpromd_hide_directory' AND u.ID = umh.user_id 
	LEFT JOIN $wpdb->usermeta umf ON umf.meta_key = 'first_name' AND u.ID = umf.user_id 
	LEFT JOIN $wpdb->usermeta uml ON uml.meta_key = 'last_name' AND u.ID = uml.user_id 
	LEFT JOIN $wpdb->usermeta umlat ON umlat.meta_key = 'pmpro_lat' AND u.ID = umlat.user_id 
	LEFT JOIN $wpdb->usermeta umlng ON umlng.meta_key = 'pmpro_lng' AND u.ID = umlng.user_id 
	LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id 
	LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id 
	LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id ";

	$sql_parts['WHERE'] = "WHERE mu.status = 'active' AND (umh.meta_value IS NULL OR umh.meta_value <> '1') AND mu.membership_id > 0 AND umlat.meta_value IS NOT NULL ";

	$sql_parts['GROUP'] = "GROUP BY u.ID ";

	//Wouldn't need this for the map
	// $sql_parts['ORDER'] = "ORDER BY ". esc_sql($order_by) . " " . $order . " ";

	$sql_parts['LIMIT'] = "LIMIT $start, $limit";

	if( $s ) {
		$sql_parts['WHERE'] .= "AND (u.user_login LIKE '%" . esc_sql($s) . "%' OR u.user_email LIKE '%" . esc_sql($s) . "%' OR u.display_name LIKE '%" . esc_sql($s) . "%' OR um.meta_value LIKE '%" . esc_sql($s) . "%') ";
	}

	// If levels are passed in.
	if ( $levels ) {
		$sql_parts['WHERE'] .= "AND mu.membership_id IN(" . esc_sql($levels) . ") ";
	}

	// Allow filters for SQL parts.
	$sql_parts = apply_filters( 'pmpro_membership_maps_sql_parts', $sql_parts, $levels, $s, $pn, $limit, $start, $end );

	$sqlQuery = $sql_parts['SELECT'] . $sql_parts['JOIN'] . $sql_parts['WHERE'] . $sql_parts['GROUP'] . 
	// $sql_parts['ORDER'] . 
	$sql_parts['LIMIT'];


	$sqlQuery = apply_filters("pmpro_membership_maps_sql", $sqlQuery, $levels, $s, $pn, $limit, $start, $end, $order_by, $order );

	$members = $wpdb->get_results( $sqlQuery, ARRAY_A );

	$marker_array = pmpromm_build_markers( $members, $marker_attributes );

	return apply_filters( 'pmpromm_return_markers_array', $marker_array );

}

function pmpromm_build_markers( $members, $marker_attributes ){

	global $wpdb, $post, $pmpro_pages, $pmprorh_registration_fields;

	if( !empty( $marker_attributes['show_avatar'] ) && ( 
		$marker_attributes['show_avatar'] === "0" || 
		$marker_attributes['show_avatar'] === "false" || 
		$marker_attributes['show_avatar'] === "no" || 
		$marker_attributes['show_avatar'] === false ) 
	){
		$show_avatar = false;
	} else {
		$show_avatar = true;
	}

	if( $marker_attributes['link'] === "0" || 
		$marker_attributes['link'] === "false" || 
		$marker_attributes['link'] === "no" || 
		$marker_attributes['link'] === false
	){
		$link = false;
	} else {
		$link = true;
	}

	if( $marker_attributes['show_email'] === "0" || 
		$marker_attributes['show_email'] === "false" || 
		$marker_attributes['show_email'] === "no" || 
		$marker_attributes['show_email'] === false 
	){
		$show_email = false;
	} else {
		$show_email = true;
	}

	if( $marker_attributes['show_level'] === "0" || 
		$marker_attributes['show_level'] === "false" || 
		$marker_attributes['show_level'] === "no" || 
		$marker_attributes['show_level'] === false
	){
		$show_level = false;
	} else {
		$show_level = true;
	}

	if( $marker_attributes['show_startdate'] === "0" || 
		$marker_attributes['show_startdate'] === "false" || 
		$marker_attributes['show_startdate'] === "no" || 
		$marker_attributes['show_startdate'] === false 
	){
		$show_startdate = false;
	} else {
		$show_startdate = true;
	}

	if( !empty( $marker_attributes['fields'] ) ) {
		// Check to see if the Block Editor is used or the shortcode.
		if ( strpos( $marker_attributes['fields'], "\n" ) !== FALSE ) {
			$fields = rtrim( $marker_attributes['fields'], "\n" ); // clear up a stray \n
			$fields_array = explode("\n", $marker_attributes['fields']); // For new block editor.
		} else {
			$fields = rtrim( $marker_attributes['fields'], ';' ); // clear up a stray ;
			$fields_array = explode(";",$marker_attributes['fields']);
		}
		if( !empty( $fields_array ) ){
			for($i = 0; $i < count($fields_array); $i++ ){
				$fields_array[$i] = explode(",", trim($fields_array[$i]));
			}
		}
	} else {
		$fields_array = false;
	}

	// Get Register Helper field options
	$rh_fields = array();

	if(!empty($pmprorh_registration_fields)) {
		foreach($pmprorh_registration_fields as $location) {
			
			foreach($location as $field) {
				
				if(!empty($field->options))
					$rh_fields[$field->name] = $field->options;
			}
		}
	}

	// var_dump($rh_fields);
	$marker_array = array();

	if( !empty( $members ) ){
		foreach( $members as $member ){
			$member_array = array();

			if( empty( $member['lat'] ) || empty( $member['lng'] ) ){
				continue;
			}

			$member_array['ID'] = $member['ID'];
			$member_array['marker_meta']['lat'] = $member['lat'];
			$member_array['marker_meta']['lng'] = $member['lng'];

			$member['meta'] = get_user_meta( $member['ID'] );


			if( !empty( $pmpro_pages['profile'] ) ) {
				$profile_url = apply_filters( 'pmpromm_profile_url', get_permalink( $pmpro_pages['profile'] ) );
			}

			$name_content = "";
			$name_content .= '<h3 class="'.pmpromm_get_element_class( 'pmpromm_display-name' ).'">';
				if( !empty( $link ) && !empty( $profile_url ) ) {
					$name_content .= '<a href="'.add_query_arg( 'pu', $member['user_nicename'], $profile_url ).'">'.$member['display_name'].'</a>';
				} else {
					$name_content .= $member['display_name'];
				}
			$name_content .= '</h3>';

			//This will allow us to hook into the content and add custom fields from RH
			$avatar_content = "";
			if( $show_avatar ){
				$avatar_align = ( !empty( $marker_attributes['avatar_align'] ) ) ? $marker_attributes['avatar_align'] : "";
				$avatar_content .= '<div class="'.pmpromm_get_element_class( 'pmpromm_avatar' ).'">';
					if( !empty( $marker_attributes['link'] ) && !empty( $profile_url ) ) {
						$avatar_content .= '<a class="'.pmpromm_get_element_class( $avatar_align ).'" href="'.add_query_arg('pu', $member['user_nicename'], $profile_url).'">'.get_avatar( $member['ID'], $marker_attributes['avatar_size'], NULL, $member['display_name'] ).'</a>';
					} else {
						$avatar_content .= '<span class="'.pmpromm_get_element_class( $avatar_align ).'">'.get_avatar( $member['ID'], $marker_attributes['avatar_size'], NULL, $member['display_name'] ).'</span>';
					}
				$avatar_content .= '</div>';
			}

			$email_content = "";
			if( $show_email ){
				$email_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_email' ).'">';
					$email_content .= '<strong>'.__( 'Email Address', 'pmpro-membership-maps' ).'</strong>&nbsp;';
					$email_content .= $member['user_email'];
				$email_content .= '</p>';
			}

			$level_content = "";
			if( $show_level ){
				$level_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_level' ).'">';
				$level_content .= '<strong>'.__('Level', 'pmpro-membership-maps').'</strong>&nbsp;';
				$level_content .= $member['membership'];
				$level_content .= '</p>';
			}

			$startdate_content = "";
			if( $show_startdate ){
				$startdate_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_date' ).'">';
				$startdate_content .= '<strong>'.__('Start Date', 'pmpro-membership-maps').'</strong>&nbsp;';
				$startdate_content .= date( get_option("date_format"), $member['joindate'] );
				$startdate_content .= '</p>';
			}

			$profile_content = "";
			if( !empty( $link ) && !empty( $profile_url ) ) {
				$profile_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_profile' ).'"><a href="'.add_query_arg( 'pu', $member['user_nicename'], $profile_url ).'">'.apply_filters( 'pmpromm_view_profile_text', __( 'View Profile', 'pmpro-membership-maps' ) ).'</a></p>';
			}

			$rhfield_content = "";

			if( !empty( $fields_array ) ){
				foreach( $fields_array as $field ){
					
					if ( WP_DEBUG ) {
						error_log("Content of field data: " . print_r( $field, true));
					}

					// Fix for a trailing space in the 'fields' shortcode attribute.
					if ( $field[0] === '' || empty( $field[1] ) ) {
						break;
					}

					if( !empty( $member['meta'][$field[1]] ) ){

						$current_field_key = $field[0];
						$current_field_val = reset( $member['meta'][$field[1]] );

						$rhfield_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_'.$current_field_key ).'">';
						if( is_array( $field ) && !empty( $field['filename'] ) ){
							//this is a file field
							$rhfield_content .= '<strong>'.$current_field_key.'</strong>';
							$rhfield_content .= pmpromm_display_file_field($member['meta'][$field[1]]);
						} elseif ( is_array( $field ) ){
							$cf_field = array();
							//this is a general array, check for Register Helper options first
							if(!empty($rh_fields[$field[1]])) {								
								foreach($field as $key => $value){
									$cf_field[$current_field_key] = $rh_fields[$field[1]][$current_field_val];
								}
							} else {
								$cf_field[] = $current_field_val;
							}
							$rhfield_content .= '<strong>'.$current_field_key.'</strong> ';
							$rhfield_content .= implode(", ",$cf_field);
						} elseif ( !empty( $rh_fields[$field[1]] ) && is_array( $rh_fields[$field[1]] ) ) {
							$rhfield_content .= '<strong>'.$current_field_val.'</strong>';
							$rhfield_content .= $rh_fields[$field[1]][$current_field];
						} elseif ( $field[1] == 'user_url' ){
							$rhfield_content .= '<a href="'.$member[$field[1]].'" target="_blank">'.$field[0].'</a>';
						} else {
							$rhfield_content .= '<strong>'.$field[0].':</strong>';
							$rhfield_content .= make_clickable($member[$field[1]]);
						}

						$rhfield_content .= '</p>';

					}
				}
			}

			$marker_content_order = apply_filters( 'pmpromm_marker_content_order', array(
				'name' 		=> $name_content,
				'avatar' 	=> $avatar_content,
				'email' 	=> $email_content,
				'level'		=> $level_content,
				'startdate' => $startdate_content,
				'rh_fields'	=> $rhfield_content,
				'profile'	=> $profile_content,
			) );

			$member_array['marker_content'] = implode( " ", $marker_content_order );

			$marker_array[] = $member_array;
		}
	}

	return $marker_array;

}

function pmpromm_after_checkout( $user_id, $morder ){

	$member_address = array(
		'street' 	=> '',
		'city' 		=> '',
		'state' 	=> '',
		'zip' 		=> ''
	);

	if( !empty( $morder->billing->street ) ){
		//Billing details are active, we can geocode
		$member_address = array(
			'street' 	=> $morder->billing->street,
			'city' 		=> $morder->billing->city,
			'state' 	=> $morder->billing->state,
			'zip' 		=> $morder->billing->zip
		);
	}

	$member_address = apply_filters( 'pmpromm_member_address_after_checkout', $member_address, $user_id, $morder );

	$coordinates = pmpromm_geocode_address( $member_address, $morder );

	if( is_array( $coordinates ) ){
		if( !empty( $coordinates['lat'] ) && !empty( $coordinates['lng'] ) ){
			update_user_meta( $user_id, 'pmpro_lat', $coordinates['lat'] );
			update_user_meta( $user_id, 'pmpro_lng', $coordinates['lng'] );
		}
	}

}
add_action( 'pmpro_after_checkout', 'pmpromm_after_checkout', 10, 2 );

function pmpromm_update_billing_info( $morder ){

	global $current_user;

	if( !empty( $_REQUEST['baddress1'] ) ){
		//Billing details are active, we can geocode
		$member_address = array(
			'street' 	=> $_REQUEST['baddress1'].' '.$_REQUEST['baddress2'],
			'city' 		=> $_REQUEST['bcity'],
			'state' 	=> $_REQUEST['bstate'],
			'zip' 		=> $_REQUEST['bzipcode']
		);

		$member_address = apply_filters( 'pmpromm_member_address_after_checkout', $member_address, $current_user->ID, $morder );

		$coordinates = pmpromm_geocode_address( $member_address, $morder );

		if( is_array( $coordinates ) ){
			if( !empty( $coordinates['lat'] ) && !empty( $coordinates['lng'] ) ){
				update_user_meta( $current_user->ID, 'pmpro_lat', $coordinates['lat'] );
				update_user_meta( $current_user->ID, 'pmpro_lng', $coordinates['lng'] );
			}
		}
	}

}
add_action( 'pmpro_billing_after_preheader', 'pmpromm_update_billing_info', 10, 1 );

//Adds API Key field to advanced settings page
function pmpromm_advanced_settings_field( $fields ) {

	$fields['pmpromm_api_key'] = array(
		'field_name' => 'pmpromm_api_key',
		'field_type' => 'text',
		'label' => __( 'Google Maps API Key', 'pmpro-membership-maps' ),
		'description' => __( 'Used by the Membership Maps Add On.', 'pmpro-membership-maps')
	);

	if( defined( 'PMPRO_VERSION' ) ){
		if( version_compare( PMPRO_VERSION, '2.4.2', '>=' ) ){
			$fields['pmpromm_api_key']['description'] = sprintf( __( 'Used by the Membership Maps Add On. %s', 'pmpro-membership-maps' ), '<a href="https://www.paidmembershipspro.com/add-ons/membership-maps/#google-maps-api-key" target="_BLANK">'.__( 'Obtain Your Google Maps API Key', 'pmpro-membership-maps' ).'</a>' );
		}
	}

	return $fields;
}
add_filter('pmpro_custom_advanced_settings','pmpromm_advanced_settings_field', 20);

/*
Function to add links to the plugin row meta
*/
function pmpromm_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-membership-maps.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/membership-maps/') . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-membership-maps' ) ) . '">' . __( 'Docs', 'pmpro-membership-maps' ) . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-membership-maps' ) ) . '">' . __( 'Support', 'pmpro-membership-maps' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpromm_plugin_row_meta', 10, 2);

/**
 * Add 'settings' to plugin action links.
 */
function pmpromm_add_action_links( $action_links ) {

	$pmpromm_links = array( 
		'<a href="' . esc_url( admin_url() . 'admin.php?page=pmpro-advancedsettings' ) . '" title="' . esc_attr( __( 'View Settings', 'pmpro-membership-maps' ) ) . '">' . __( 'API Settings', 'pmpro-membership-maps' ) . '</a>'
	);
	return array_merge( $pmpromm_links, $action_links );
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'pmpromm_add_action_links' );


//Load text domain
function pmpromm_load_textdomain() {

	$plugin_rel_path = basename( dirname( __FILE__ ) ) . '/languages';
	load_plugin_textdomain( 'pmpro-membership-maps', false, $plugin_rel_path );

}
add_action( 'plugins_loaded', 'pmpromm_load_textdomain' );

//Show map on directory page
function pmpromm_load_map_directory_page( $sqlQuery, $atts ){

	$attributes = array(
		'link' => $atts['link'],
		'avatar_size' => $atts['avatar_size'] ,
		'show_avatar' => $atts['show_avatar'] ,
		'show_email' => $atts['show_email'] ,
		'show_level' => $atts['show_level'] ,
		'show_startdate' => $atts['show_startdate'] ,
		'avatar_align' => $atts['avatar_align'] ,
		'fields' => $atts['fields'],
		'zoom' => isset( $atts['zoom'] ) ? $atts['zoom'] : '8'
	);

	echo pmpromm_shortcode( $attributes );

}
add_action( 'pmpro_member_directory_before', 'pmpromm_load_map_directory_page', 10, 2 );

/**
 * Adds the zoom level to the Membership Directory pages
 */
function pmpromm_add_zoom_level_directory_page( $atts ){

	$atts['zoom'] = apply_filters( 'pmpromm_default_zoom_level', '8' ); //Must be a string to prevent any PHP errors

	return $atts;

}
add_filter( 'pmpro_member_directory_before_atts', 'pmpromm_add_zoom_level_directory_page', 10, 1 );

//If we're on the profile page, only show that member's marker
function pmpromm_load_profile_map_marker( $sql_parts, $levels, $s, $pn, $limit, $start, $end ){

	if( isset( $_REQUEST['pu'] ) ){

	    //Get the profile user - doing this helps when profile's nicenames look like email addresses. This caused issues in the past.
		if(!empty($_REQUEST['pu']) && is_numeric($_REQUEST['pu']))
			$pu = get_user_by('id', $_REQUEST['pu']);
		elseif(!empty($_REQUEST['pu']))
			$pu = get_user_by('slug', $_REQUEST['pu']);
		elseif(!empty($current_user->ID))
			$pu = $current_user;
		else
			$pu = false;
				
		unset($sql_parts['GROUP']);

		if( $pu ){

		    $member = sanitize_text_field( $pu->data->user_login );

			$sql_parts['WHERE'] .= "AND (u.user_login LIKE '%" . esc_sql($member) . "%' OR u.user_email LIKE '%" . esc_sql($member) . "%' OR u.display_name LIKE '%" . esc_sql($member) . "%' OR um.meta_value LIKE '%" . esc_sql($member) . "%') ";
			
		}

	}

	return $sql_parts;

}
add_filter( 'pmpro_membership_maps_sql_parts', 'pmpromm_load_profile_map_marker', 10, 7 );

//Adds the map to the profile page
function pmpromm_show_single_map_profile( $pu ){

	if( !empty( $pu->ID ) ){

		$lat = get_user_meta( $pu->ID, 'pmpro_lat', true );
		$lng = get_user_meta( $pu->ID, 'pmpro_lng', true );

		$baddress1 = get_user_meta( $pu->ID, 'pmpro_baddress1', true );

		if( ( empty( $lat ) || empty( $lng ) ) && !empty( $baddress1 ) ){
			//Coordinates are empty but address isn't, lets try geocode
			$member_address = array(
				'street' 	=> $baddress1 .' '. get_user_meta( $pu->ID, 'pmpro_baddress2', true ),
				'city' 		=> get_user_meta( $pu->ID, 'pmpro_bcity', true ),
				'state' 	=> get_user_meta( $pu->ID, 'pmpro_bstate', true ),
				'zip' 		=> get_user_meta( $pu->ID, 'pmpro_bzipcode', true )
			);

			$member_address = apply_filters( 'pmpromm_single_map_address_geocode', $member_address, $pu );

			$coordinates = pmpromm_geocode_address( $member_address );

			if( is_array( $coordinates ) ){
				update_user_meta( $pu->ID, 'pmpro_lat', $coordinates['lat'] );
				update_user_meta( $pu->ID, 'pmpro_lng', $coordinates['lng'] );
			}

		}

		if( !empty( $lat ) && !empty( $lng ) ){
			echo do_shortcode( '[pmpro_membership_maps]' );
		}

	}

}
add_action( 'pmpro_member_profile_before', 'pmpromm_show_single_map_profile', 10, 1 );

function pmpromm_display_file_field( $meta_field ) {
	$meta_field_file_type = wp_check_filetype($meta_field['fullurl']);
	switch ($meta_field_file_type['type']) {
		case 'image/jpeg':
		case 'image/png':
		case 'image/gif':
			return '<a href="' . $meta_field['fullurl'] . '" title="' . $meta_field['filename'] . '" target="_blank"><img class="subtype-' . $meta_field_file_type['ext'] . '" src="' . $meta_field['fullurl'] . '"><span class="'.pmpromm_get_element_class( 'pmpromm_filename' ).'">' . $meta_field['filename'] . '</span></a>'; break;
	case 'video/mpeg':
	case 'video/mp4':
		return do_shortcode('[video src="' . $meta_field['fullurl'] . '"]'); break;
	case 'audio/mpeg':
	case 'audio/wav':
		return do_shortcode('[audio src="' . $meta_field['fullurl'] . '"]'); break;
	default:
		return '<a href="' . $meta_field['fullurl'] . '" title="' . $meta_field['filename'] . '" target="_blank"><img class="subtype-' . $meta_field_file_type['ext'] . '" src="' . wp_mime_type_icon($meta_field_file_type['type']) . '"><span class="'.pmpromm_get_element_class( 'pmpromm_filename' ).'">' . $meta_field['filename'] . '</span></a>'; break;
	}
}

function pmpromm_get_element_class( $class, $element = null ){

	if( function_exists( 'pmpro_get_element_class' ) ){
		return pmpro_get_element_class( $class, $element );
	}

	return $class;
}

function pmpromm_geocode_address( $addr_array, $morder = false ){

	$address_string = implode( ", ", array_filter( $addr_array ) );

	$remote_request = wp_remote_get( 'https://maps.googleapis.com/maps/api/geocode/json', 
		array( 'body' => array(
			'key' 		=> apply_filters( 'pmpromm_geocoding_api_key', pmpro_getOption( 'pmpromm_api_key' ) ),
			'address' 	=> $address_string
		) ) 
	);

	if( !is_wp_error( $remote_request ) ){

		$request_body = wp_remote_retrieve_body( $remote_request );

		$request_body = json_decode( $request_body );

		if( !empty( $request_body->status ) && $request_body->status == 'OK' ){

			if( !empty( $request_body->results[0] ) ){

				$lat = $request_body->results[0]->geometry->location->lat;
				$lng = $request_body->results[0]->geometry->location->lng;

				do_action( 'pmpromm_geocode_response', $request_body, $morder );

				return apply_filters( 'pmpromm_geocode_return_array', array( 'lat' => $lat, 'lng' => $lng ), $request_body, $addr_array, $morder );

			}

		} else {

			pmpromm_report_geocode_api_error( $request_body );

		}

	}

}

/**
 * Error log if there are issues with the Google Maps API
 * @since 0.1
 */
function pmpromm_report_geocode_api_error( $response ){

	if( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'PMPROMM_DEBUG' ) && PMPROMM_DEBUG ) ){

		if( !empty( $response->error_message ) ){

			$to = get_bloginfo( 'admin_email' );

			if( defined( 'PMPROMM_DEBUG_EMAIL' ) && PMPROMM_DEBUG_EMAIL !== "" ){
				$to = PMPROMM_DEBUG_EMAIL;
			}

			$subj = sprintf( __('Paid Memberships Pro - Membership Maps: An Error Occurred - %s', 'pmpro-membership-maps' ), current_time( 'mysql') );

			$error = $response->status .': '. $response->error_message;

			if( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ){
				error_log( "Paid Memberships Pro - Membership Maps: ".$error );
			}

			$mail_error = apply_filters( 'pmpromm_enable_geocode_error_email', true );

			if( $mail_error ){
				wp_mail( $to, $subj, $error );
			}

		}

	}

}
