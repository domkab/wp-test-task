<?php

namespace Top_Sites_Plugin\DB;

if (!defined('ABSPATH')) {
  exit;
}

use Top_Sites_Plugin\DB\Migrator;

class Installer
{
  public static function install()
  {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $raw_table = $wpdb->prefix . 'top_sites_raw';

    if ($wpdb->get_var("SHOW TABLES LIKE '$raw_table'") != $raw_table) {
      $sql_raw = "CREATE TABLE $raw_table (
                id INT NOT NULL,
                domain_name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql_raw);

      Migrator::migrateFromJson();
    }

    $new_table = $wpdb->prefix . 'top_sites_new';

    $new_table = $wpdb->prefix . 'top_sites_new';
    if ($wpdb->get_var("SHOW TABLES LIKE '$new_table'") != $new_table) {
      $sql_new = "CREATE TABLE $new_table (
                id INT NOT NULL,
                domain_name VARCHAR(255) NOT NULL,
                page_rank DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                PRIMARY KEY (id)
            ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql_new);
    }
  }
}
