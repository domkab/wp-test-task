<?php

namespace Top_Sites_Plugin\Utils;

if (!defined('ABSPATH')) {
  exit;
}

class Paginator
{

  /**
   * Paginate an array of items.
   *
   * @param array $items
   * @param int $current_page
   * @param int $per_page
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
}
