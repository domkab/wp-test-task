<?php

namespace Top_Sites_Plugin;

if (!defined('ABSPATH')) {
  exit;
}

class Helpers
{

  /**
   * Retrieves site data from the original JSON file.
   *
   * @return array Array of sites data.
   */
  public static function get_sites_data(): array
  {
    $json_path = TSP_PLUGIN_DIR . 'data/top-sites.json';
    if (!file_exists($json_path)) {
      error_log(sprintf('JSON file not found at: %s', $json_path));
      return [];
    }

    $json_data = file_get_contents($json_path);
    $data_array = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('JSON decode error in get_sites_data: ' . json_last_error_msg());
      return [];
    }

    $first_five_items = array_slice($data_array, 0, 5);
    error_log('First five items: ' . json_encode($first_five_items));

    return $data_array;
  }

  public static function get_ranks_for_domains(array $domains): array
  {
    $api_url = 'https://openpagerank.com/api/v1.0/getPageRank';
    $query   = http_build_query(['domains' => $domains]);
    $url     = $api_url . '?' . $query;

    error_log('OpenPageRank API URL: ' . $url);

    $apiKey = get_option('openpagerank_api_key');
    $args   = [
      'headers' => [
        'API-OPR' => $apiKey,
      ],
      'timeout' => 10,
    ];

    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
      error_log('OpenPageRank API error: ' . $response->get_error_message());
      return [];
    }

    $body = wp_remote_retrieve_body($response);
    error_log('OpenPageRank API response body: ' . $body);

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('JSON decode error in get_ranks_for_domains: ' . json_last_error_msg());
    }

    $result = [];
    if (isset($data['response']) && is_array($data['response'])) {
      foreach ($data['response'] as $item) {
        if (isset($item['domain'])) {
          $result[$item['domain']] = $item;
        }
      }
    } else {
      error_log('Unexpected API response structure: ' . print_r($data, true));
    }

    return $result;
  }

  /**
   * Updates the ranking for all sites by splitting domains into batches of 100,
   * retrieving their correct rank, sorting the list, and then persisting the updated data.
   *
   * @return array Updated and sorted array of sites.
   */
  public static function update_sites_ranked(): array
  {
    $sites = self::get_sites_data();
    if (empty($sites)) {
      return [];
    }

    // Extract domains from the sites.
    $domains = [];
    foreach ($sites as $site) {
      if (isset($site['rootDomain'])) {
        $domains[] = $site['rootDomain'];
      }
    }

    // Split domains into chunks of 100.
    $domain_chunks = array_chunk($domains, 100);
    $rank_data = [];

    foreach ($domain_chunks as $chunk) {
      $chunk_rank_data = self::get_ranks_for_domains($chunk);
      $rank_data = array_merge($rank_data, $chunk_rank_data);
      // Optionally, add a small delay if the API has rate limits.
      sleep(1);
    }

    // Update each site with new rank information.
    foreach ($sites as &$site) {
      $domainKey = $site['rootDomain'] ?? '';
      if (isset($rank_data[$domainKey])) {
        // Use the 'page_rank_decimal' field from the API response.
        $site['rank'] = floatval($rank_data[$domainKey]['page_rank_decimal']);
      } else {
        $site['rank'] = 0;
      }
    }
    unset($site);

    // Sort sites by descending rank.
    usort($sites, function ($a, $b) {
      return $b['rank'] <=> $a['rank'];
    });

    // Persist the updated sites list to a new JSON file.
    $updated_path = TSP_PLUGIN_DIR . 'data/top-sites-updated.json';
    file_put_contents($updated_path, json_encode($sites));

    return $sites;
  }

  public static function get_sites_ranked(): array
  {
    $updated_path = TSP_PLUGIN_DIR . 'data/top-sites-updated.json';
    if (file_exists($updated_path)) {
      $json_data = file_get_contents($updated_path);
      $sites = json_decode($json_data, true);
      if (json_last_error() === JSON_ERROR_NONE && !empty($sites)) {
        return $sites;
      }
    }

    return self::update_sites_ranked();
  }

  /**
   * Paginates an array of items.
   *
   * @param array $items The full list of items.
   * @param int $current_page Current page number.
   * @param int $per_page Number of items per page.
   * @return array Contains 'data' for the current page and 'links' for pagination.
   */
  public static function paginate(array $items, int $current_page, int $per_page): array
  {
    $total = count($items);
    $total_pages = (int)ceil($total / $per_page);
    $offset = ($current_page - 1) * $per_page;

    $data = array_slice($items, $offset, $per_page);
    $links = paginate_links([
      'base'      => add_query_arg('paged', '%#%'),
      'format'    => '',
      'prev_text' => '&laquo;',
      'next_text' => '&raquo;',
      'total'     => $total_pages,
      'current'   => $current_page,
      'type'      => 'list',
    ]);

    return compact('data', 'links');
  }

  public static function render_notice(string $message, string $type = 'success'): void
  {
?>
    <div class="notice notice-<?= esc_attr($type); ?>">
      <p><?= esc_html($message); ?></p>
    </div>
<?php
  }
}
