<?php

namespace Top_Sites_Plugin\TopSites;

if (!defined('ABSPATH')) {
  exit;
}

use Top_Sites_Plugin\TopSites\TopSitesRepo;

class PageRankService
{
  /**
   * Fetch ranking data for a list of domains.
   *
   * @param array $domains List of domain names.
   * @return array Associative array of domain => rank data.
   */
  public function getRanksForDomains(array $domains): array
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

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('JSON decode error in getRanksForDomains: ' . json_last_error_msg());
      return [];
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
   * Updates site rankings by:
   * 1. Checking if the new table is empty. If so, populating it with raw data.
   * 2. Optionally forcing an API update (if $forceUpdate is true) even if cached data exists.
   * 3. Otherwise, if cached data exists and $forceUpdate is false, returning the cached data.
   * 4. Fetching new ranking data from the API in batches,
   * 5. Updating the DB records in the top_sites_new table,
   * 6. Caching the updated list for subsequent paginated views,
   * 7. Returning the updated, sorted list of sites.
   *
   * @param bool $forceUpdate If true, bypass the transient cache and update via API.
   * @return array Updated sites data.
   */
  public function updateSitesRanked(bool $forceUpdate = false): array
  {
    if (!$forceUpdate) {
      $cachedData = get_transient('top_sites_ranked');

      if ($cachedData !== false) {
        error_log('Using cached top_sites_ranked data.');
        return $cachedData;
      }
    }

    error_log('No cached data found or force update requested. Updating site rankings via API.');

    $repo = new TopSitesRepo();

    $sites = $repo->getAllSitesNew();

    if (empty($sites)) {
      error_log('New table empty. Populating from raw table.');
      $rawSites = $repo->getAllSitesRaw();

      foreach ($rawSites as $rawSite) {
        TopSitesRepo::insertSiteNew(
          intval($rawSite['id']),
          $rawSite['domain_name'],
          0.000
        );
      }

      $sites = $repo->getAllSitesNew();
    }

    $domains = array_map(function ($site) {
      return $site['domain_name'];
    }, $sites);

    $domain_chunks = array_chunk($domains, 100);
    $rank_data = [];

    foreach ($domain_chunks as $chunk) {
      $chunk_rank_data = $this->getRanksForDomains($chunk);
      $rank_data = array_merge($rank_data, $chunk_rank_data);
    }

    foreach ($sites as $site) {
      $domain = $site['domain_name'];
      $newRank = isset($rank_data[$domain])
        ? floatval($rank_data[$domain]['page_rank_decimal'])
        : 0.000;

      TopSitesRepo::updateSiteNew($site['id'], $newRank, $domain);
      $site['page_rank'] = $newRank;
    }

    $updatedSites = $repo->getAllSitesNew();
    usort($updatedSites, function ($a, $b) {
      return $b['page_rank'] <=> $a['page_rank'];
    });

    set_transient('top_sites_ranked', $updatedSites, 300);
    error_log('Site rankings updated and cached for 5 minutes.');

    return $updatedSites;
  }
}
