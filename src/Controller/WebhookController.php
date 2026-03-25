<?php

declare(strict_types=1);

namespace Payroad\Bridge\Symfony\Controller;

use Payroad\Application\Exception\AttemptNotFoundException;
use Payroad\Application\Exception\RefundNotFoundException;
use Payroad\Application\UseCase\Webhook\HandleRefundWebhookCommand;
use Payroad\Application\UseCase\Webhook\HandleRefundWebhookUseCase;
use Payroad\Application\UseCase\Webhook\HandleWebhookCommand;
use Payroad\Application\UseCase\Webhook\HandleWebhookUseCase;
use Payroad\Bridge\Symfony\Webhook\WebhookVerifierInterface;
use Payroad\Port\Provider\ProviderRegistryInterface;
use Payroad\Port\Provider\RefundWebhookResult;
use Payroad\Port\Provider\WebhookResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController
{
    /**
     * @param iterable<WebhookVerifierInterface> $verifiers
     */
    public function __construct(
        private readonly ProviderRegistryInterface  $registry,
        private readonly HandleWebhookUseCase       $handleWebhook,
        private readonly HandleRefundWebhookUseCase $handleRefundWebhook,
        private readonly iterable                   $verifiers,
    ) {}

    #[Route('/webhooks/{provider}', methods: ['POST'])]
    public function handle(string $provider, Request $request): Response
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);

        // Flatten headers to string map. Always include the raw body under 'raw-body'
        // so providers that need it for signature verification can access it.
        // Query string params are merged in so providers can read callback tokens
        // embedded in the URL (e.g. CoinGate HMAC token).
        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $headers[$key] = $values[0] ?? '';
        }
        foreach ($request->query->all() as $key => $value) {
            $headers[$key] = (string) $value;
        }
        $headers['raw-body'] = $rawBody;

        $providerInstance = $this->registry->getByName($provider);
        $event            = $providerInstance->parseIncomingWebhook($payload, $headers);

        if ($event === null) {
            return new Response('OK', Response::HTTP_OK);
        }

        try {
            // Run optional application-level verifiers (e.g. CoinGate token check).
            foreach ($this->verifiers as $verifier) {
                if ($verifier->supports($provider)) {
                    $verifier->verify($event, $payload, $headers);
                }
            }

            if ($event instanceof RefundWebhookResult) {
                $this->handleRefundWebhook->execute(new HandleRefundWebhookCommand($provider, $event));
            } elseif ($event instanceof WebhookResult) {
                $this->handleWebhook->execute(new HandleWebhookCommand($provider, $event));
            }
        } catch (AttemptNotFoundException | RefundNotFoundException) {
            // Unknown aggregate — acknowledge so the provider does not retry.
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
