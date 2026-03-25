<?php

declare(strict_types=1);

namespace Payroad\Bridge\Symfony\Webhook;

use Payroad\Port\Provider\WebhookEvent;

/**
 * Allows application code to register provider-specific webhook verification
 * logic that runs after the provider parses the raw request.
 *
 * Tag implementing services with 'payroad.webhook_verifier'.
 */
interface WebhookVerifierInterface
{
    public function supports(string $providerName): bool;

    /**
     * Verify the authenticity of the webhook. Throw any exception to reject it.
     */
    public function verify(WebhookEvent $event, array $payload, array $headers): void;
}
