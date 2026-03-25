<?php

declare(strict_types=1);

namespace Payroad\Bridge\Symfony\Provider;

use Payroad\Application\Exception\ProviderNotFoundException;
use Payroad\Port\Provider\Card\CardProviderInterface;
use Payroad\Port\Provider\Cash\CashProviderInterface;
use Payroad\Port\Provider\Crypto\CryptoProviderInterface;
use Payroad\Port\Provider\P2P\P2PProviderInterface;
use Payroad\Port\Provider\PaymentProviderInterface;
use Payroad\Port\Provider\ProviderRegistryInterface;

final class ProviderRegistry implements ProviderRegistryInterface
{
    /** @param iterable<PaymentProviderInterface> $providers */
    public function __construct(private readonly iterable $providers) {}

    public function forCard(string $providerName): CardProviderInterface
    {
        return $this->resolve($providerName, CardProviderInterface::class);
    }

    public function forCrypto(string $providerName): CryptoProviderInterface
    {
        return $this->resolve($providerName, CryptoProviderInterface::class);
    }

    public function forP2P(string $providerName): P2PProviderInterface
    {
        return $this->resolve($providerName, P2PProviderInterface::class);
    }

    public function forCash(string $providerName): CashProviderInterface
    {
        return $this->resolve($providerName, CashProviderInterface::class);
    }

    public function getByName(string $providerName): PaymentProviderInterface
    {
        return $this->resolve($providerName, PaymentProviderInterface::class);
    }

    private function resolve(string $providerName, string $interface): mixed
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof $interface && $provider->supports($providerName)) {
                return $provider;
            }
        }

        throw new ProviderNotFoundException($providerName);
    }
}
