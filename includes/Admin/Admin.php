<?php

namespace Top_Sites_Plugin\Admin;

if (!defined('ABSPATH')) {
  exit;
}

use Top_Sites_Plugin\TopSites\TopSitesRepo;
use Top_Sites_Plugin\TopSites\AjaxHandlers;
use Top_Sites_Plugin\Admin\AdminView;

class Admin
{

  public static function init()
  {
    $self = new self();
    add_action('admin_menu', [$self, 'register_menus']);
    add_action('admin_init', [$self, 'register_settings']);
    add_action('admin_enqueue_scripts', [$self, 'enqueue_admin_assets']);
    add_action('wp_ajax_search_top_sites', [AjaxHandlers::class, 'searchTopSites']);
  }

  public function register_menus()
  {
    add_menu_page(
      'Top Sites',
      'Top Sites',
      'manage_options',
      'top-sites',
      [AdminView::class, 'mainPage'],
      'dashicons-admin-site',
      2
    );

    add_submenu_page(
      'top-sites',
      'Settings',
      'Settings',
      'manage_options',
      'top-sites-settings',
      [AdminView::class, 'settingsPage']
    );
  }

  public function register_settings()
  {
    register_setting('top_sites_settings', 'openpagerank_api_key');
  }

  public function enqueue_admin_assets($hook)
  {
    $allowed_hooks = [
      'toplevel_page_top-sites',
      'top-sites_page_top-sites-settings'
    ];

    if (in_array($hook, $allowed_hooks, true)) {
      wp_enqueue_style('tsp-admin-styles', TSP_PLUGIN_URL . 'assets/style.css', [], '1.0.1');
      wp_enqueue_script('tsp-admin-script', TSP_PLUGIN_URL . 'assets/script.js', ['jquery'], '1.0.1', true);
    }
  }

  public static function activate() {}

  public static function deactivate() {}
}
