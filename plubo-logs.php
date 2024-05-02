<?php

/**
 * @wordpress-plugin
 * Plugin Name:       PLUBO Logs
 * Plugin URI:        https://sirvelia.com/
 * Description:       Create logs for your plugin easily.
 * Version:           1.0.0
 * Author:            Albert Tarrés - Sirvelia
 * Author URI:        https://sirvelia.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       plubo-logs
 * Domain Path:       /languages
 */

define('PLUBO_LOGS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PLUBO_LOGS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PLUBO_LOGS_ASSETS_URL', PLUBO_LOGS_PLUGIN_URL . 'dist/');

require_once PLUBO_LOGS_PLUGIN_DIR . 'vendor/autoload.php';