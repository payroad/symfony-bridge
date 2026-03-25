# payroad/symfony-bridge

Symfony integration bundle for the [Payroad](https://github.com/payroad/payroad-core) payment platform.

## Features

- `PayroadBundle` — auto-registers all services
- `ProviderRegistry` — resolves typed providers (`forCard()`, `forCrypto()`, `forP2P()`, `forCash()`)
- `WebhookController` — routes incoming webhooks to `HandleWebhookUseCase`
- `PayroadExtension` — loads provider configuration from `payroad.yaml`
- `ProviderFactoryInterface` — pluggable factory pattern for DI-based provider registration

## Requirements

- PHP 8.2+
- Symfony 6.4+
- `payroad/payroad-core`

## Installation

```bash
composer require payroad/symfony-bridge
```

## Configuration

```yaml
# config/packages/payroad.yaml
payroad:
  providers:
    stripe:
      factory: Payroad\Provider\Stripe\StripeProviderFactory
      secret_key:     '%env(STRIPE_SECRET_KEY)%'
      webhook_secret: '%env(STRIPE_WEBHOOK_SECRET)%'

    braintree:
      factory: Payroad\Provider\Braintree\BraintreeProviderFactory
      environment: '%env(BRAINTREE_ENVIRONMENT)%'
      merchant_id: '%env(BRAINTREE_MERCHANT_ID)%'
      public_key:  '%env(BRAINTREE_PUBLIC_KEY)%'
      private_key: '%env(BRAINTREE_PRIVATE_KEY)%'

    internal_cash:
      factory: Payroad\Provider\InternalCash\InternalCashProviderFactory
```

## Webhook routing

The bundle registers webhook endpoints automatically:

```
POST /webhooks/{providerName}
```

Map these URLs in your provider dashboards. The controller verifies signatures (if the provider supports it) and delegates to `HandleWebhookUseCase`.
