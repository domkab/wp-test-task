<?php

namespace Top_Sites_Plugin\TopSites;

if (!defined('ABSPATH')) {
  exit;
}

class TopSitesRepo
{
  public static function newTableName(): string
  {
    global $wpdb;
    return $wpdb->prefix . 'top_sites_new';
  }

  public static function rawTableName(): string
  {
    global $wpdb;

    return $wpdb->prefix . 'top_sites_raw';
  }

  /**
   * Retrieves all site records from the raw table.
   *
   * @return array Array of sites, each containing 'id' and 'domain_name'.
   */
  public function getAllSitesRaw(): array
  {
    global $wpdb;
    $table_name = self::rawTableName();
    $query = "SELECT * FROM $table_name ORDER BY id ASC";
    $results = $wpdb->get_results($query, ARRAY_A);

    return is_array($results) ? $results : [];
  }

  /**
   * Retrieves all site records from the new table.
   *
   * @return array Array of sites, each containing 'id', 'domain_name', and 'page_rank'.
   */
  public function getAllSitesNew(): array
  {
    global $wpdb;
    $table_name = self::newTableName();
    $query = "SELECT * FROM $table_name ORDER BY id ASC";
    $results = $wpdb->get_results($query, ARRAY_A);

    return is_array($results) ? $results : [];
  }

  /**
   * Updates a site's record in the new table with a new page rank.
   *
   * @param int    $oldId       The site's original ID.
   * @param int    $newRank     The new rank value.
   * @param string $domain_name The domain name.
   * @return bool True on success, false on failure.
   */
  public static function updateSiteNew(int $id, float $newRank, string $domain_name): bool
  {
    global $wpdb;
    $table_name = self::newTableName();

    $updated = $wpdb->update(
      $table_name,
      [
        'page_rank'   => $newRank,
        'domain_name' => $domain_name,
      ],
      ['id' => $id],
      [
        '%f',
        '%s'
      ],
      ['%d']
    );
    return $updated !== false;
  }

  /**
   * Inserts a new site record into the new table.
   *
   * @param int    $id          The site's ID.
   * @param string $domain_name The domain name.
   * @param float  $page_rank   The page rank value.
   * @return bool True on success, false on failure.
   */
  public static function insertSiteNew(int $id, string $domain_name, float $page_rank): bool
  {
    global $wpdb;
    $table_name = self::newTableName();
    $inserted = $wpdb->insert(
      $table_name,
      [
        'id' => $id,
        'domain_name' => $domain_name,
        'page_rank' => $page_rank,
      ],
      [
        '%d',
        '%s',
        '%f'
      ]
    );
    return $inserted !== false;
  }

  public function searchSites(string $query): array
  {
    global $wpdb;
    $table_name = self::newTableName();
    $sql = $wpdb->prepare(
      "SELECT * FROM $table_name WHERE domain_name LIKE %s ORDER BY page_rank DESC",
      '%' . $wpdb->esc_like($query) . '%'
    );

    $results = $wpdb->get_results($sql, ARRAY_A);

    error_log('logging the res... ' . print_r($results, true));

    return is_array($results) ? $results : [];
  }
}
