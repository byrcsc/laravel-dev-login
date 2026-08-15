<?php

declare(strict_types=1);

namespace ByRcsc\LaravelDevLogin\Http\Middleware;

use ByRcsc\LaravelDevLogin\Gatekeeper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The gate that does not trust the application's own configuration.
 *
 * Everything else can be switched on by an `.env` file that reaches the wrong
 * machine. This one is checked against the host the request actually arrived
 * on, and it answers with a 404 rather than a 403: a 403 tells a stranger the
 * page is there.
 */
final class EnsureHostIsAllowed
{
    public function __construct(private readonly Gatekeeper $gatekeeper) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->gatekeeper->hostIsAllowed($request->getHost()), 404);

        return $next($request);
    }
}
