<?php

namespace Drupal\status_feed\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\status_feed\DataCollector;
use Drupal\status_feed\FeedBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Building monitor feed.
 *
 * @package Drupal\status_feed\Controller
 */
class FeedController extends ControllerBase {

  /**
   * Feed builder.
   *
   * @var \Drupal\status_feed\FeedBuilder
   */
  protected $feedBuilder;

  /**
   * Data collector.
   *
   * @var \Drupal\status_feed\DataCollector
   */
  protected $dataCollector;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * FeedController constructor.
   *
   * @param \Drupal\status_feed\FeedBuilder $feed_builder
   *   Feed builder.
   * @param \Drupal\status_feed\DataCollector $data_collector
   *   Data collector.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(FeedBuilder $feed_builder, DataCollector $data_collector, CacheBackendInterface $cache) {
    $this->feedBuilder = $feed_builder;
    $this->dataCollector = $data_collector;
    $this->cache = $cache;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\status_feed\FeedBuilder $feed_builder */
    $feed_builder = $container->get('status_feed.feed_builder');
    /** @var \Drupal\status_feed\DataCollector $data_collector */
    $data_collector = $container->get('status_feed.data_collector');
    /** @var \Drupal\Core\Cache\CacheBackendInterface $cache */
    $cache = $container->get('cache.default');
    return new static($feed_builder, $data_collector, $cache);
  }

  /**
   * Build monitor feed.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return feed as json response.
   */
  public function build() {
    $cache = $this->cache->get('status_feed_feed');

    // Return cached data, if found.
    if (!empty($cache->data)) {
      return $cache->data;
    }

    // Generate the feed.
    $feed = [
      'system' => 'drupal',
      'core' => $this->feedBuilder->getCoreData(),
      'modules' => $this->feedBuilder->getModuleData(),
      'created' => time(),
      'status' => $this->feedBuilder->getStatusData(),
    ];

    // Return the it as a json response.
    $response = new JsonResponse($feed);

    // Cache the response.
    $this->dataCollector->setCache('status_feed_feed', $response, [
      'status_feed_feed',
      'status_feed_core_status',
      'status_feed_modules_status',
      'status_feed_status_data',
    ]);

    return $response;
  }

}
