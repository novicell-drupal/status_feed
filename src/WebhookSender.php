<?php

namespace Drupal\status_feed;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Pushes the normal status feed to a website-specific Drools webhook.
 */
class WebhookSender {

  public const SETTINGS = 'status_feed.webhook';

  public function __construct(
    protected FeedBuilder $builder,
    protected ClientInterface $client,
    protected StateInterface $state,
    protected LockBackendInterface $lock,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sends when due, or immediately when requested from the settings form.
   */
  public function send(bool $force = FALSE): bool {
    $settings = $this->state->get(self::SETTINGS, []);
    if (empty($settings['enabled']) || empty($settings['url']) || empty($settings['token'])) {
      return FALSE;
    }
    if (!$force && time() < $this->state->get('status_feed.webhook_next_attempt', 0)) {
      return FALSE;
    }
    if (!$this->lock->acquire('status_feed.webhook_send', 60)) {
      return FALSE;
    }
    try {
      // Retry failures on a later cron run, without delaying data collection.
      $interval = max(60, (int) ($settings['interval'] ?? 3600));
      $this->state->set('status_feed.webhook_next_attempt', time() + min(300, $interval));
      $response = $this->client->request('POST', $settings['url'], [
        'headers' => ['Authorization' => 'Bearer ' . $settings['token'], 'Accept' => 'application/json'],
        'json' => $this->builder->build(),
        'connect_timeout' => 5,
        'timeout' => 30,
        'allow_redirects' => FALSE,
        'http_errors' => FALSE,
      ]);
      if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
        $this->logger->warning('Status webhook rejected the feed (HTTP @status).', ['@status' => $response->getStatusCode()]);
        return FALSE;
      }
      $this->state->set('status_feed.webhook_last_success', time());
      $this->state->set('status_feed.webhook_next_attempt', time() + $interval);
      return TRUE;
    }
    catch (\Throwable $exception) {
      // Transport exception messages may contain credentials or the payload.
      $this->logger->warning('Status webhook delivery failed (@type); it will retry on cron.', ['@type' => get_class($exception)]);
      return FALSE;
    }
    finally {
      $this->lock->release('status_feed.webhook_send');
    }
  }

}
