=== Paid Memberships Pro - Membership Maps Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, map
Requires at least: 5.2
Tested up to: 6.4
Stable tag: 0.7

Display a map of members or for a single member's profile.

== Description ==

Display a map of members via shortcode or on the frontend pages for the [Member Directory and Profiles Add On](https://www.paidmembershipspro.com/add-ons/member-directory/) for Paid Memberships Pro.

= Official Paid Memberships Pro Add On =

This is an official Add On for [Paid Memberships Pro](https://www.paidmembershipspro.com), the most complete member management and membership subscriptions plugin for WordPress.

= Shortcode Attributes =
This plugin creates a shortcode [member_maps] you can place on any page of your WordPress site. Membership Maps will also display automatically on the Member Directory and Profiles Add On.

[View Shortcode Attributes](https://www.paidmembershipspro.com/add-ons/membership-maps/#shortcode)

== Installation ==

= Download, Install and Activate! =
1. Upload the `pmpro-membership-maps` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

= Settings =

1. This plugin requires Paid Memberships Pro to be installed and activated.
1. By default, the map will geocode the member’s "Billing Address". Navigate to Memberships > Settings > Payment Gateway & SSL in the WordPress admin. Confirm the "Show Billing Address Fields" setting is set to "Yes".
1. Next, enter your Google Maps API Key on the Memberships > Settings > Advanced Settings page in the WordPress admin. [Click here for help obtaining up your Google Maps API Key](https://www.paidmembershipspro.com/add-ons/membership-maps/#google-maps-api-key).

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-membership-maps/issues

= I need help installing, configuring, or customizing the plugin. =

Please visit our premium support site at https://www.paidmembershipspro.com for more documentation and our support forums.

== Changelog ==
= 0.7 -2024-02-16 =
* SECURITY: Only users with the "edit_users" capability may add the pmpro_membership_maps shortcode to posts and widgets now.
* ENHANCEMENT: Added better support for the Membership Directory Limit attribute to apply to the map displayed on the Directory page.
* REFACTOR: Refactored marker data indexes to prevent conflicts.

= 0.6 - 2023-10-12 =
* ENHANCEMENT: Improved accessibility for screen readers. (@michaelbeil)
* ENHANCEMENT: Added 'max_zoom' attribute to the shortcode for how far the zoom is limited on the map. Accepts a value of 0-18. (@JarrydLong)
* ENHANCEMENT: Added support for Multiple Memberships Per User. (@dparker1005)

= 0.5 - 2022-09-22 =
* ENHANCEMENT: Test the API request whenever saving a new API Key to ensure it's all setup correctly.
* ENHANCEMENT: Improved functionality and sanitzation of custom fields in marker windows. URL values now are clickable.
* ENHANCEMENT: Geocode user address whenever their profile is saved or updated and billing fields are present on the edit user/profile page.

= 0.4 2022-07-27 =
* SECURITY: Improved sanitization and escaping of variables.
* ENHANCEMENT: Improved support for Membership Directory V1.2+.
* ENHANCEMENT: Improved the map logic on the single profile view of the Member Directory to show only the person in question marker.
* ENHANCEMENT: Improved the spacing around the map on the frontend.
* BUG FIX: Fixed an issue where 'show_avatar' in Membership Directory was always showing regardless of attribute value (@aquiferweb)

= 0.3 2021-06-09 =
* ENHANCEMENT: Added in support for Zoom levels for map. New filter 'pmpromm_default_zoom_level'.
* ENHANCEMENT: Filter added to geocoding API key. New filter 'pmpromm_geocoding_api_key'.
* ENHANCEMENT: Custom fields now supported to show up inside the markers info window.
* ENHANCEMENT: Code refactored and implemented better map styling options. New filter 'pmpromm_map_styles'.
* ENHANCEMENT: General improvements made to single map query.
* BUG FIX: Fixed an issue with shortcode attribute 'ID' conflicts and changed to 'map_id' to be more explicit.

= 0.2 2020-10-07 =
* BUG FIX: Fixed an error where an incorrect variable was referenced in JavaScript.
* BUG FIX: Fixed a warning for empty marker coordinates.
* ENHANCEMENT: Added in link to documentation on how to get a Google Maps API key on the 'Advanced Settings' page.
* ENHANCEMENT: General improvements to code to handle larger amounts of markers. Defaults to 100 markers at a time.
* ENHANCEMENT: New filters added to allow a start and end for loading markers to let developer's load only a certain amount of markers. 'pmpromm_load_markers_start' and 'pmpromm_load_markers_limit' respectively.
* ENHANCEMENT: Support internationalization and loaded general .pot, .po and .mo files for translations.

= 0.1 =
* Initial commit.
