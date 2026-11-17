<?php

namespace Drupal\status_feed;

/**
 * Feed builder service.
 *
 * @package Drupal\status_feed
 */
class FeedBuilder {

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

    // Get the code that should be executed on cron.
    self::$dataCollector = $data_collector;
  }

  /**
   * Finished executing the script, set the execution time to original value.
   */
  public function __destruct() {
    set_time_limit(self::$maxExecutionTime);
  }

  /**
   * Return core data.
   *
   * @return array
   *   Collected core data.
   */
  public function getCoreData() {
    return self::$dataCollector->collectData('core');
  }

  /**
   * Return module data.
   *
   * @return array
   *   Collected module data.
   */
  public function getModuleData() {
    return self::$dataCollector->collectData('modules');
  }

  /**
   * Return status data.
   *
   * @return array
   *   Collected status data.
   */
  public function getStatusData() {
    return self::$dataCollector->collectData('status');
  }

  /**
   * Builds the same payload for HTTP feeds and outbound webhook delivery.
   */
  public function build(): array {
    return [
      'system' => 'drupal',
      'core' => $this->getCoreData(),
      'modules' => $this->getModuleData(),
      'created' => time(),
      'status' => $this->getStatusData(),
    ];
  }

}
