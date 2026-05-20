<?php

declare(strict_types=1);

namespace InOtherAgents\Http\Controllers;

use Illuminate\Http\JsonResponse;
use InOtherAgents\Support\CanonicalUrl;

/**
 * RFC 8414 OAuth 2.0 Authorization Server Metadata.
 *
 * Passport ships /oauth/authorize and /oauth/token but doesn't publish
 * this discovery document; we do. The issuer is the same origin as the
 * MCP endpoint — Passport runs in-process on the consumer app.
 */
final class AuthorizationServerMetadataController
{
    public function __invoke(): JsonResponse
    {
        $issuer = CanonicalUrl::issuer();
        $scope = (string) config('agents.auth.oauth.scope', 'agent');

        $metadata = [
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer.'/oauth/authorize',
            'token_endpoint' => $issuer.'/oauth/token',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'scopes_supported' => [$scope],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post',
                'none',
            ],
        ];

        if ((bool) config('agents.auth.oauth.dcr.enabled', true)) {
            $metadata['registration_endpoint'] = $issuer.'/oauth/register';
        }

        return response()->json($metadata);
    }
}
