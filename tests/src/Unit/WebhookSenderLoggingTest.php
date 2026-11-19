<?php

namespace Drupal\Tests\status_feed\Unit;

use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\State\StateInterface;
use Drupal\status_feed\FeedBuilder;
use Drupal\status_feed\WebhookSender;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Verifies what administrators see in the log after a rejected delivery.
 */
class WebhookSenderLoggingTest extends TestCase {

  /**
   * Logs the status and bounded diagnostic without echoing the secret or HTML.
   */
  #[DataProvider('errors')]
  public function testRejectedDelivery(string $body, string $expected): void {
    $secret = str_repeat('a', 64);
    $state = $this->createStub(StateInterface::class);
    $state->method('get')->willReturn([
      'enabled' => TRUE, 'url' => 'https://example.test/feed', 'token' => $secret,
    ]);
    $lock = $this->createStub(LockBackendInterface::class);
    $lock->method('acquire')->willReturn(TRUE);
    $builder = new class extends FeedBuilder {

      public function __construct() {}

      public function __destruct() {}

      public function build(): array {
        return [];
      }

    };
    $client = new Client(['handler' => HandlerStack::create(new MockHandler([new Response(403, [], $body)]))]);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('warning')->with(
      $this->stringContains('@reason'),
      ['@status' => 403, '@reason' => $expected],
    );
    $this->assertFalse((new WebhookSender($builder, $client, $state, $lock, $logger))->send(TRUE));
  }

  /**
   * Receiver errors and non-JSON responses from intermediaries.
   */
  public static function errors(): array {
    return [
      ['{"error":"Webhook receiving is disabled."}', 'Webhook receiving is disabled.'],
      ['<html>Forbidden</html>', 'The endpoint did not return a JSON error message.'],
      [json_encode(['error' => 'Invalid ' . str_repeat('a', 64)]), 'Invalid [redacted]'],
    ];
  }

}
