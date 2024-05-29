<?php

/**
 * Extends the Member Edit Panel class to add a new panel for the member address
 *
 * @since TBD
 */
class PMPro_Member_Edit_Panel_Map_Address extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$user = self::get_user();
		$this->slug = 'map_address';
		$this->title = __( 'Membership Map Address', 'paid-memberships-pro' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
        
        $user = self::get_user();

		?>
		<div id="pmpromm-map-address">
			<?php pmpromm_show_pin_location_fields( $user->ID, 'table' ); ?>
            <div>
                <button type="submit" name="pmpro-member-edit-memberships-panel-member_address" class="button button-primary" value=""><?php _e( 'Save Map Address', 'paid-memberships-pro' ); ?></button>
            </div>
		</div> <!-- end #member-history-orders -->
		<?php
	}

    /**
	 * Save panel data
	 */
	public function save() {

		// If the current user can't edit users, bail.
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		// Get the user we are editing
		$user = self::get_user();

        if( ! empty( $_REQUEST['pmpro_member_edit_panel'] ) && $_REQUEST['pmpro_member_edit_panel'] == 'map_address' ) {
            pmpromm_save_pin_location_fields( $user->ID );
        }
        
    }

	/**
	 * Check if the current user can view this panel.
	 * Can be overridden by child classes.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_show() {
		return current_user_can( 'manage_options' );
	}
}

/**
 * Adds a panel to the Edit Member Dashboard
 *
 * @since TBD
 */
function pmpromm_edit_panel_map_address( $panels ){
    $panels[] = new PMPro_Member_Edit_Panel_Map_Address();

    return $panels;
}
add_filter( 'pmpro_member_edit_panels', 'pmpromm_edit_panel_map_address' );