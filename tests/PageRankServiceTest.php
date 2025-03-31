<?php

use PHPUnit\Framework\TestCase;
use Top_Sites_Plugin\TopSites\PageRankService;
use Top_Sites_Plugin\TopSites\TopSitesRepo;
use WP_Mock;

class PageRankServiceTest extends TestCase
{
  public function setUp(): void
  {
    WP_Mock::setUp();
  }

  public function tearDown(): void
  {
    WP_Mock::tearDown();
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
}
