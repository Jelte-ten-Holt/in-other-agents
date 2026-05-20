<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tools
    |--------------------------------------------------------------------------
    |
    | The consumer's tool class list. Each entry is the fully qualified class
    | name of an AgentTool. The order here is the order in which tools are
    | advertised to MCP clients via tools/list.
    |
    */
    'tools' => [],

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    */
    'route' => [
        'enabled' => true,
        'path' => env('AGENT_ROUTE_PATH', '/mcp'),
        'throttle' => env('AGENT_ROUTE_THROTTLE', '60,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical URL
    |--------------------------------------------------------------------------
    |
    | The stable, externally-reachable URL of this consumer's MCP endpoint.
    | Used as the RFC 9728 `resource` identifier in protected-resource-metadata
    | and as the RFC 8707 audience that issued OAuth tokens are bound to.
    |
    | Must be set when OAuth is enabled. For local dev you can leave it blank
    | and the resolver will fall back to `url(config('agents.route.path'))`,
    | but Co-work / remote MCP clients need a stable hostname — set this to
    | your production DNS (e.g. `https://app.example.com/mcp`).
    |
    */
    'canonical_url' => env('AGENT_CANONICAL_URL'),

    /*
    |--------------------------------------------------------------------------
    | Server identity
    |--------------------------------------------------------------------------
    |
    | Advertised to MCP clients in the initialize response.
    |
    */
    'server' => [
        'name' => env('AGENT_SERVER_NAME', 'In Other Agents'),
        'version' => env('AGENT_SERVER_VERSION', '0.2.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Two paths, resolved in order by `AuthenticateAgent`:
    |
    |   1. OAuth 2.1 access token — Passport-issued, audience-bound to
    |      `agents.canonical_url`. Enabled by `auth.oauth.enabled`. Required
    |      for Co-work / web-based MCP clients that won't paste a bearer.
    |
    |   2. Static bearer token — the `auth.bearer_token` fallback, kept for
    |      Claude Code / MCP Inspector / curl. Empty → that path fails closed;
    |      if OAuth is also disabled, every request 401s.
    |
    | The two can coexist: Co-work goes through OAuth, CLI clients stay on
    | the bearer. Both paths 401 with the same RFC 9728 `WWW-Authenticate`
    | header so an OAuth-capable client can discover the metadata endpoint.
    |
    */
    'auth' => [
        'bearer_token' => env('AGENT_BEARER_TOKEN'),

        'oauth' => [
            'enabled' => (bool) env('AGENT_OAUTH_ENABLED', false),

            // Base scope granted to every access token that reaches /mcp.
            // The static bearer bypasses scope checks entirely — it's the
            // operator credential.
            'scope' => env('AGENT_OAUTH_SCOPE', 'agent'),

            // Elevated scope that unlocks admin-only tools. Not grantable
            // via DCR — provision admin clients through Passport directly.
            // Set to null to disable admin OAuth entirely; admin stays
            // reachable via the static bearer in that case.
            'admin_scope' => env('AGENT_OAUTH_ADMIN_SCOPE', 'agent.admin'),

            // Reject OAuth requests that omit the RFC 8707 `resource`
            // parameter. Off by default to preserve the "single-resource
            // AS" shortcut; turn on once every known client sends it.
            'require_resource' => (bool) env('AGENT_OAUTH_REQUIRE_RESOURCE', false),

            // RFC 7591 Dynamic Client Registration endpoint. `rate_limit`
            // is "requests,minutes". `initial_access_token`, when non-empty,
            // flips DCR from open to authenticated — callers must present
            // the matching bearer. `max_clients` caps the total number of
            // DCR-registered clients to bound table growth.
            'dcr' => [
                'enabled' => (bool) env('AGENT_DCR_ENABLED', true),
                'rate_limit' => env('AGENT_DCR_RATE_LIMIT', '5,1'),
                'initial_access_token' => env('AGENT_DCR_INITIAL_ACCESS_TOKEN'),
                'max_clients' => (int) env('AGENT_DCR_MAX_CLIENTS', 50),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Monolog channel used by AgentLogSubscriber. Defaults to the app's
    | configured stack — consumers can route to a dedicated `agent` channel
    | by setting AGENT_LOG_CHANNEL and configuring it in `config/logging.php`.
    |
    */
    'log' => [
        'channel' => env('AGENT_LOG_CHANNEL', 'stack'),
    ],
];
