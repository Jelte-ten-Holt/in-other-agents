<?php

declare(strict_types=1);

namespace InOtherAgents\Http\Controllers;

use Illuminate\Http\JsonResponse;
use InOtherAgents\Support\CanonicalUrl;

/**
 * RFC 9728 OAuth 2.0 Protected Resource Metadata.
 *
 * Advertised via `WWW-Authenticate: Bearer resource_metadata="..."` on 401
 * responses from the MCP endpoint, so an OAuth-aware client can discover
 * where to obtain tokens without out-of-band configuration.
 */
final class ProtectedResourceMetadataController
{
    public function __invoke(): JsonResponse
    {
        $scope = (string) config('agents.auth.oauth.scope', 'agent');

        return response()->json([
            'resource' => CanonicalUrl::resource(),
            'authorization_servers' => [CanonicalUrl::issuer()],
            'scopes_supported' => [$scope],
            'bearer_methods_supported' => ['header'],
        ]);
    }
}
