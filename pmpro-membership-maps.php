<?php
/**
 * Plugin Name: Paid Memberships Pro - Membership Maps Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/membership-maps/
 * Description: Display a map of members or for a single member's profile.
 * Version: 0.7.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-membership-maps
 * Domain Path: /languages
 */

//Includes the Member Edit Map Address Panel
require_once( plugin_dir_path( __FILE__ ) . 'includes/pmpro-class-member-edit-panel-map-address.php' );

function pmpromm_shortcode( $atts ){

	extract(shortcode_atts(array(
		'height' 		=> '400', //Uses px
		'width'			=> '100', //Uses %
		'zoom'			=> apply_filters( 'pmpromm_default_zoom_level', '8' ),
		'max_zoom' 		=> NULL,
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
		'fields' 		=> NULL,
		'limit' 		=> 100,
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
	$limit = apply_filters( 'pmpromm_load_markers_limit', $limit, $levels, $marker_attributes );

	//Get the marker data
	$marker_data = pmpromm_load_marker_data( $levels, $marker_attributes, $start, $limit );

	$api_key = get_option( 'pmpro_pmpromm_api_key' );

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
		'max_zoom' => $max_zoom,
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

	$sql_parts['SELECT'] = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, u.user_nicename, u.display_name, u.user_url, UNIX_TIMESTAMP(u.user_registered) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(mu.startdate) as startdate, UNIX_TIMESTAMP(mu.enddate) as enddate, umf.meta_value as first_name, uml.meta_value as last_name, umlat.meta_value as old_lat, umlng.meta_value as old_lng, ummap.meta_value as maplocation FROM $wpdb->users u ";

	$sql_parts['JOIN'] = "
	LEFT JOIN $wpdb->usermeta umh ON umh.meta_key = 'pmpromd_hide_directory' AND u.ID = umh.user_id 
	LEFT JOIN $wpdb->usermeta umf ON umf.meta_key = 'first_name' AND u.ID = umf.user_id 
	LEFT JOIN $wpdb->usermeta uml ON uml.meta_key = 'last_name' AND u.ID = uml.user_id 
    LEFT JOIN $wpdb->usermeta umlat ON umlat.meta_key = 'pmpro_lat' AND u.ID = umlat.user_id 
	LEFT JOIN $wpdb->usermeta umlng ON umlng.meta_key = 'pmpro_lng' AND u.ID = umlng.user_id 
	LEFT JOIN $wpdb->usermeta ummap ON ummap.meta_key = 'pmpromm_pin_location' AND u.ID = ummap.user_id 
	LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id 
	LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id ";

	$sql_parts['WHERE'] = "WHERE mu.status = 'active' AND (umh.meta_value IS NULL OR umh.meta_value <> '1') AND mu.membership_id > 0 AND ummap.meta_value IS NOT NULL ";

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

	if( isset( $marker_attributes['show_avatar'] ) && ( 
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

	$marker_array = array();

	if( !empty( $members ) ){
		foreach( $members as $member ){
			$member_array = array();

			$member_address = isset( $member['maplocation'] ) ? maybe_unserialize( $member['maplocation'] ) : '';

            if( empty( $member_address['latitude'] ) && $member_address['old_lat'] !== NULL && $member_address['old_lng'] !== NULL ) {
                //If we don't have a new address, check for a valid old address
                $member_array['marker_meta']['lat'] = $member_address['old_lat'];
			    $member_array['marker_meta']['lng'] = $member_address['old_lng'];
            } else {
                $member_array['marker_meta']['lat'] = $member_address['latitude'];
			    $member_array['marker_meta']['lng'] = $member_address['longitude'];
            }

			$member_array['ID'] = $member['ID'];

			$member['meta'] = get_user_meta( $member['ID'] );

			if( !empty( $pmpro_pages['profile'] ) ) {
				$profile_url = apply_filters( 'pmpromm_profile_url', get_permalink( $pmpro_pages['profile'] ) );
			}

			$name_content = "";
			$name_content .= '<h2 class="'.pmpromm_get_element_class( 'pmpromm_display-name' ).'">';
				if( !empty( $link ) && !empty( $profile_url ) ) {					
					$user_profile = pmpromm_profile_url( $member, $profile_url );
					$name_content .= '<a href="'.$user_profile.'">'.$member['display_name'].'</a>';
				} else {
					$name_content .= $member['display_name'];
				}
			$name_content .= '</h2>';

			//This will allow us to hook into the content and add custom fields from RH
			$avatar_content = "";
			if( $show_avatar ){
				$avatar_align = ( !empty( $marker_attributes['avatar_align'] ) ) ? $marker_attributes['avatar_align'] : "";
				$avatar_content .= '<div class="'.pmpromm_get_element_class( 'pmpromm_avatar' ).'">';
					if( !empty( $marker_attributes['link'] ) && !empty( $profile_url ) ) {
						$user_profile = pmpromm_profile_url( $member, $profile_url );
						$avatar_content .= '<a class="'.pmpromm_get_element_class( $avatar_align ).'" href="'.$user_profile.'">'.get_avatar( $member['ID'], $marker_attributes['avatar_size'], NULL, $member['display_name'] ).'</a>';
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

			// We may need to get all of the user's levels for MMPU compatibility. Declaring a variable here to hold that data.
			$user_levels = null;

			$level_content = "";
			if( $show_level ){
				$user_levels = pmpro_getMembershipLevelsForUser( $member['ID'] );
				$level_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_level' ).'">';
				if ( count ( $user_levels ) > 1 ) {
					$level_content .= '<strong>'.__( 'Levels', 'pmpro-membership-maps' ).'</strong>&nbsp;';
					$level_content .= implode( ', ', wp_list_pluck( $user_levels, 'name' ) );
				} else {
					$level_content .= '<strong>'.__( 'Level', 'pmpro-membership-maps' ).'</strong>&nbsp;';
					$level_content .= $user_levels[0]->name;
				}
				$level_content .= '</p>';
			}

			$startdate_content = "";
			if( $show_startdate ){
				// Make sure that we have the user's levels.
				if ( empty( $user_levels ) ) {
					$user_levels = pmpro_getMembershipLevelsForUser( $member['ID'] );
				}

				// Calculate their oldest startdate.
				$min_startdate = null;
				foreach( $user_levels as $level ) {
					if ( empty( $min_startdate ) || $level->startdate < $min_startdate ) {
						$min_startdate = $level->startdate;
					}
				}

				// Display the start date.
				$startdate_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_date' ).'">';
				$startdate_content .= '<strong>'.__('Start Date', 'pmpro-membership-maps').'</strong>&nbsp;';
				$startdate_content .= date_i18n( get_option( 'date_format' ), $min_startdate );
				$startdate_content .= '</p>';
			}

			// We should get rid of the user levels now so that it doesn't affect future loop iterations.
			unset( $user_levels );

			$profile_content = "";
			if( !empty( $link ) && !empty( $profile_url ) ) {
				$user_profile = pmpromm_profile_url( $member, $profile_url );
				$profile_content .= '<p class="'.pmpromm_get_element_class( 'pmpromm_profile' ).'"><a href="'.$user_profile.'">'.apply_filters( 'pmpromm_view_profile_text', __( 'View Profile', 'pmpro-membership-maps' ) ).'</a></p>';				
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

					if( !empty( $member['meta'][$field[1]] ) || !empty( $member[$field[1]] ) ){

						$current_field_key = $field[0];
						if( isset( $member['meta'][$field[1]] ) ) {
							$current_field_val = reset( $member['meta'][$field[1]] );
						} else {
							$current_field_val = $member[$field[1]];
						}

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
								$current_field_val = maybe_unserialize( $current_field_val );
								if( is_array( $current_field_val ) ) {
									//Adds support for serialized fields (typically multiselect)
									$cf_field[] = implode( ", ", $current_field_val );
								} else {
									// Check if the field is a valid URL and then try to make it clickable.
									if ( wp_http_validate_url( $current_field_val ) ) {
										$current_field_val = make_clickable( $current_field_val );
									}
									$cf_field[] = $current_field_val;	
								}
							}
							$rhfield_content .= '<strong>' . esc_html( $current_field_key ) . '</strong> ';
							$rhfield_content .= wp_kses_post( implode( ', ', $cf_field ) );
						} elseif ( !empty( $rh_fields[$field[1]] ) && is_array( $rh_fields[$field[1]] ) ) {
							$rhfield_content .= '<strong>' . esc_html( $current_field_val ) . '</strong>';
							$rhfield_content .= wp_kses_post( $rh_fields[$field[1]][$current_field] );
						} else {
							$rhfield_content .= '<strong>' . esc_html( $field[0] ) . ':</strong>';
							$rhfield_content .= make_clickable( $member[$field[1]] );
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

	pmpromm_save_pin_location_fields( $user_id );

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
			$fields['pmpromm_api_key']['description'] = sprintf( __( 'Used by the Membership Maps Add On. %s %s', 'pmpro-membership-maps' ), '<a href="https://www.paidmembershipspro.com/add-ons/membership-maps/#google-maps-api-key" target="_BLANK">' . __( 'Obtain Your Google Maps API Key', 'pmpro-membership-maps' ).'</a>', '<br/><code>' . __( 'API Key Status', 'pmpro-membership-maps' ).': ' . get_option( 'pmpro_pmpromm_api_key_status' ) ) . '</code>';
		}
	}

	return $fields;
}
add_filter('pmpro_custom_advanced_settings','pmpromm_advanced_settings_field', 20);

/**
 * Test the API key upon saving the PMPro Advanced Settings.

 * @return void
 */
function pmpromm_test_api_key() {

	if( ! empty( $_REQUEST['pmpromm_api_key'] ) && current_user_can( 'manage_options' ) ) {

		$current_key = get_option( 'pmpro_pmpromm_api_key' );

		$new_key = trim( sanitize_text_field( $_REQUEST['pmpromm_api_key'] ) );

		$api_key_status = get_option( 'pmpro_pmpromm_api_key_status' );

		//API key differs or the status is not OK, let's test the key.
		if ( $new_key !== $current_key || $api_key_status !== 'OK' ) {

			/**
			 * This is a sample address used to test if the API key entered works as expected. 
			 */
			$member_address = array(
				'street' 	=> '1313 Disneyland Drive',
				'city' 		=> 'Anaheim',
				'state' 	=> 'CA',
				'zip' 		=> '92802'
			);
			
			add_filter( 'pmpromm_geocoding_api_key', 'pmpromm_use_api_key_on_save' );
			$geocoded_result = pmpromm_geocode_address( $member_address, false, true );
			
			if( $geocoded_result->status == 'OK' ) {
				pmpro_setOption( 'pmpromm_api_key_status', 'OK' );				
			} else {
				$status = sanitize_text_field( $geocoded_result->status . ' ' . $geocoded_result->error_message );
				pmpro_setOption( 'pmpromm_api_key_status', $status );
			}			
				

		}

	}

}
add_action( 'admin_init', 'pmpromm_test_api_key' );

/**
 * Sets the geocoding API key to the $_REQUEST value instead of a stored value
 * @param  string $api_key The current API Key
 * @return string The API key found in the $_REQUEST var
 */
function pmpromm_use_api_key_on_save( $api_key ) {

	if ( ! empty( $_REQUEST['pmpromm_api_key'] ) ) {
		$api_key = trim( sanitize_text_field( $_REQUEST['pmpromm_api_key'] ) );
	}

	return $api_key;
}

/**
 * Adds the API key status to site health
 *
 * @param array $fields The site health fields
 */
function pmpromm_sitehealth_information( $fields ) {

	if( ! isset( $fields['pmpro'] ) ) {
		return $fields;
	}

	$map_data = array( 'pmpromm-api-key-status' => array(
		'label' => __( 'Membership Maps API Key Status', 'paid-memberships-pro' ),
		'value' => esc_html( get_option( 'pmpro_pmpromm_api_key_status' ) ),
	) );

	$fields['pmpro']['fields'] = array_merge( $fields['pmpro']['fields'], $map_data );

	return $fields;

}
add_filter( 'debug_information', 'pmpromm_sitehealth_information', 11, 1 );

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
		'zoom' => isset( $atts['zoom'] ) ? $atts['zoom'] : '8',
		'limit' => isset( $atts['limit'] ) ? $atts['limit'] : '100',
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

	global $wp_query;

	if( !empty( $wp_query->get( 'pu' ) ) ) {
		$pu = sanitize_text_field( $wp_query->get( 'pu' ) );
	} else { 
		if( !empty( $_REQUEST['pu'] ) ) {
			$pu = sanitize_text_field( $_REQUEST['pu'] );
		}
	}

	if( !empty( $pu ) ){

	    //Get the profile user - doing this helps when profile's nicenames look like email addresses. This caused issues in the past.
		if( !empty( $pu ) && is_numeric( $pu ) ) {
			$pu = get_user_by('id',  $pu );
		} elseif( !empty( $pu ) ) {
			$pu = get_user_by('slug',  $pu );
		} elseif( !empty( $current_user->ID ) ) {
			$pu = $current_user;
		} else {
			$pu = false;		
		}

		if( $pu ){

		    $member = sanitize_email( $pu->data->user_email );

			$sql_parts['WHERE'] .= " AND ( u.user_email = '" . esc_sql($member) . "' ) ";
			
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

function pmpromm_geocode_address( $addr_array, $morder = false, $return_body = false ){

	$address_string = implode( ", ", array_filter( $addr_array ) );

	$remote_request = wp_remote_get( 'https://maps.googleapis.com/maps/api/geocode/json', 
		array( 'body' => array(
			'key' 		=> apply_filters( 'pmpromm_geocoding_api_key', get_option( 'pmpro_pmpromm_api_key' ) ),
			'address' 	=> $address_string
		) ) 
	);

	if( !is_wp_error( $remote_request ) ){

		$request_body = wp_remote_retrieve_body( $remote_request );

		$request_body = json_decode( $request_body );

		if( $return_body ) {
			return $request_body;
		}

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

/**
 * Decides how the profile URL should be formatted if the Member Directory is active
 */
function pmpromm_profile_url( $pu, $profile_url ) {

	if( function_exists( 'pmpromd_build_profile_url' ) ) {
		/**
		 * Use the new permalink structure that gets used in Member Directory
		 * $pu comes in as an array - we cast it into an object so it works with the
		 * ret of member directory functions
		 */
		
		return esc_url( pmpromd_build_profile_url( (object)$pu, $profile_url ) );

	} else {
		//Stick to how we've always done it
		return add_query_arg( 'pu', $pu['user_nicename'], $profile_url );
	}		

}

/**
 * Geocodebilling fields when saving/updating a user profile
 *
 * @since 0.5
 */
function pmpro_geocode_billing_address_fields_frontend( $user_id ){

	if ( !function_exists( 'pmpromm_geocode_address' ) ){
		return;
	}

	pmpromm_save_pin_location_fields( $user_id );

}
add_action( 'pmpro_personal_options_update', 'pmpro_geocode_billing_address_fields_frontend', 10, 1 );
add_action( 'personal_options_update', 'pmpro_geocode_billing_address_fields_frontend', 10, 1 );
add_action( 'edit_user_profile_update', 'pmpro_geocode_billing_address_fields_frontend', 10, 1 );

/**
 * Strip the [pmpro_membership_maps] shortcode from content if the current user can't edit users.
 *
 * @since 0.7

 * @param string|array $content The content to strip the shortcode from.
 *                              If an array is passed in, all elements
 *                              will be filtered recursively.
 *                              Non-strings are ignored.
 *
 * @return mixed The content with the shortcode removed. Will be the same type as the input.
 */
function pmpromm_maybe_strip_shortcode( $content ) {
	// If the user can edit users, we don't need to strip the shortcode.
	if ( current_user_can( 'edit_users' ) ) {
		return $content;
	}

	// If an array is passed in, filter all elements recursively.
	if ( is_array( $content ) ) {
		foreach ( $content as $key => $value ) {
			$content[ $key ] = pmpromm_maybe_strip_shortcode( $value );
		}
		return $content;
	}

	// If we're not looking at a string, just return it.
	if ( ! is_string( $content ) ) {
		return $content;
	}
	
	// Okay, we have a string, figure out the regex.
	$shortcodeRegex = get_shortcode_regex( array( 'pmpro_membership_maps' ) );	

	// Replace shortcode wrapped in block comments.
	$blockWrapperPattern = "/<!-- wp:shortcode -->\s*$shortcodeRegex\s*<!-- \/wp:shortcode -->/s";
	$content = preg_replace( $blockWrapperPattern, '', $content );

	// Replace the shortcode by itself.
	$shortcodePattern = "/$shortcodeRegex/";
	$content = preg_replace( $shortcodePattern, '', $content );

	return $content;
}
add_filter( 'content_save_pre', 'pmpromm_maybe_strip_shortcode' );
add_filter( 'excerpt_save_pre', 'pmpromm_maybe_strip_shortcode' );
add_filter( 'widget_update_callback', 'pmpromm_maybe_strip_shortcode' );

/**
 * Only allow those with the edit_users capability
 * to use the pmpro_membership_maps shortcode in post_meta.
 *
 * @since 0.7
 * @param int    $meta_id     ID of the meta data entry.
 * @param int    $object_id   ID of the object the meta is attached to.
 * @param string $meta_key    Meta key.
 * @param mixed  $_meta_value Meta value.
 * @return void
 */
function pmpromm_maybe_strip_shortcode_from_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
	// Bail if the value is not a string or array.
	if ( ! is_string( $_meta_value ) && ! is_array( $_meta_value ) ) {
		return;
	}

	// Strip the shortcode from the meta value.
	$stripped_value = pmpromm_maybe_strip_shortcode( $_meta_value );

	// If there was a change, save our stripped version.
	if ( $stripped_value !== $_meta_value ) {
		update_post_meta( $object_id, $meta_key, $stripped_value );
	}
}
add_action( 'updated_post_meta', 'pmpromm_maybe_strip_shortcode_from_post_meta', 10, 4 );

/**
 * Saves the user's pin location to user meta
 *
 * @since TBD
 * @param mixed  $user_id User ID but not required
 * @return void
 */
function pmpromm_save_pin_location_fields( $user_id = false ) {

    $save = true;

    //No user ID provided, lets get the current user
    if( ! $user_id ) {
        
        $user_id = get_current_user_id();

        //Current user isn't logged in for some reason, bail
        if( ! $user_id ) {
            return;
        }

    }

    if( ! empty( $_REQUEST['pmpromm_street_name'] ) && $save ) {
        
        //geocode address
        $member_address = array(
            'street'    => ( ! empty( $_REQUEST['pmpromm_street_name'] ) ) ? sanitize_text_field( $_REQUEST['pmpromm_street_name'] ) : '',
            'city'      => ( ! empty( $_REQUEST['pmpromm_city'] ) ) ? sanitize_text_field( $_REQUEST['pmpromm_city'] ) : '',
            'state'     => ( ! empty( $_REQUEST['pmpromm_state'] ) ) ? sanitize_text_field( $_REQUEST['pmpromm_state'] ) : '',
            'zip'       => ( ! empty( $_REQUEST['pmpromm_zip'] ) ) ? sanitize_text_field( $_REQUEST['pmpromm_zip'] ) : '',
            'country'   => ( ! empty( $_REQUEST['pmpromm_country'] ) ) ? sanitize_text_field( $_REQUEST['pmpromm_country'] ) : ''
        );

        if( ! isset( $_REQUEST['pmpromm_optin'] ) ) {
            $member_address['optin'] = false;
        } else {
            $member_address['optin'] = true;
        }
        
        $coordinates = pmpromm_geocode_address( $member_address );
        
        if( is_array( $coordinates ) ){
            if( !empty( $coordinates['lat'] ) && !empty( $coordinates['lng'] ) ){
                $member_address['latitude'] = $coordinates['lat'];
                $member_address['longitude'] = $coordinates['lng'];

                //Only add to user meta if the address has been geocoded
                update_user_meta( $user_id, 'pmpromm_pin_location', $member_address );
            } else {
                //Cleanup pin location in case
                delete_user_meta( $user_id, 'pmpromm_pin_location' );
            }
        }

    } else {
        //Cleanup pin location in case
        delete_user_meta( $user_id, 'pmpromm_pin_location' );

    }
}

/**
 * Displays the address fields for the user
 *
 * @since TBD
 * @param mixed  $user_id User ID but not required
 * @param string $layout  Layout type - choice of div or table
 * @return string HTML output
 */
function pmpromm_show_pin_location_fields( $user_id = false, $layout = 'div' ) {

    //No user ID provided, lets get the current user
    if( ! $user_id ) {
        
        $user_id = get_current_user_id();

        //Current user isn't logged in for some reason, bail
        if( ! $user_id ) {
            return;
        }

    }

    $pin_location = get_user_meta( $user_id, 'pmpromm_pin_location', true );

    $pmpromm_street = isset( $pin_location['street'] ) ? $pin_location['street'] : '';
    $pmpromm_city = isset( $pin_location['city'] ) ? $pin_location['city'] : '';
    $pmpromm_state = isset( $pin_location['state'] ) ? $pin_location['state'] : '';
    $pmpromm_zip = isset( $pin_location['zip'] ) ? $pin_location['zip'] : '';
    $pmpromm_country = isset( $pin_location['country'] ) ? $pin_location['country'] : '';
    $pmpromm_optin = isset( $pin_location['optin'] ) ? $pin_location['optin'] : false;

    /**
     * Adds support for the old billing fields
     */
    if( empty( $pin_location ) ) {
        //We haven't got saved an address, lets check if there's a valid old address available
        $pmpromm_old_lat = get_user_meta( $user_id, 'pmpro_lat', true );
        $pmpromm_old_lng = get_user_meta( $user_id, 'pmpro_lng', true );

        if( ! empty( $pmpromm_old_lat ) && ! empty( $pmpromm_old_lng ) ){
            $pmpromm_street = get_user_meta( $user_id, 'pmpro_baddress1', true ).' '.get_user_meta( $user_id, 'pmpro_baddress2', true );
            $pmpromm_city = get_user_meta( $user_id, 'pmpro_bcity', true );
            $pmpromm_state = get_user_meta( $user_id, 'pmpro_bstate', true );
            $pmpromm_zip = get_user_meta( $user_id, 'pmpro_bzipcode', true );
            $pmpromm_country = get_user_meta( $user_id, 'pmpro_bcountry', true );
            $pmpromm_optin = true;
        }
        
    }

    if( $layout == 'div' ) {
        ?>
        <div id="pmpro_checkout_box-more-information" class="pmpro_checkout">
            <hr>
            <h2>
                <span class="pmpro_checkout-h2-name"><?php _e( 'Membership Map Address', 'paid-memberships-pro' ); ?></span>
            </h2>
            <div class="pmpromm_pin_location_field pmpro_checkout-fields">
            <div class='pmpro_checkout-field'>
                    <label for="pmpromm_optin"><?php _e( 'Show on Membership Map', 'pmpro-membership-maps' ); ?></label>
                    <input type="checkbox" id="pmpromm_optin" name="pmpromm_optin" value="1" <?php checked( 1, $pmpromm_optin ); ?> />
                    <p class="description"><?php _e( 'Check this box to include your address on the Membership Map. Unchecking this will empty the address fields.', 'pmpro-membership-maps' ); ?></p>
                </div>
                <div class='pmpro_checkout-field'>
                    <label for="pmpromm_street_name"><?php _e( 'Street Name', 'pmpro-membership-maps' ); ?></label>
                    <input type="text" id="pmpromm_street_name" name="pmpromm_street_name" value="<?php echo esc_attr( $pmpromm_street ); ?>" />
                </div>
                <div class="pmpromm_pin_location_field pmpro_checkout-field">
                    <label for="pmpromm_city"><?php _e( 'City', 'pmpro-membership-maps' ); ?></label>
                    <input type="text" id="pmpromm_city" name="pmpromm_city" value="<?php echo esc_attr( $pmpromm_city ); ?>" />
                </div>
                <div class="pmpromm_pin_location_field pmpro_checkout-field">
                    <label for="pmpromm_state"><?php _e( 'State', 'pmpro-membership-maps' ); ?></label>
                    <input type="text" id="pmpromm_state" name="pmpromm_state" value="<?php echo esc_attr( $pmpromm_state ); ?>" />
                </div>
                <div class="pmpromm_pin_location_field pmpro_checkout-field">
                    <label for="pmpromm_zip"><?php _e( 'Zip Code', 'pmpro-membership-maps' ); ?></label>
                    <input type="text" id="pmpromm_zip" name="pmpromm_zip" value="<?php echo esc_attr( $pmpromm_zip ); ?>" />
                </div>
                <div class="pmpromm_pin_location_field pmpro_checkout-field">
                    <label for="pmpromm_country"><?php _e( 'Country', 'pmpro-membership-maps' ); ?></label>
                    
                    <select name="pmpromm_country" id="pmpromm_country" class="<?php echo esc_attr( pmpro_get_element_class( '', 'bcountry' ) ); ?>">
                    <?php
                        global $pmpro_countries, $pmpro_default_country;
                        if(!$bcountry) {
                            $bcountry = $pmpro_default_country;
                        }
                        foreach($pmpro_countries as $abbr => $country) { ?>
                            <option value="<?php echo esc_attr( $abbr ) ?>" <?php if($abbr == $pmpromm_country) { ?>selected="selected"<?php } ?>><?php echo esc_html( $country )?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
    <?php
    } else {
        //Table layout
        ?>
        <table class="form-table">                
            <tr class='form-field'>
                <th>
                    <label for="pmpromm_optin"><?php _e( 'Show on Membership Map', 'pmpro-membership-maps' ); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="pmpromm_optin" name="pmpromm_optin" value="1" <?php checked( 1, $pmpromm_optin ); ?> />
                    <p class="description"><?php _e( 'Check this box to include your address on the Membership Map. Unchecking this will empty the address fields.', 'pmpro-membership-maps' ); ?></p>
                </td>
            </tr>
            <tr class='form-field'>
                <th>
                    <label for="pmpromm_street_name"><?php _e( 'Street Name', 'pmpro-membership-maps' ); ?></label>
                </th>
                <td>
                    <input type="text" id="pmpromm_street_name" name="pmpromm_street_name" value="<?php echo esc_attr( $pmpromm_street ); ?>" />
                </td>
            </tr>
            <tr class='form-field'>
                <th>
                    <label for="pmpromm_city"><?php _e( 'City', 'pmpro-membership-maps' ); ?></label>
                </th>
                <td>
                    <input type="text" id="pmpromm_city" name="pmpromm_city" value="<?php echo esc_attr( $pmpromm_city ); ?>" />
                </td>
            </tr>
            <tr class='form-field'>
                <th>
                    <label for="pmpromm_state"><?php _e( 'State', 'pmpro-membership-maps' ); ?></label>
                </th>
                <td>
                    <input type="text" id="pmpromm_state" name="pmpromm_state" value="<?php echo esc_attr( $pmpromm_state ); ?>" />
                </td>
            </tr>
            <tr class='form-field'>
                <th>
                    <label for="pmpromm_zip"><?php _e( 'Zip Code', 'pmpro-membership-maps' ); ?></label>
                </th>
                <td>
                    <input type="text" id="pmpromm_zip" name="pmpromm_zip" value="<?php echo esc_attr( $pmpromm_zip ); ?>" />
                </td>
            </tr>
            <tr class='form-field'>
                <th>
                    <label for="pmpromm_country"><?php _e( 'Country', 'pmpro-membership-maps' ); ?></label>
                </th>
                <td>
                    <select name="pmpromm_country" id="pmpromm_country" class="<?php echo esc_attr( pmpro_get_element_class( '', 'bcountry' ) ); ?>">
                        <?php
                            global $pmpro_countries, $pmpro_default_country;
                            if(!$bcountry) {
                                $bcountry = $pmpro_default_country;
                            }
                            foreach($pmpro_countries as $abbr => $country) { ?>
                                <option value="<?php echo esc_attr( $abbr ) ?>" <?php if($abbr == $pmpromm_country) { ?>selected="selected"<?php } ?>><?php echo esc_html( $country )?></option>
                            <?php } ?>
                    </select>
                </td>
            </tr> 
        </table>
        <?php
    }
}

/**
 * Displays the address fields for the user on the checkout page
 *
 * @since TBD
 * @return string HTML output
 */
function pmpromm_checkout_fields(){
    pmpromm_show_pin_location_fields();
}
add_action( 'pmpro_checkout_boxes', 'pmpromm_checkout_fields' );

/**
 * Displays the address fields for the user on the profile page
 *
 * @since TBD
 * @return string HTML output
 */
function pmpromm_edit_profile_fields( $current_user ){
    pmpromm_show_pin_location_fields( $current_user->ID );
}
add_action( 'pmpro_show_user_profile', 'pmpromm_edit_profile_fields', 10, 1 );