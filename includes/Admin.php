<?php
namespace Top_Sites_Plugin;

if (!defined('ABSPATH')) {
  exit;
}

require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Views.php';

class Admin {

  public static function init() {
    $self = new self();
    add_action('admin_menu', [$self, 'register_menus']);
    add_action('admin_init', [$self, 'register_settings']);
    add_action('admin_enqueue_scripts', [$self, 'enqueue_admin_assets']);
  }

  public function register_menus() {
    add_menu_page(
      'Top Sites',
      'Top Sites',
      'manage_options',
      'top-sites',
      [Views::class, 'main_page'],
      'dashicons-admin-site',
      2
    );

    add_submenu_page(
      'top-sites',
      'Settings',
      'Settings',
      'manage_options',
      'top-sites-settings',
      [Views::class, 'settings_page']
    );
  }

  public function register_settings() {
    register_setting('top_sites_settings', 'openpagerank_api_key');
  }

  public function enqueue_admin_assets($hook) {
    $allowed_hooks = [
      'toplevel_page_top-sites',
      'top-sites_page_top-sites-settings'
    ];

    if (in_array($hook, $allowed_hooks, true)) {
      wp_enqueue_style('tsp-admin-styles', TSP_PLUGIN_URL . 'assets/style.css', [], '1.0.1');
      wp_enqueue_script('tsp-admin-scripts', TSP_PLUGIN_URL . 'assets/script.js', ['jquery'], '1.0.1', true);
    }
  }

  public static function activate() {
    // Activation logic here
  }

  public static function deactivate() {
    // Deactivation logic here
  }
}