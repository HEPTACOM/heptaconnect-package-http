<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EventSubscriberTagCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $id = $definition->getClass() ?? $id;

            if (
                \is_a($id, EventSubscriberInterface::class, true)
                && !$definition->hasTag('kernel.event_subscriber')
            ) {
                $definition->addTag('kernel.event_subscriber');
            }
        }
    }
}
