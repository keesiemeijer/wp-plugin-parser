# WP Plugin Parser

Version: 1.0.0  
Requires at least: 4.0  
Tested up to: 4.9  
Parsed JSON: 4.9

This WordPress plugin lets you see what functions, classes and methods are used by other plugins.

Features:
* displays warnings for plugins that use `deprecated` or `blacklisted` functions.
* checks if the plugin is compatible with a specific PHP versions.
* calculates the minimum `Requires at least` version needed for a plugin.

The plugins are parsed with the [WP Parser](https://github.com/WordPress/phpdoc-parser).
The checks for PHP version compatibility is done with [PHPCompatibility](https://github.com/wimg/PHPCompatibility).

To get the relevant WordPress information (since, deprecated etc) this plugin references JSON files (created by the [WP Parser JSON](https://github.com/keesiemeijer/wp-parser-json) plugin).

See the `Parsed JSON` version for what WordPress version was last parsed to create the files.

## Installation
Download or clone this repository in your `plugins` folder and install the dependencies (WP Parser). 

```bash
# Go to the plugin folder
cd wp-plugin-parser

# Install dependencies
composer install
```

## What gets parsed
All plugin files, functions and methods are parsed to retrieve the functions, classes and methods they use. The `since` and `deprecated` attributes are only checked for functions and classes. Meaning, methods are not (yet) flagged when `deprecated`, and are not used for calculating the `Requires at least` version.

**Note**: [PHP language constructs](https://secure.php.net/manual/en/reserved.keywords.php) like `eval()` are not picked up by the parser. This plugin finds the language constructs `die()`, `eval()` and `exit()` with a regular expression. Be aware that this can lead to false positives.

## Settings Page.
The settings page for this plugin is at `wp-admin` > `Tools` > `WP Plugin Parser`.

![settings-page](https://user-images.githubusercontent.com/1436618/37988701-e12e5f96-3201-11e8-9b73-9f4cae281b19.png)