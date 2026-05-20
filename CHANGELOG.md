# Changelog

All notable changes to `in-other-agents` are documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning follows [SemVer](https://semver.org/).

## [0.2.0] — 2026-05-20

OAuth 2.1 + Dynamic Client Registration, lifted back from the `in-other-shops` Agent domain. Bearer remains the default — OAuth is opt-in via `AGENT_OAUTH_ENABLED=true`. The two can coexist: Co-work goes through OAuth, Claude Code / curl stay on the bearer.

### Added

- **OAuth 2.1 access-token path** in `AuthenticateAgent` — Passport-issued bearer tokens validated via the `api` guard, scope-gated against `agents.auth.oauth.scope` / `agents.auth.oauth.admin_scope`. The static bearer fallback survives untouched for CLI clients.
- **RFC 9728 Protected Resource Metadata** at `/.well-known/oauth-protected-resource`. Advertised via `WWW-Authenticate: Bearer resource_metadata="…"` on 401s so OAuth-capable clients can discover the token endpoint without out-of-band config.
- **RFC 8414 Authorization Server Metadata** at `/.well-known/oauth-authorization-server`. Passport ships the endpoints but no discovery doc; we add it.
- **RFC 7591 Dynamic Client Registration** at `POST /oauth/register`. Validates redirect URIs, grant types, scopes; sanitises `client_name`; supports optional initial-access-token gating and a configurable max-clients cap. Throttled per `AGENT_DCR_RATE_LIMIT`.
- **RFC 8707 Resource Indicator enforcement** via `EnforceResourceParameter` — pushed onto `passport.middleware` when OAuth is enabled, validates `resource` against the canonical URL on every Passport route.
- **`Support\CanonicalUrl`** helper — resolves the externally-reachable MCP URL from `AGENT_CANONICAL_URL`, with fallback to `url()` for local dev. Used as both the RFC 9728 resource identifier and the OAuth issuer origin.
- **`Events\DynamicClientRegistered`** + a new handler in `AgentLogSubscriber` so DCR registrations land on the audit log.
- **Feature tests** for the metadata endpoints, resource-parameter enforcement, and bearer-path middleware (19 tests). End-to-end OAuth flow remains verified through the consumer's Passport setup.

### Config

New section `agents.auth.oauth`, all opt-in:

```
AGENT_OAUTH_ENABLED=true
AGENT_CANONICAL_URL=https://app.example.com/mcp
AGENT_OAUTH_SCOPE=agent
AGENT_OAUTH_ADMIN_SCOPE=agent.admin
AGENT_OAUTH_REQUIRE_RESOURCE=false
AGENT_DCR_ENABLED=true
AGENT_DCR_RATE_LIMIT=5,1
AGENT_DCR_INITIAL_ACCESS_TOKEN=
AGENT_DCR_MAX_CLIENTS=50
```

`laravel/passport` is now a `suggest` — required only when OAuth is enabled.

### Breaking changes

None for consumers that stay on the bearer path. Existing `agents.auth.bearer_token` config and behaviour are unchanged.

## [0.1.0] — 2026-05-20

Initial release. Extracted from the `in-other-shops` Agent domain to give Laravel apps a small, library-agnostic surface for exposing MCP (Streamable HTTP) tools.

### Added

- `AgentTool` abstract base — single point of coupling to `opgginc/laravel-mcp-server`. Tools extend this and implement `AgentToolContract`; library adaptation and event dispatch live here so tool subclasses stay library-neutral.
- `AuthenticateAgent` middleware — static bearer token check via `config('agents.auth.bearer_token')`. Stamps `agent.is_admin` and `agent.bearer_hash` on the request for downstream consumers.
- `ToolRegistry` — resolves the consumer's `config('agents.tools')` array into invokable tool instances.
- `AgentLogSubscriber` — audit logging of tool invocations to the configured Monolog channel. Bearer tokens are SHA-256-hashed (12-char prefix) before logging.
- `ToolInvoked` / `ToolInvocationFailed` events with `ToolInvocation` DTO.
- Service provider that wires the `/mcp` route (path configurable, throttle configurable), registers middleware, subscribes the log listener.
- `php artisan vendor:publish --tag=agents-config` to copy the default config into the consumer.

### Known limitations

- Bearer-token-only auth. OAuth (Passport + DCR) is on the roadmap for when a consumer needs Co-work reachability.
- No automated test coverage in this release — the surface has been verified in production via `in-other-worlds`' agent connector running an equivalent codepath. Tests will land alongside the first OAuth pass.
