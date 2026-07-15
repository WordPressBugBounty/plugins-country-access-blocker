=== Country Access Blocker ===
Contributors: valerikluger
Author URI: https://premium-plugin.com/
Tags: country blocker, geo blocking, ip blocker, block country, block ip
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block or allow website visitors from specific countries based on IP geolocation. 

== Description ==

Country Access Blocker lets you restrict or allow access to your WordPress site based on visitor countries.

Features:
* Block visitors from specific countries
* Clean, GDPR-compliant country list
* Easy admin interface to configure blocked countries
* Enable or disable IP-based country blocking with one checkbox
* No external dependencies or WooCommerce required
* Uses ip-api.com free API for geolocation

This plugin is ideal if you want to restrict access from certain countries or comply with geo-based regulations.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/country-access-blocker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the **Country Blocker** menu in the WordPress admin.
4. Enable the checkbox for "Enable IP-based country blocking".
5. Select the countries you want to block.
6. Click "Save settings".

== Frequently Asked Questions ==

= Does this plugin work without enabling IP-based blocking? =

No. The plugin requires the IP check to be enabled to function. You must enable IP-based country blocking to use the plugin's blocking features.

= Which geolocation service does this plugin use? =

It uses the free ip-api.com API to determine visitor countries based on their IP addresses.

= Is the plugin GDPR compliant? =

The plugin does not store or transmit any personal data other than the visitor IP for the purpose of country detection. The country list is clean and minimal.

= Can I whitelist countries instead of blocking? =

Currently, the plugin works by selecting countries to block. Whitelisting is not supported in this version.


== Changelog ==

= 1.6 =
* Added all available local WP Languages
* Minor UI improvements

= 1.5 =
* Added North Korea to the country list
* Added a small admin info card and a review button
* Added a dismiss button to hide the admin card for 30 days
* Minor UI spacing improvements

= 1.4 =
* Added admin UI improvements to enable country blocking toggle
* Improved detected country display after saving settings
* Fixed unblock all button to not disable IP check toggle
* Minor bug fixes and code cleanup

= 1.3 =
* Initial public release

== Upgrade Notice ==

If upgrading from previous versions, please re-save the settings to ensure IP check toggle is correctly applied.

== License ==

This plugin is licensed under the GPLv2 or later.

== Support ==

For support or bug reports, please open an issue on the plugin's GitHub repository or contact the author.
