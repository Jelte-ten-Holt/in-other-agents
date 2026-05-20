<?php

declare(strict_types=1);

namespace InOtherAgents\Support;

/**
 * Resolver for the consumer's externally-reachable MCP origin and
 * audience identifier.
 *
 * Two values, derived from a single config knob (`agents.canonical_url`):
 *
 *   - resource() — the RFC 9728 protected-resource identifier, also the
 *                  RFC 8707 audience that issued OAuth tokens are bound to.
 *                  Typically the full /mcp URL.
 *   - issuer()   — the OAuth authorization-server origin (scheme + host +
 *                  optional port). Passport runs in-process so the issuer
 *                  is the same origin as the resource — we just strip the
 *                  path. Used as the `iss` field in metadata responses and
 *                  in WWW-Authenticate's `resource_metadata=` URL.
 *
 * Falls back to Laravel's `url()` helper when canonical_url isn't set;
 * fine for local dev, but Co-work / remote MCP clients need a stable
 * hostname — set AGENT_CANONICAL_URL in production.
 */
final class CanonicalUrl
{
    public static function resource(): string
    {
        $configured = config('agents.canonical_url');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        return rtrim(url((string) config('agents.route.path', '/mcp')), '/');
    }

    public static function issuer(): string
    {
        $resource = self::resource();
        $parsed = parse_url($resource);

        if (! isset($parsed['scheme'], $parsed['host'])) {
            return rtrim(url('/'), '/');
        }

        $origin = $parsed['scheme'].'://'.$parsed['host'];

        if (isset($parsed['port'])) {
            $origin .= ':'.$parsed['port'];
        }

        return $origin;
    }
}
