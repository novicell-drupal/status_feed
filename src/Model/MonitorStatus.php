<?php

namespace Drupal\status_feed\Model;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Class MonitorStatus.
 *
 * @package Drupal\status_feed\Model
 */
class MonitorStatus {

  /**
   * The name of the status you want to make.
   *
   * @var string
   */
  protected $name;

  /**
   * The severity of this status. Uses the same as drupal message severities.
   *
   * @var int
   *
   * @see \Drupal\Core\Logger\RfcLogLevel
   */
  protected $severity;

  /**
   * The message to include with the status. Why is this status here?
   *
   * @var string
   */
  protected $message;

  /**
   * MonitorStatus constructor.
   *
   * @param string $name
   *   Status name.
   * @param int $severity
   *   The severity of the status.
   * @param string $message
   *   The message to include with the status.
   */
  public function __construct($name, $severity = RfcLogLevel::INFO, $message = '') {
    $this->name = $name;
    $this->severity = $severity;
    $this->message = $message;
  }

  /**
   * Returns an array of data with the MonitorStatus.
   *
   * @return array
   *   An array of the important data on this object.
   */
  public function getData(): array {
    return [
      'name' => $this->getName(),
      'severity' => $this->getSeverity(),
      'message' => $this->getMessage(),
    ];
  }

  /**
   * Return status name.
   *
   * @return string
   *   Status name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set status name.
   *
   * @param string $name
   *   Status name.
   */
  public function setName($name): void {
    $this->name = $name;
  }

  /**
   * Return the severity.
   *
   * @return int
   *   Severity level.
   *
   * @see RfcLogLevel::getLevels()
   */
  public function getSeverity() {
    return $this->severity;
  }

  /**
   * Set severity level.
   *
   * @param int $severity
   *   Severity level.
   *
   * @see RfcLogLevel::getLevels()
   */
  public function setSeverity($severity): void {
    $this->severity = $severity;
  }

  /**
   * Return status message.
   *
   * @return string
   *   Status message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Set status message.
   *
   * @param string $message
   *   Status message.
   */
  public function setMessage($message): void {
    $this->message = $message;
  }

}
