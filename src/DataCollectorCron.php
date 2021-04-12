<?php

namespace Drupal\status_feed;

use Drupal\Core\Cache\Cache;

/**
 * Run data collector on cron.
 *
 * @package Drupal\status_feed
 */
class DataCollectorCron {

  const STATE_KEY = 'status_feed.data_collection.next_execution';

  use LogTrait;
  use TimeLog;

  /**
   * The request of cron.
   *
   * In used to calculate the next time this script is executed by cron.
   *
   * @var int
   */
  private static $requestTime;

  /**
   * Data collector.
   *
   * @var \Drupal\status_feed\DataCollector
   */
  private static $dataCollector;

  /**
   * Containing the max execution time defined in php.ini.
   *
   * @var string
   */
  private static $maxExecutionTime;

  /**
   * DataCollectorCron constructor.
   *
   * @param \Drupal\status_feed\DataCollector $data_collector
   *   Data collector.
   */
  public function __construct(DataCollector $data_collector) {
    // To prevent the script from timing out we set the time limit to 0.
    self::$maxExecutionTime = ini_get('max_execution_time');
    set_time_limit(0);

    self::$requestTime = \Drupal::time()->getRequestTime();
    self::$dataCollector = $data_collector;
  }

  /**
   * Finished executing the script, set the execution time to original value.
   */
  public function __destruct() {
    set_time_limit(self::$maxExecutionTime);
  }

  /**
   * Code to be executed by cron.
   */
  public function run() {
    $this->log('Cron run');
    $this->startTimeLog();

    // We usually don't want to act every time
    // cron runs (which could be every minute)
    // so keep a time for the next run in the site state.
    $next_execution = \Drupal::state()->get(self::STATE_KEY, 0);

    // If next execution time is passed.
    if (self::$requestTime >= $next_execution) {
      $updated_next_execution = strtotime(date('Y-m-d H:i', self::$requestTime) . ':00') + self::$dataCollector::$cacheTime;

      if (self::$dataCollector->execute()) {
        \Drupal::state()->set(self::STATE_KEY, $updated_next_execution);
        Cache::invalidateTags(['status_feed_feed']);
      }
    }

    $this->endTimeLog();
    $this->log($this->getTimeLogFormatted());
  }

}
