<?php

declare(strict_types=1);

namespace InOtherAgents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolver for the /mcp endpoint.
 *
 * v1 supports only the static bearer token at `config('agents.auth.bearer_token')`.
 * OAuth (Passport + DCR) is planned for when this package is wired up for Co-work;
 * the in-other-shops Agent domain has the reference shape — lift it back in here
 * when the OAuth migration happens.
 *
 * On success the request gets stamped:
 *   - `agent.is_admin`    — always true for bearer holders (operator)
 *   - `agent.bearer_hash` — short SHA-256 prefix of the token, for log correlation
 */
final class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();

        if ($bearer !== '' && $this->authenticateViaStaticBearer($bearer, $request)) {
            return $next($request);
        }

        return response()->json(['error' => 'unauthorized'], 401, [
            'WWW-Authenticate' => 'Bearer',
        ]);
    }

    private function authenticateViaStaticBearer(string $bearer, Request $request): bool
    {
        $expected = (string) config('agents.auth.bearer_token', '');

        if ($expected === '' || ! hash_equals($expected, $bearer)) {
            return false;
        }

        $request->attributes->set('agent.is_admin', true);
        $request->attributes->set('agent.bearer_hash', substr(hash('sha256', $bearer), 0, 12));

        return true;
    }
}
