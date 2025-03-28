<?php

namespace Top_Sites_Plugin\DB;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * Migrates data from a remote JSON file (from GitHub) into the raw table.
 *
 * This method retrieves the JSON from a remote URL and inserts each record
 * into the 'top_sites_raw' table using the JSON field 'rank' as id and 'rootDomain' as domain_name.
 *
 * @return void
 */
class Migrator
{
  public static function migrateFromJson(): void
  {
    global $wpdb;

    $json_url = 'https://raw.githubusercontent.com/Kikobeats/top-sites/master/top-sites.json';

    $response = wp_remote_get($json_url, ['timeout' => 15]);
    if (is_wp_error($response)) {
      error_log(
        'Error fetching JSON from GitHub: ' . $response->get_error_message()
      );

      return;
    }

    $json_data = wp_remote_retrieve_body($response);
    if (empty($json_data)) {
      error_log('Empty JSON data retrieved from ' . $json_url);

      return;
    }

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
