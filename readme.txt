=== Wp Parser Version ===
Contributors: keesiemeijer
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Parse plugins to see what (PHP, WP, plugin) functions and classes they use. 

== Description ==

This plugin parses WordPress plugins to see which (PHP, WP, plugin) functions and classes they use. The [WP Parser](https://github.com/WordPress/phpdoc-parser) plugin is used to parse the plugins. After parsing the results are displayed and the minimum `Requires at least` version is calculated. It also displays a notice if deprecated functions or classes are used by the plugin.

To get the relevant WordPress information (since, deprecated etc) this plugin references JSON files (created by the [WP Parser JSON](https://github.com/keesiemeijer/wp-parser-json) plugin).

The last WordPress version to create the JSON files is: WordPress 4.9.0