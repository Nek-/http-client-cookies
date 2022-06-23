<?php

namespace Amp\Http\Client\Cookie;

use Amp\Http\Client\HttpException;
use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Cookie\ResponseCookie;
use Psr\Http\Message\UriInterface as PsrUri;

interface CookieJar
{
    /**
     * Retrieve all cookies matching the specified constraints.
     *
     * @param PsrUri $uri
     *
     * @return list<RequestCookie> Returns an array (possibly empty) of all cookie matches.
     */
    public function get(PsrUri $uri): array;

    /**
     * Store a cookie.
     *
     * @param ResponseCookie ...$cookie
     *
     * @throws HttpException
     */
    public function store(ResponseCookie ...$cookie): void;
}
