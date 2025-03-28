<?php

namespace Top_Sites_Plugin\Admin;

if (! defined('ABSPATH')) {
  exit;
}

use Top_Sites_Plugin\TopSites\PageRankService;
use Top_Sites_Plugin\Utils\Paginator;

class AdminView
{
  public static function mainPage()
  {
    $apiKey = get_option('openpagerank_api_key');
    $currentPage = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
    $perPage = 100;

    $service = new PageRankService();
    $sitesData = $service->updateSitesRanked();

    $sitesData = $currentPage === 1
      ? $service->updateSitesRanked(true)
      : $service->updateSitesRanked();

    if (empty($sitesData)) {
      self::renderNotice('No data available.', 'error');
      return;
    }

    $pagination = Paginator::paginate($sitesData, $currentPage, $perPage);
?>
    <div class="wrap">
      <h1>Top Sites</h1>
      <?php if (empty($apiKey)) : ?>
        <?php self::renderNotice('Warning: No OpenPageRank API key set.', 'error'); ?>
      <?php endif; ?>

      <div class="search__form" id="search-wrapper">
        <label for="domain-search">Search Domain:</label>
        <input type="text" id="domain-search" placeholder="Type a domain name..." />
      </div>

      <table id="sites-table" class="top-sites__table widefat fixed striped">
        <thead>
          <tr>
            <th class="top-sites__table--rank">Page Rank</th>
            <th>Domain</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagination['data'] as $site) : ?>
            <tr>
              <td><?= esc_html($site['page_rank']); ?></td>
              <td class="top-sites__table--domain"><?= esc_html($site['domain_name']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="tablenav bottom">
        <div class="tablenav-pages">
          <?= $pagination['links']; ?>
        </div>
      </div>
    </div>
  <?php
  }

  public static function settingsPage()
  {
  ?>
    <div class="wrap">
      <h1>Top Sites Settings</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('top_sites_settings');
        do_settings_sections('top_sites_settings');
        ?>
        <table class="form-table">
          <tr valign="top">
            <th>OpenPageRank API Key</th>
            <td>
              <input
                type="text"
                name="openpagerank_api_key"
                value="<?= esc_attr(get_option('openpagerank_api_key')); ?>"
                size="50" />
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
  }

  private static function renderNotice(string $message, string $type = 'success'): void
  {
  ?>
    <div class="notice notice-<?= esc_attr($type); ?>">
      <p><?= esc_html($message); ?></p>
    </div>
<?php
  }
}
