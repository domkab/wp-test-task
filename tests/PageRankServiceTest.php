<?php

use PHPUnit\Framework\TestCase;
use Top_Sites_Plugin\TopSites\PageRankService;
use Top_Sites_Plugin\TopSites\TopSitesRepo;

if (!class_exists('WP_Error')) {
  class WP_Error
  {
    public function __construct($code = '', $message = '', $data = '') {}
  }
}

if (!class_exists('wpdb')) {
  class wpdb
  {
    public $prefix = 'wp_';

    public function get_results($query, $output = OBJECT)
    {
      return [];
    }
    public function update($table, $data, $where, $format = null, $where_format = null)
    {
      return true;
    }
    public function insert($table, $data, $format = null)
    {
      return true;
    }
    public function prepare($query, ...$args)
    {
      return $query;
    }
    public function esc_like($text)
    {
      return $text;
    }
  }
}

class PageRankServiceTest extends TestCase
{
  public function setUp(): void
  {
    WP_Mock::setUp();

    global $wpdb;
    $wpdb = new wpdb();

    WP_Mock::userFunction('is_wp_error', ['return' => false]);
    WP_Mock::userFunction('get_option', [
      'args' => ['openpagerank_api_key'],
      'return' => 'fake-api-key',
    ]);
    WP_Mock::userFunction('wp_remote_retrieve_body', [
      'return' => '{}',
    ]);
  }

  public function tearDown(): void
  {
    WP_Mock::tearDown();

    global $wpdb;
    $wpdb = null;
  }

  public function testUpdateSitesRankedReturnsCachedData()
  {
    $cachedData = [
      ['id' => 1, 'domain_name' => 'pinterest.com', 'page_rank' => 8.72],
      ['id' => 2, 'domain_name' => 'bit.ly', 'page_rank' => 8.70],
      ['id' => 3, 'domain_name' => 'github.com', 'page_rank' => 8.64],
    ];

    WP_Mock::userFunction('get_transient', [
      'args'   => ['top_sites_ranked'],
      'return' => $cachedData,
    ]);

    $service = new PageRankService();
    $result = $service->updateSitesRanked();

    $this->assertEquals($cachedData, $result);
  }

  public function testFetchFreshDataWhenCacheIsEmpty()
  {
    WP_Mock::userFunction('get_transient', [
      'args' => ['top_sites_ranked'],
      'return' => false,
    ]);

    WP_Mock::userFunction('get_option', [
      'args' => ['openpagerank_api_key'],
      'return' => 'fake-api-key',
    ]);

    $mockRepo = $this->createMock(TopSitesRepo::class);
    $mockRepo->method('getAllSitesNew')->willReturn([
      ['id' => 1, 'domain_name' => 'pinterest.com', 'page_rank' => 8.72],
    ]);

    $service = $this->getMockBuilder(PageRankService::class)
      ->setConstructorArgs([$mockRepo])
      ->onlyMethods(['getRanksForDomains'])
      ->getMock();

    $service->method('getRanksForDomains')
      ->willReturn(['pinterest.com' => ['page_rank_decimal' => '8.72']]);

    WP_Mock::userFunction('set_transient')->once()->with(
      'top_sites_ranked',
      WP_Mock\Functions::type('array'),
      300
    );

    $result = $service->updateSitesRanked();

    $this->assertIsArray($result);
    $this->assertEquals(8.72, $result[0]['page_rank']);
  }

  public function testForceApiUpdateEvenIfCacheExists()
  {
    WP_Mock::userFunction('get_option', [
      'args' => ['openpagerank_api_key'],
      'return' => 'fake-api-key',
    ]);

    $mockRepo = $this->createMock(TopSitesRepo::class);
    $mockRepo->method('getAllSitesNew')->willReturn([
      ['id' => 1, 'domain_name' => 'bit.ly', 'page_rank' => 8.70],
    ]);

    $service = $this->getMockBuilder(PageRankService::class)
      ->setConstructorArgs([$mockRepo])
      ->onlyMethods(['getRanksForDomains'])
      ->getMock();

    $service->method('getRanksForDomains')
      ->willReturn(['bit.ly' => ['page_rank_decimal' => '8.70']]);

    WP_Mock::userFunction('set_transient')->once()->with(
      'top_sites_ranked',
      WP_Mock\Functions::type('array'),
      300
    );

    $result = $service->updateSitesRanked(true);

    $this->assertIsArray($result);
    $this->assertEquals(8.70, $result[0]['page_rank']);
  }

  public function testShouldHandleApiErrorGracefully()
  {
    WP_Mock::userFunction('get_option', [
      'args' => ['openpagerank_api_key'],
      'return' => 'fake-api-key',
    ]);

    WP_Mock::userFunction('wp_remote_get', [
      'return' => new WP_Error('api_error', 'API is down'),
    ]);

    WP_Mock::userFunction('is_wp_error', [
      'return' => true,
    ]);

    $service = new PageRankService();
    $result = $service->getRanksForDomains(['brokenapi.com']);

    $this->assertEquals([], $result);
  }

  public function testShouldHandleJsonDecodeErrorGracefully()
  {
    WP_Mock::userFunction('get_option', [
      'args' => ['openpagerank_api_key'],
      'return' => 'fake-api-key',
    ]);

    WP_Mock::userFunction('wp_remote_get', [
      'return' => ['body' => 'invalid_json'],
    ]);

    WP_Mock::userFunction('wp_remote_retrieve_body', [
      'return' => 'invalid_json',
    ]);

    $service = new PageRankService();
    $result = $service->getRanksForDomains(['invalidjson.com']);

    $this->assertEquals([], $result);
  }
}
