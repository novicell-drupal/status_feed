<?php

namespace Drupal\status_feed;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
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
    if (empty($settings['enabled'])) {
      if ($force) {
        $this->logger->warning('Status feed was not sent because webhook sending is disabled on this site.');
      }
      return FALSE;
    }
    if (empty($settings['url']) || empty($settings['token'])) {
      $this->logger->warning('Status feed was not sent because the webhook @setting is missing.', [
        '@setting' => empty($settings['url']) ? 'URL' : 'secret',
      ]);
      return FALSE;
    }
    if (!$force && time() < $this->state->get('status_feed.webhook_next_attempt', 0)) {
      return FALSE;
    }
    if (!$this->lock->acquire('status_feed.webhook_send', 60)) {
      return FALSE;
    }
    $stage = 'scheduling delivery';
    try {
      // Retry failures on a later cron run, without delaying data collection.
      $interval = max(60, (int) ($settings['interval'] ?? 3600));
      $this->state->set('status_feed.webhook_next_attempt', time() + min(300, $interval));
      $stage = 'building the status feed';
      $feed = $this->builder->build();
      $stage = 'posting the status feed';
      $response = $this->client->request('POST', $settings['url'], [
        'headers' => ['Authorization' => 'Bearer ' . $settings['token'], 'Accept' => 'application/json'],
        'json' => $feed,
        'connect_timeout' => 5,
        'timeout' => 30,
        'allow_redirects' => FALSE,
        'http_errors' => FALSE,
      ]);
      if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
        $this->logger->warning('Status webhook rejected the feed (HTTP @status): @reason', [
          '@status' => $response->getStatusCode(),
          '@reason' => $this->rejectionReason($response, $settings['token']),
        ]);
        return FALSE;
      }
      $stage = 'recording successful delivery';
      $this->state->set('status_feed.webhook_last_success', time());
      $this->state->set('status_feed.webhook_next_attempt', time() + $interval);
      return TRUE;
    }
    catch (\Throwable $exception) {
      // Transport exception messages may contain credentials or the payload.
      $this->logger->warning('Status webhook failed while @stage (@type).', ['@stage' => $stage, '@type' => get_class($exception)]);
      return FALSE;
    }
    finally {
      $this->lock->release('status_feed.webhook_send');
    }
  }

  /**
   * Extracts the receiver's JSON diagnostic without logging raw error pages.
   */
  private function rejectionReason(ResponseInterface $response, string $token): string {
    $body = $response->getBody()->read(4097);
    if (strlen($body) <= 4096) {
      $error = json_decode($body, TRUE)['error'] ?? NULL;
      if (is_string($error)) {
        $reason = trim((string) preg_replace('/[[:cntrl:]]+/', ' ', str_replace($token, '[redacted]', $error)));
        if ($reason !== '') {
          return mb_substr($reason, 0, 512);
        }
      }
    }
    return 'The endpoint did not return a JSON error message.';
  }

}
