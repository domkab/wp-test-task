<?php

namespace Top_Sites_Plugin\TopSites;

if (!defined('ABSPATH')) {
  exit;
}

class TopSitesRepo
{

  private $jsonPath;
  private $updatedPath;

  public function __construct()
  {
    $this->jsonPath    = TSP_PLUGIN_DIR . 'data/top-sites.json';
    $this->updatedPath = TSP_PLUGIN_DIR . 'data/top-sites-updated.json';
  }

  public function getSitesData(): array
  {
    if (!file_exists($this->jsonPath)) {
      error_log(sprintf('JSON file not found at: %s', $this->jsonPath));
      return [];
    }

    $jsonData = file_get_contents($this->jsonPath);
    $dataArray = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('JSON decode error in getSitesData: ' . json_last_error_msg());
      return [];
    }

    return $dataArray;
  }

  public function persistUpdatedSites(array $sites): void
  {
    file_put_contents($this->updatedPath, json_encode($sites));
  }

  public function getUpdatedSites(): array
  {
    if (file_exists($this->updatedPath)) {
      $jsonData = file_get_contents($this->updatedPath);
      $sites = json_decode($jsonData, true);
      if (json_last_error() === JSON_ERROR_NONE && !empty($sites)) {
        return $sites;
      }
    }
    return [];
  }
}
