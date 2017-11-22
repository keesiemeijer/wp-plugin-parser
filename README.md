# WP Plugin Parser

Version: 1.0.0  
Requires at least: 4.0  
Tested up to: 4.9  
Parsed JSON: 4.9

This plugin parses WordPress plugins to see which (PHP, WP, plugin) functions and classes they use. The [WP Parser](https://github.com/WordPress/phpdoc-parser) plugin is used to parse the plugins. After parsing the results are displayed and the minimum `Requires at least` version is calculated. It also displays a notice if deprecated functions or classes are used by the plugin.

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

## Settings Page.
The settings page for this plugin is at `wp-admin` > `Tools` > `WP Plugin Parser`.

![settings-page](https://user-images.githubusercontent.com/1436618/33147251-63f83566-cfc7-11e7-9c34-548be8871bed.png)