<?php

namespace Drupal\status_feed;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\status_feed\Event\MonitorStatusEvent;
use Drupal\status_feed\Event\MonitorStatusEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Data collector service.
 *
 * @package Drupal\status_feed
 */
class DataCollector {

  use LogTrait;
  use TimeLog;
  use StringTranslationTrait;

  /**
   * Request time.
   *
   * @var int
   */
  private static $requestTime;

  /**
   * Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * State.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Cache time.
   *
   * @var float
   */
  static public $cacheTime = 60 * 60;

  /**
   * Activate cron.
   *
   * @var int
   */
  static public $activateCron = 0;

  /**
   * DataCollector constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Connection.
   * @param \Drupal\Core\State\State $state
   *   State.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   */
  public function __construct(Connection $database, State $state, CacheBackendInterface $cache, EventDispatcherInterface $event_dispatcher) {
    $this->database = $database;
    $this->state = $state;
    $this->cache = $cache;
    $this->eventDispatcher = $event_dispatcher;

    self::$requestTime = \Drupal::time()->getRequestTime();

    $settings = \Drupal::config('status_feed.settings');
    if (!empty($settings)) {
      self::$cacheTime = $settings->get('cachetime');
      self::$activateCron = $settings->get('activate_cron');
    }
  }

  /**
   * Execute the date collection.
   *
   * This function i primarily used in the cron call. After the data is
   * collection we invalidate the feed cache.
   *
   * @return bool
   *   TRUE if we can collect data.
   */
  public function execute() {
    try {
      // Import core status.
      $this->collectData('core');

      // Import modules status.
      $this->collectData('modules');

      $this->collectData('status');

      return TRUE;
    }
    catch (\Exception $e) {
      error_log($e->getMessage());
    }
    return FALSE;
  }

  /**
   * Collect data.
   *
   * @param string $type
   *   Type (eg. core, modules or status).
   *
   * @return array
   *   Status.
   */
  public function collectData($type) {
    switch ($type) {
      case 'core':
        $status = $this->cache->get('status_feed_core_status');

        if (empty($status)) {
          $status = $this->getCoreData();
        }
        break;

      case 'modules':
        $status = $this->cache->get('status_feed_modules_status');

        if (empty($status)) {
          $status = $this->getModulesData();
        }
        break;

      case 'status':
        $status = $this->cache->get('status_feed_status_status');

        if (empty($status)) {
          $status = $this->getStatusData();
        }
    }

    $this->state->set('status_feed.collect_' . $type . '_data_last_run', self::$requestTime);
    // CacheBackendInterface::get() returns an envelope, not the cached data.
    return is_object($status) && isset($status->data) ? $status->data : ($status ?? []);
  }

  /**
   * Get core status data.
   *
   * @return array
   *   Core data.
   */
  protected function getCoreData() {
    $response = [
      'current_version' => \Drupal::VERSION,
    ];

    // Invalidate the feed cache.
    Cache::invalidateTags(['status_feed_feed', 'status_feed_core_status']);
    $this->setCache('status_feed_core_status', $response, ['status_feed_feed', 'status_feed_core_status']);

    return $response;
  }

  /**
   * Get modules status data.
   *
   * @return array
   *   Modules data.
   */
  protected function getModulesData() {
    $response = [];

    /** @var \Drupal\Core\Extension\ExtensionList $extension_service */
    $extension_service = \Drupal::service('extension.list.module');
    $extension_list = $extension_service->getAllInstalledInfo();
    foreach ($extension_list as $machine_name => $module) {
      if (strtolower($module['package']) !== 'core') {
        $response[$machine_name] = (object) [
          'name' => $module['name'],
          'package' => $module['package'],
          'type' => ucfirst($module['type']),
          'status' => 'Enabled',
          'version' => $module['version'] ?? NULL,
        ];
      }
    }

    // Invalidate the feed cache.
    Cache::invalidateTags(['status_feed_feed', 'status_feed_modules_status']);
    $this->setCache('status_feed_modules_status', $response, ['status_feed_feed', 'status_feed_modules_status']);

    return $response;
  }

  /**
   * Get Cron status data.
   *
   * @return array
   *   Monitor status.
   */
  protected function getStatusData() {
    $event = new MonitorStatusEvent();
    /** @var \Drupal\status_feed\Event\MonitorStatusEvent $monitor_event */
    $monitor_event = $this->eventDispatcher->dispatch($event, MonitorStatusEvents::REGISTER_STATUS);
    $response = $monitor_event->getMonitorStatus();

    // Invalidate the feed cache.
    Cache::invalidateTags(['status_feed_feed', 'status_feed_status_status']);
    $this->setCache('status_feed_status_status', $response, ['status_feed_feed', 'status_feed_cron_status']);

    return $response;
  }

  /**
   * Set caching for the given CID.
   *
   * @param string $cid
   *   Cache id.
   * @param mixed $response
   *   Response.
   * @param array $tags
   *   Cache tags.
   */
  public function setCache($cid, $response, array $tags) {
    $time = \Drupal::time()->getRequestTime() + self::$cacheTime;
    $this->cache->set($cid, $response, $time, $tags);
  }

}
