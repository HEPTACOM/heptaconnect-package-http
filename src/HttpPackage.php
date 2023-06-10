<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http;

use Heptacom\HeptaConnect\Package\Http\DependencyInjection\EventSubscriberTagCompilerPass;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PackageContract;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class HttpPackage extends PackageContract
{
    public function buildContainer(ContainerBuilder $containerBuilder): void
    {
        parent::buildContainer($containerBuilder);
        $containerBuilder->addCompilerPass(new EventSubscriberTagCompilerPass());
        $containerBuilder->addCompilerPass(
            new RegisterListenersPass(EventDispatcherInterface::class)
        );
    }
}
