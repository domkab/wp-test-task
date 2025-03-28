<?php

namespace Top_Sites_Plugin\DB;

if (!defined('ABSPATH')) {
  exit;
}

  /**
   * Migrates data from the JSON file into the raw table in the database.
   *
   * This method reads the JSON file located at TSP_PLUGIN_DIR . 'data/top-sites.json'
   * and inserts each record into the 'top_sites_raw' table using the JSON field 'rank' as id
   * and 'rootDomain' as domain_name.
   *
   * @return void
   */

class Migrator
{
  public static function migrateFromJson(): void
  {
    global $wpdb;
    $json_path = TSP_PLUGIN_DIR . 'data/top-sites.json';

    if (!file_exists($json_path)) {
      error_log(sprintf('JSON file not found at: %s', $json_path));

      return;
    }

    $json_data = file_get_contents($json_path);
    $data_array = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('JSON decode error in migrateFromJson: ' . json_last_error_msg());
      
      return;
    }

    $table_name = $wpdb->prefix . 'top_sites_raw';
    foreach ($data_array as $site) {
      $wpdb->replace(
        $table_name,
        [
          'id'          => intval($site['rank']),
          'domain_name' => $site['rootDomain'],
        ],
        [
          '%d',
          '%s'
        ]
      );
    }
  }
}
