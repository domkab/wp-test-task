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
    error_log('OpenPageRank API response body: ' . $body);

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
   * 1. Checking if the new table is empty. If so, populate it with raw data.
   * 2. Fetching new ranking data from the API in batches.
   * 3. Updating the DB records in the top_sites_new table.
   * 4. Returning the updated, sorted list of sites.
   *
   * @return array Updated sites data.
   */
  public function updateSitesRanked(): array
  {
    $repo = new TopSitesRepo();

    $sites = $repo->getAllSitesNew();

    if (empty($sites)) {
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
      $newRank = 0.000;
      if (isset($rank_data[$domain])) {
        $newRank = floatval($rank_data[$domain]['page_rank_decimal']);
      }
      TopSitesRepo::updateSiteNew($site['id'], $newRank, $domain);
    }

    $updatedSites = $repo->getAllSitesNew();

    usort($updatedSites, function ($a, $b) {
      return $b['page_rank'] <=> $a['page_rank'];
    });

    return $updatedSites;
  }
}
