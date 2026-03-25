<?php

declare(strict_types=1);

namespace Payroad\Bridge\Symfony;

use Payroad\Bridge\Symfony\DependencyInjection\PayroadExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PayroadBundle extends Bundle
{
    public function getContainerExtension(): PayroadExtension
    {
        return new PayroadExtension();
    }
}
