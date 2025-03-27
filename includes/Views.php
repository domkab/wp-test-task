<?php

namespace Top_Sites_Plugin;

if (!defined('ABSPATH')) {
  exit;
}

class Views
{

  public static function main_page()
  {
    $apiKey = get_option('openpagerank_api_key');
    $current_page = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
    $per_page = 100;

    $sites_data = Helpers::get_sites_ranked();

    if (empty($sites_data)) {
      Helpers::render_notice('No data available.', 'error');
      return;
    }

    $pagination = Helpers::paginate($sites_data, $current_page, $per_page);
?>
    <div class="wrap">
      <h1>Top Sites</h1>
      <?php if (empty($apiKey)) : ?>
        <?php Helpers::render_notice('Warning: No OpenPageRank API key set.', 'error'); ?>
      <?php endif; ?>

      <table class="top-sites__table widefat fixed striped">
        <thead>
          <tr>
            <th class="top_sites__table--rank">Rank</th>
            <th>Domain</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pagination['data'] as $site) : ?>
            <tr>
              <td><?= esc_html($site['rank']); ?></td>
              <td class="top-sites__table--domain"><?= esc_html($site['rootDomain']); ?></td>
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

  public static function settings_page()
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
              <input type="text" name="openpagerank_api_key" value="<?= esc_attr(get_option('openpagerank_api_key')); ?>" size="50" />
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
<?php
  }
}
