<?php

namespace Top_Sites_Plugin;

if (!defined('ABSPATH')) {
  exit;
}

class Helpers
{

  public static function get_sites_data()
  {
    $json_path = TSP_PLUGIN_DIR . 'data/top-sites.json';
    if (!file_exists($json_path)) return [];

    $json_data = file_get_contents($json_path);
    return json_decode($json_data, true) ?: [];
  }


  public static function get_ranks_for_domains(array $domains)
  {
    $api_url = 'https://openpagerank.com/api/v1.0/getPageRank';
    $query  = http_build_query(['domains' => $domains]);
    $url    = $api_url . '?' . $query;

    $args = [
      'headers' => [
        'API-OPR' => 'api',
      ],
      'timeout' => 10,
    ];

    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
      // logerror.
      return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $result = [];
    if (isset($data['response']) && is_array($data['response'])) {
      foreach ($data['response'] as $item) {
        if (isset($item['domain'])) {
          $result[$item['domain']] = $item;
        }
      }
    }

    return $result;
  }

  public static function paginate($items, $current_page, $per_page)
  {
    $total = count($items);
    $total_pages = ceil($total / $per_page);
    $offset = ($current_page - 1) * $per_page;

    $data = array_slice($items, $offset, $per_page);
    $links = paginate_links([
      'base' => add_query_arg('paged', '%#%'),
      'format' => '',
      'prev_text' => '&laquo;',
      'next_text' => '&raquo;',
      'total' => $total_pages,
      'current' => $current_page,
      'type' => 'list'
    ]);

    return compact('data', 'links');
  }

  public static function render_notice($message, $type = 'success')
  {
?>
    <div class="notice notice-<?= esc_attr($type); ?>">
      <p><?= esc_html($message); ?></p>
    </div>
<?php
  }
}
