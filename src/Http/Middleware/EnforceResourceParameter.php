<?php

declare(strict_types=1);

namespace InOtherAgents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use InOtherAgents\Support\CanonicalUrl;
use Symfony\Component\HttpFoundation\Response;

/**
 * RFC 8707 Resource Indicators.
 *
 * Clients following the MCP authorization spec include a `resource`
 * parameter on /oauth/authorize and /oauth/token to identify the resource
 * server a token is destined for. We accept only the canonical URL of
 * this consumer's /mcp endpoint; anything else is `invalid_target`.
 *
 * Tolerance for a missing `resource` is controlled by
 * `agents.auth.oauth.require_resource`:
 *   - false (default): absent `resource` is accepted. Single-resource AS
 *     shortcut — tokens issued without `resource` are implicitly bound
 *     to this AS because it has exactly one protected resource.
 *   - true: absent `resource` is rejected. Setups with more than one MCP
 *     endpoint behind the same Passport AS should flip this on.
 *
 * Wired into Passport's middleware stack from `AgentServiceProvider`
 * (pushed onto `passport.middleware`); applied to every Passport route
 * including /oauth/authorize and /oauth/token.
 */
final class EnforceResourceParameter
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('agents.auth.oauth.enabled', false)) {
            return $next($request);
        }

        $resource = $request->input('resource');
        $expected = CanonicalUrl::resource();

        if ($resource === null || $resource === '') {
            if ((bool) config('agents.auth.oauth.require_resource', false)) {
                return $this->invalidTarget($expected);
            }

            return $next($request);
        }

        $candidates = is_array($resource) ? $resource : [$resource];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || rtrim($candidate, '/') !== $expected) {
                return $this->invalidTarget($expected);
            }
        }

        return $next($request);
    }

    private function invalidTarget(string $expected): Response
    {
        return response()->json([
            'error' => 'invalid_target',
            'error_description' => "The requested resource is not served by this authorization server. Expected: {$expected}.",
        ], 400);
    }
}
