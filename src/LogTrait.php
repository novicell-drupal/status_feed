<?php

namespace Drupal\status_feed;

/**
 * Log trait.
 *
 * @package Drupal\status_feed
 */
trait LogTrait {

  /**
   * Print to log.
   *
   * @param string $message
   *   Log message.
   * @param string $type
   *   Log status.
   * @param bool $use_drupal_logger
   *   TRUE for using drupal logger.
   */
  public function log($message, $type = 'status', $use_drupal_logger = FALSE) {
    $this->errorLog($message);
    if ($use_drupal_logger == TRUE) {
      \Drupal::logger(get_called_class())->$type($message);
    }
  }

  /**
   * Print to error_log.
   *
   * @param string $message
   *   Log message.
   */
  protected function errorLog($message) {
    error_log($message);
  }

}
