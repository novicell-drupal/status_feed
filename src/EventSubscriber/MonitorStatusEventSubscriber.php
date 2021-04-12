<?php

namespace Drupal\status_feed\EventSubscriber;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\status_feed\Event\MonitorStatusEvent;
use Drupal\status_feed\Event\MonitorStatusEvents;
use Drupal\status_feed\Model\MonitorStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Monitor status event-subscriber.
 *
 * @package Drupal\status_feed\EventSubscriber
 */
class MonitorStatusEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MonitorStatusEvents::REGISTER_STATUS => ['onRegisterStatus'],
    ];
  }

  /**
   * AStatus Feed status, with info about when last cron was run.
   *
   * @param \Drupal\status_feed\Event\MonitorStatusEvent $event
   *   Monitor status event.
   */
  public function onRegisterStatus(MonitorStatusEvent $event) {
    $last_cron_run = \Drupal::state()->get('system.cron_last');
    $last_cron_date = new DrupalDateTime();
    $date = new DrupalDateTime('now');
    $status = $last_cron_run > $date->modify('-1 day')->getTimestamp();

    $cron_status = new MonitorStatus(
      'drupal_last_cron',
      $status ? RfcLogLevel::INFO : RfcLogLevel::CRITICAL,
      'Last cron run was: ' . $last_cron_date->setTimestamp($last_cron_run)->format('d-m-Y H:i:s')
    );
    $event->addMonitorStatus($cron_status);
  }

}
