<?php

/**
 * Plugin Name: Top Sites Plugin
 * Plugin URI: https://example.com/top-sites-plugin
 * Description: CC test task
 * Version: 1.0.0
 * Author: Dominykas Bobinas
 * Author URI: https://github.com/domkab
 * License: GPL2
 * Text Domain: top-sites-wp
 */

if (! defined('ABSPATH')) {
  exit;
}

define('TSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TSP_PLUGIN_URL', plugin_dir_url(__FILE__));

class Top_Sites_Plugin
{
  private static $instance;

  public static function instance()
  {
    if (! isset(self::$instance) && ! (self::$instance instanceof Top_Sites_Plugin)) {
      self::$instance = new self();
      self::$instance->setup_hooks();
    }
    return self::$instance;
  }

  private function __construct() {}

  private function setup_hooks()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('admin_menu', array($this, 'register_admin_pages'));
    add_action('admin_init', function () {
      register_setting('top_sites_settings', 'openpagerank_api_key');
    });
  }

  public function enqueue_scripts()
  {
    wp_enqueue_style('tsp-styles', TSP_PLUGIN_URL . 'assets/style.css', array(), '1.0.0');
    wp_enqueue_script('tsp-scripts', TSP_PLUGIN_URL . 'assets/script.js', array('jquery'), '1.0.0', true);
  }

  public function enqueue_admin_scripts($hook)
  {
    $allowed_hooks = array(
      'toplevel_page_top-sites',
      'top-sites_page_top-sites-settings'
    );

    if (! in_array($hook, $allowed_hooks, true)) {
      return;
    }

    wp_enqueue_style('tsp-admin-styles', TSP_PLUGIN_URL . 'assets/style.css', array(), '1.0.0');
  }

  public function register_admin_pages()
  {
    add_menu_page(
      'Top Sites',
      'Top Sites',
      'manage_options',
      'top-sites',
      array($this, 'render_main_page'),
      'dashicons-admin-site',
      2
    );

    add_submenu_page(
      'top-sites',
      'Settings',
      'Settings',
      'manage_options',
      'top-sites-settings',
      array($this, 'render_settings_page')
    );
  }

  public function render_main_page()
  {
    $apiKey = get_option('openpagerank_api_key');
    $current_page = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
    $per_page = 100;

    $json_path = TSP_PLUGIN_DIR . 'data/top-sites.json';

    if (!file_exists($json_path)) {
      echo '<div class="notice notice-error"><p>Data file not found.</p></div>';

      return;
    }

    $json_data = file_get_contents($json_path);
    $sites_data = json_decode($json_data, true);

    if (empty($sites_data)) {
      echo '<div class="notice notice-error"><p>No data available.</p></div>';

      return;
    }

    $total_sites = count($sites_data);
    $total_pages = ceil($total_sites / $per_page);
    $offset = ($current_page - 1) * $per_page;

    $sites_for_page = array_slice($sites_data, $offset, $per_page);
?>
    <div class="wrap">
      <h1>Top Sites</h1>
      <?php if (empty($apiKey)) : ?>
        <div class="notice notice-error">
          <p>
            <?php esc_html_e(
              'Warning: No OpenPageRank API key is set. The plugin will not work without it. Please set it in the Settings submenu.',
              'top-sites-wp'
            ); ?>
          </p>
        </div>
      <?php endif; ?>

      <table class="top-sites__table widefat fixed striped">
        <thead>
          <tr>
            <th class="top_sites__table--rank">Rank</th>
            <th>Domain</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sites_for_page as $site) : ?>
            <tr>
              <td><?php echo esc_html($site['rank']); ?></td>
              <td><?php echo esc_html($site['rootDomain']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="tablenav bottom">
        <div class="tablenav-pages">
          <?php
          echo paginate_links(array(
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;', 'top-sites-wp'),
            'next_text' => __('&raquo;', 'top-sites-wp'),
            'total' => $total_pages,
            'current' => $current_page,
            'type' => 'list'
          ));
          ?>
        </div>
      </div>
    </div>
  <?php
  }

  public function render_settings_page()
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
            <th scope="row"><?php esc_html_e('OpenPageRank API Key', 'top-sites-wp'); ?></th>
            <td>
              <input
                type="text"
                name="openpagerank_api_key"
                value="<?php echo esc_attr(get_option('openpagerank_api_key')); ?>"
                size="50" />
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
<?php
  }

  public static function activate() {}

  public static function deactivate() {}
}

register_activation_hook(__FILE__, array('Top_Sites_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Top_Sites_Plugin', 'deactivate'));

Top_Sites_Plugin::instance();
