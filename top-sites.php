<?php

/**
 * Plugin Name: Top Sites Plugin
 * Plugin URI: https://example.com/top-sites-plugin
 * Description: CC test task
 * Version: 1.0.1
 * Author: Dominykas Bobinas
 * Author URI: https://github.com/domkab
 * License: GPL2
 * Text Domain: top-sites-wp
 */

if (!defined('ABSPATH')) {
  exit;
}

require_once __DIR__ . '/vendor/autoload.php';

define('TSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSP_PLUGIN_URL', plugin_dir_url(__FILE__));

Top_Sites_Plugin\Admin\Admin::init();

register_activation_hook(__FILE__, ['Top_Sites_Plugin\Admin', 'activate']);
register_deactivation_hook(__FILE__, ['Top_Sites_Plugin\Admin', 'deactivate']);
