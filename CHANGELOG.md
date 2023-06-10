# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security

## [1.0.0] - 2023-06-10

### Added

- Add composer dependency `heptacom/heptaconnect-portal-base: ^0.9.5` as `\Heptacom\HeptaConnect\Package\Http\HttpPackage` is a package and HTTP middleware `\Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface` is used
- Add composer dependencies `psr/http-client: ^1.0`, `psr/http-factory: ^1.0` and `psr/http-message: ^1.0 || ^2.0` as PSR-7 based HTTP messages are used
- Add composer dependencies `psr/event-dispatcher: ^1.0`, `symfony/event-dispatcher: ^5.0 || ^6.0` and `symfony/event-dispatcher-contracts: ^2.0 || ^3.0` as events are dispatched
- Add composer dependency `symfony/dependency-injection: ^5.0 || ^6.0` as compiler passes are used
- Add composer dependency `symfony/options-resolver: ^5.1 || ^6.0` as unstructured data is validated using the Symfony options resolver
- Introduce outbound HTTP cache feature using `\Heptacom\HeptaConnect\Package\Http\Components\HttpCache\HttpCache` based on `\Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract\HttpCacheInterface`
- Add class `\Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Psr7MessageSerializer` described by `\Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract\Psr7MessageSerializerInterface` to serialize and deserialize PSR-7 messages for storing them in a cache
- Add event `\Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract\Event\HttpCacheActiveEvent` to influence, whether a request cycle is cached
- Add event `\Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract\Event\HttpCacheKeyEvent` to influence under which cache key a request is cached
- Add service tag `heptaconnect.http.client.middleware` to `Heptacom\HeptaConnect\Package\Http\Components\HttpCache\Contract\HttpCacheInterface` to ensure it is chained in outgoing HTTP communication
- Introduce HTTP request cycle profiling using `\Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\HttpRequestCycleProfiler` based on `\Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleProfilerInterface` to control, which outgoing request cycles shall be measured and how the measurements will be processed
- Add struct `\Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleCollector` to store `\Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycle` for each request in a request cycle measurement
- Add `\Heptacom\HeptaConnect\Package\Http\DependencyInjection\EventSubscriberTagCompilerPass` as class and into the container building to register any `\Symfony\Component\EventDispatcher\EventSubscriberInterface` as active subscriber
- Add service `Symfony\Component\EventDispatcher\EventDispatcherInterface` for class `\Symfony\Component\EventDispatcher\EventDispatcher` with aliases `event_dispatcher`, `Symfony\Contracts\EventDispatcher\EventDispatcherInterface` and `Psr\EventDispatcher\EventDispatcherInterface`
