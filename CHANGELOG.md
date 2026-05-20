# Changelog

All notable changes to `in-other-agents` are documented here. Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning follows [SemVer](https://semver.org/).

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
