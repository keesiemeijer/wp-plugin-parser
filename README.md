# WP Plugin Parser

Version: 1.0.0  
Requires at least: 4.0  
Tested up to: 4.9  
Parsed JSON: 4.9

This plugin parses WordPress plugins to see which (PHP, WP, plugin) functions and classes they use. The [WP Parser](https://github.com/WordPress/phpdoc-parser) plugin is used to parse the plugins.

After parsing the minimum `Requires at least` version is calculated. Warnings are displayed if `deprecated` WordPress functions or classes are used by a plugin. Blacklisted functions, you can set in the settings page, also generate warnings if found.

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

**Note**: Be aware that PHP language constructs (like `eval` or `isset`) are not (yet) picked up by the parser (or this plugin).

## Settings Page.
The settings page for this plugin is at `wp-admin` > `Tools` > `WP Plugin Parser`.

![settings-page](https://user-images.githubusercontent.com/1436618/33182491-838b4f00-d074-11e7-8e5e-d5227ef59cc6.png)