<?php

namespace Drupal\status_feed\Event;

use Drupal\status_feed\Model\MonitorStatus;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Monitor status event.
 *
 * @package Drupal\status_feed\Event
 */
class MonitorStatusEvent extends Event {

  /**
   * Monitor status collector.
   *
   * @var array
   */
  protected $status = [];

  /**
   * Return the monitor status array.
   *
   * @return array
   *   Monitor status array.
   */
  public function getMonitorStatus() {
    return $this->status;
  }

  /**
   * Adding monitor status to array.
   *
   * @param \Drupal\status_feed\Model\MonitorStatus $status
   *   Monitor status.
   */
  public function addMonitorStatus(MonitorStatus $status): void {
    $this->status[$status->getName()] = $status->getData();
  }

}
