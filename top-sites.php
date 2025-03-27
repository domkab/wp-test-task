<?php

/**
 * Plugin Name: Top Sites Plugin
 * Plugin URI: https://example.com/top-sites-plugin
 * Description: CC test task
 * Version: 1.0.0
 * Author: Dominykas Bobinas
 * Author URI: https://github.com/domkab
 * License: GPL2
 * Text Domain: top-sites-wp
 */

if (! defined('ABSPATH')) {
  exit;
}

define('TSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSP_PLUGIN_URL', plugin_dir_url(__FILE__));

class Top_Sites_Plugin
{
  private static $instance;

  public static function instance()
  {
    if (! isset(self::$instance) && ! (self::$instance instanceof Top_Sites_Plugin)) {
      self::$instance = new self();
      self::$instance->setup_hooks();
    }
    return self::$instance;
  }

  private function __construct()
  {
    // Any additional initialization code can go here.
  }

  private function setup_hooks()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('admin_menu', array($this, 'register_admin_page'));
  }

  public function enqueue_admin_scripts($hook)
  {
    if ('toplevel_page_top-sites' !== $hook) {
      return;
    }

    wp_enqueue_style('tsp-admin-styles', TSP_PLUGIN_URL . 'assets/style.css', array(), '1.0.0');
  }

  public function register_admin_page()
  {
    add_menu_page(
      'Top Sites',
      'Top Sites',
      'manage_options',
      'top-sites',
      array($this, 'render_admin_page'),
      'dashicons-admin-site',
      2
    );
  }

  public function render_admin_page()
  {
    echo '<div class="wrap"><h1>Top Sites</h1></div>';
  }

  public static function activate()
  {
  }

  public static function deactivate()
  {
  }
}

register_activation_hook(__FILE__, array('Top_Sites_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Top_Sites_Plugin', 'deactivate'));

Top_Sites_Plugin::instance();
