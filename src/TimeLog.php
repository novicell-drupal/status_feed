<?php

namespace Drupal\status_feed;

/**
 * Time logging service.
 *
 * @package Drupal\status_feed
 */
trait TimeLog {

  /**
   * Start time.
   *
   * @var string|float
   */
  protected $start;

  /**
   * End time.
   *
   * @var string|float
   */
  protected $end;

  /**
   * Start time log.
   */
  protected function startTimeLog() {
    $this->start = microtime(TRUE);
  }

  /**
   * End time log.
   */
  protected function endTimeLog() {
    $this->end = microtime(TRUE);
  }

  /**
   * Formatting the time log.
   *
   * @return string|null
   *   Formatted time or NULL if time isn't started or ended.
   */
  protected function getTimeLogFormatted() {
    $time = $this->getTimeLog();
    if (!empty($time)) {
      return number_format($time, 2);
    }
    return NULL;
  }

  /**
   * Get the time log.
   *
   * @return string|null
   *   Return time or NULL if time isn't started or ended.
   */
  protected function getTimeLog() {
    if (!empty($this->start) && !empty($this->end)) {
      $formatted_time = $this->end - $this->start;
      unset($this->start, $this->end);
      return $formatted_time;
    }

    return NULL;
  }

}
