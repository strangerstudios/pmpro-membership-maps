jQuery(document).ready(function() {
    // Initially hide the address fields if the checkbox is not checked
    if (!jQuery('#pmpromm_optin').is(':checked')) {
        jQuery('#pmpromm_address_fields').hide();
    }

    // Toggle visibility when the checkbox state changes
    jQuery('#pmpromm_optin').on('change', function() {
        if (jQuery(this).is(':checked')) {
            jQuery('#pmpromm_address_fields').show();
        } else {
            jQuery('#pmpromm_address_fields').hide();
        }
    });
});