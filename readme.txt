=== WP Plugin Parser ===
Contributors: keesiemeijer
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Parse plugins to see what (PHP, WP, plugin) functions and classes they use. 

== Description ==

Features:
* Warnings are displayed for plugins that use `deprecated` or `blacklisted` functions.
* Allows you to check if a plugin is compatible with a specific PHP version.
* The minimum `Requires at least` version needed for a plugin is automatically calculated.
* Exclude directories for parsing (e.g. `node_modules`, `vendor`).

The [WP Parser](https://github.com/WordPress/phpdoc-parser) is used for parsing.  
The [PHPCompatibility](https://github.com/wimg/PHPCompatibility) sniffs are used to check for PHP version compatibility.

To get the relevant WordPress information (since, deprecated etc) this plugin references [JSON files](https://github.com/keesiemeijer/wp-plugin-parser/tree/master/json-files) (created by the [WP Parser JSON](https://github.com/keesiemeijer/wp-parser-json) plugin).

See the `Parsed JSON` version above for what WordPress version is referenced.

= What gets parsed =
All plugin files, functions, classes and methods are parsed to see what functions, classes and methods they use. The `since` and `deprecated` attributes are only checked for functions and classes. Meaning, methods are not (yet) flagged when `deprecated`, and are not used for calculating the `Requires at least` version.

= False positives =
This tool can't detect if a plugin actively checks for compatibility with a PHP version. This means that if a plugin fails the compatibility check it could well be aware of this issue. You will have to inspect the plugin manually, to make sure it fails gracefully and doesn't produce errors.

[PHP language constructs](https://secure.php.net/manual/en/reserved.keywords.php) like `eval()` are not picked up by the parser. This plugin finds the language constructs `die()`, `eval()` and `exit()` with a regular expression. Be aware that this can lead to false positives.

= Settings Page =
The settings page for this plugin is at `wp-admin` > `Tools` > `WP Plugin Parser`.

== Installation ==
Download or clone this repository in your `plugins` folder and install the dependencies (WP Parser). 

<pre><code>
# Go to the plugin folder
cd wp-plugin-parser

# Install dependencies
composer install
</code></pre>

