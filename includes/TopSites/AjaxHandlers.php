<?php

namespace Top_Sites_Plugin\TopSites;

if (! defined('ABSPATH')) {
  exit;
}

class AjaxHandlers
{

  public static function searchTopSites()
  {
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

    if ($search_query === "") {
      $repo = new TopSitesRepo();
      $service = new PageRankService();
      $results = $service->updateSitesRanked();

      if (empty($results)) {
        wp_send_json_error('No sites available.');
      }

      wp_send_json_success($results);
    }

    $repo = new TopSitesRepo();
    $results = $repo->searchSites($search_query);

    if (empty($results)) {
      wp_send_json_error('No matching sites found.');
    }
    wp_send_json_success($results);
  }
}
