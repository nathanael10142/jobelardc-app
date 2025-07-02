<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request; // Import the Request class

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    // We will trust all proxies (**) because Render.com uses dynamic IPs.
    // This is the simplest and most common solution for deployments on Render.
    protected $proxies = '**';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    // Ensure that all relevant headers are included so that Laravel
    // can correctly detect the protocol (HTTPS), host, etc., behind the proxy.
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB; // Include this header for broader compatibility with load balancers

}
