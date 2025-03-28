<?php

namespace Top_Sites_Plugin\TopSites;

if (!defined('ABSPATH')) {
  exit;
}

class PageRankService
{

  /**
   * Fetch ranking data for a list of domains.
   *
   * @param array $domains List of domains.
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
   * Update site rankings by fetching new data from the API.
   *
   * @param array $sites List of sites data.
   * @return array Updated sites data.
   */
  public function updateSitesRanked(array $sites): array
  {
    // Extract domains from sites
    $domains = array_map(function ($site) {
      return $site['rootDomain'] ?? '';
    }, $sites);

    // Split domains into chunks of 100.
    $domain_chunks = array_chunk($domains, 100);
    $rank_data = [];

    foreach ($domain_chunks as $chunk) {
      $chunk_rank_data = $this->getRanksForDomains($chunk);
      $rank_data = array_merge($rank_data, $chunk_rank_data);
      sleep(1); // Respect API rate limits.
    }

    // Update each site with new rank information.
    foreach ($sites as &$site) {
      $domainKey = $site['rootDomain'] ?? '';
      if (isset($rank_data[$domainKey])) {
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

    return $sites;
  }
}
