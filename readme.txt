=== Paid Memberships Pro - Membership Maps Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, map
Requires at least: 3.5
Tested up to: 5.5
Stable tag: 0.2

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

= 0.2 07-10-2020 =
* BUG FIX: Fixed an error where an incorrect variable was referenced in JavaScript.
* BUG FIX: Fixed a warning for empty marker coordinates.
* ENHANCEMENT: Added in link to documentation on how to get a Google Maps API key on the 'Advanced Settings' page.
* ENHANCEMENT: General improvements to code to handle larger amounts of markers. Defaults to 100 markers at a time.
* ENHANCEMENT: New filters added to allow a start and end for loading markers to let developer's load only a certain amount of markers. 'pmpromm_load_markers_start' and 'pmpromm_load_markers_limit' respectively.
* ENHANCEMENT: Support internationalization and loaded general .pot, .po and .mo files for translations.

= 0.1 =
* Initial commit.
