<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EventSubscriberTagCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            if (
                \is_a($definition->getClass(), EventSubscriberInterface::class, true)
                && !$definition->hasTag('kernel.event_subscriber')
            ) {
                $definition->addTag('kernel.event_subscriber');
            }
        }
    }
}
