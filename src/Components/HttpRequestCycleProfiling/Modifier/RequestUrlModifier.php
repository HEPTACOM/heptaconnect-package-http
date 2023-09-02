<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Modifier;

use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycle;
use Heptacom\HeptaConnect\Package\Http\Components\HttpRequestCycleProfiling\Contract\HttpRequestCycleModifierInterface;
use Psr\Http\Message\RequestInterface;

final class RequestUrlModifier implements HttpRequestCycleModifierInterface
{
    /**
     * @param array{string, string}|null $schemeReplacement
     * @param array{string, string}|null $userInfoReplacement
     * @param array{string, string}|null $hostReplacement
     * @param array{string, string}|null $portReplacement
     * @param array{string, string}|null $pathReplacement
     * @param array{string, string}|null $queryReplacement
     * @param array{string, string}|null $fragmentReplacement
     */
    public function __construct(
        private ?array $schemeReplacement = null,
        private ?array $userInfoReplacement = null,
        private ?array $hostReplacement = null,
        private ?array $portReplacement = null,
        private ?array $pathReplacement = null,
        private ?array $queryReplacement = null,
        private ?array $fragmentReplacement = null,
    ) {
    }

    public function modify(HttpRequestCycle $httpRequestCycle): HttpRequestCycle
    {
        $request = $this->replaceRequest($httpRequestCycle->getRequest());

        return new HttpRequestCycle($request, $httpRequestCycle->getResponse(), $httpRequestCycle->getMetadata());
    }

    private function replaceRequest(RequestInterface $request): RequestInterface
    {
        $uri = $request->getUri();

        if (\is_array($this->schemeReplacement)) {
            $uri = $uri->withScheme(\preg_replace($this->schemeReplacement[0], $this->schemeReplacement[1], $uri->getScheme()) ?? '');
        }

        if (\is_array($this->userInfoReplacement)) {
            $userInfoValue = \preg_replace($this->userInfoReplacement[0], $this->userInfoReplacement[1], $uri->getUserInfo()) ?? '';
            $userInfo = \explode(':', $userInfoValue, 2);

            $uri = $uri->withUserInfo($userInfo[0], $userInfo[1] ?? null);
        }

        if (\is_array($this->hostReplacement)) {
            $uri = $uri->withHost(\preg_replace($this->hostReplacement[0], $this->hostReplacement[1], $uri->getHost()) ?? '');
        }

        if (\is_array($this->portReplacement)) {
            $portValue = \preg_replace($this->portReplacement[0], $this->portReplacement[1], (string) $uri->getPort());

            if (\is_numeric($portValue)) {
                $port = (int) $portValue;
            } else {
                $port = null;
            }

            $uri = $uri->withPort($port);
        }

        if (\is_array($this->pathReplacement)) {
            $uri = $uri->withPath(\preg_replace($this->pathReplacement[0], $this->pathReplacement[1], $uri->getPath()) ?? '');
        }

        if (\is_array($this->queryReplacement)) {
            $uri = $uri->withQuery(\preg_replace($this->queryReplacement[0], $this->queryReplacement[1], $uri->getQuery()) ?? '');
        }

        if (\is_array($this->fragmentReplacement)) {
            $uri = $uri->withFragment(\preg_replace($this->fragmentReplacement[0], $this->fragmentReplacement[1], $uri->getFragment()) ?? '');
        }

        return $request->withUri($uri);
    }
}
