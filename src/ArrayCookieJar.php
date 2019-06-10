<?php

namespace Amp\Http\Client\Cookie;

use Amp\Http\Client\Request;
use Amp\Http\Cookie\ResponseCookie;

class ArrayCookieJar implements CookieJar
{
    private $cookies = [];

    /**
     * Store a cookie.
     *
     * @param ResponseCookie $cookie
     *
     * @return void
     */
    public function store(ResponseCookie $cookie): void
    {
        $this->cookies[$cookie->getDomain()][$cookie->getPath() ?: '/'][$cookie->getName()] = $cookie;
    }

    /**
     * Remove a specific cookie from the storage.
     *
     * @param ResponseCookie $cookie
     */
    public function remove(ResponseCookie $cookie): void
    {
        unset($this->cookies[$cookie->getDomain()][$cookie->getPath() ?: '/'][$cookie->getName()]);
    }

    /**
     * Remove all stored cookies.
     */
    public function removeAll(): void
    {
        $this->cookies = [];
    }

    /**
     * Retrieve all stored cookies.
     *
     * @return array Returns array in the format `$array[$domain][$path][$cookieName]`.
     */
    public function getAll(): array
    {
        return $this->cookies;
    }

    /**
     * Retrieve all cookies matching the specified constraints.
     *
     * @param Request $request
     *
     * @return array Returns an array (possibly empty) of all cookie matches.
     */
    public function get(Request $request): array
    {
        $this->clearExpiredCookies();

        $path = $request->getUri()->getPath() ?: '/';
        $domain = \strtolower($request->getUri()->getHost());

        $matches = [];

        foreach ($this->cookies as $cookieDomain => $domainCookies) {
            if (!$this->matchesDomain($domain, $cookieDomain)) {
                continue;
            }

            foreach ($domainCookies as $cookiePath => $pathCookies) {
                if (!$this->matchesPath($path, $cookiePath)) {
                    continue;
                }

                foreach ($pathCookies as $cookieName => $cookie) {
                    $matches[] = $cookie;
                }
            }
        }

        return $matches;
    }

    private function clearExpiredCookies(): void
    {
        foreach ($this->cookies as $domain => $domainCookies) {
            foreach ($domainCookies as $path => $pathCookies) {
                foreach ($pathCookies as $name => $cookie) {
                    /** @var ResponseCookie $cookie */
                    if ($cookie->getExpiry() && $cookie->getExpiry()->getTimestamp() < \time()) {
                        unset($this->cookies[$domain][$path][$name]);
                    }
                }
            }
        }
    }

    /**
     * @param string $requestDomain
     * @param string $cookieDomain
     *
     * @return bool
     *
     * @link http://tools.ietf.org/html/rfc6265#section-5.1.3
     */
    private function matchesDomain(string $requestDomain, string $cookieDomain): bool
    {
        if ($requestDomain === \ltrim($cookieDomain, '.')) {
            return true;
        }

        /** @noinspection SubStrUsedAsStrPosInspection */
        $isWildcardCookieDomain = $cookieDomain[0] === '.';
        if (!$isWildcardCookieDomain) {
            return false;
        }

        if (\filter_var($requestDomain, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (\substr($requestDomain, 0, -\strlen($cookieDomain)) . $cookieDomain === $requestDomain) {
            return true;
        }

        return false;
    }

    /**
     * @link http://tools.ietf.org/html/rfc6265#section-5.1.4
     *
     * @param string $requestPath
     * @param string $cookiePath
     *
     * @return bool
     */
    private function matchesPath(string $requestPath, string $cookiePath): bool
    {
        if ($requestPath === $cookiePath) {
            $isMatch = true;
        } elseif (\strpos($requestPath, $cookiePath) === 0
            && (\substr($cookiePath, -1) === '/' || $requestPath[\strlen($cookiePath)] === '/')
        ) {
            $isMatch = true;
        } else {
            $isMatch = false;
        }

        return $isMatch;
    }
}
