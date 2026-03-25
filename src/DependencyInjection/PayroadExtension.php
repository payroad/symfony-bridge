<?php

declare(strict_types=1);

namespace Payroad\Bridge\Symfony\DependencyInjection;

use Payroad\Port\Provider\PaymentProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class PayroadExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        // Load bundle's own services (ProviderRegistry, WebhookController, etc.)
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Register a service pair (factory + provider) for each configured provider.
        foreach ($config['providers'] as $name => $providerConfig) {
            $factoryClass = $providerConfig['factory'];
            $providerConf = $providerConfig['config'] ?? [];

            // Factory — no-argument, instantiated once by the container.
            $factoryId = sprintf('payroad.provider.%s.factory', $name);
            $container->register($factoryId, $factoryClass)->setPublic(false);

            // Resolve the concrete return type of create() via reflection so that
            // the service can be autowired by concrete class (e.g. BraintreeProvider).
            $returnType  = (new \ReflectionMethod($factoryClass, 'create'))->getReturnType();
            $concreteClass = ($returnType instanceof \ReflectionNamedType && !$returnType->isBuiltin())
                ? $returnType->getName()
                : PaymentProviderInterface::class;

            // Provider — created by calling factory->create($config) at runtime,
            // so %env(...)% values are resolved before the call.
            $providerId = sprintf('payroad.provider.%s', $name);
            $container->register($providerId, $concreteClass)
                ->setFactory([new Reference($factoryId), 'create'])
                ->addArgument($providerConf)
                ->addTag('payroad.provider')
                ->setPublic(false);

            // Register an autowiring alias for the concrete class when it differs
            // from the base interface (allows injecting BraintreeProvider directly).
            if ($concreteClass !== PaymentProviderInterface::class) {
                $container->setAlias($concreteClass, $providerId)->setPublic(false);
            }
        }
    }

    public function getAlias(): string
    {
        return 'payroad';
    }
}
