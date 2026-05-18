# in-other-agents

Bearer-gated MCP (Streamable HTTP) scaffolding for Laravel apps. Provides:

- `AgentTool` abstract base — the single point of coupling to `opgginc/laravel-mcp-server`
- `AuthenticateAgent` middleware — static bearer token check, request stamping for log correlation
- `ToolRegistry` — resolves the consumer's `config('agents.tools')` list
- `AgentLogSubscriber` — audit logging of tool invocations to a Monolog channel
- Service provider wiring the `/mcp` route + middleware + log subscriber

This package ships zero tools of its own. Consumers register everything via `config/agents.php`.

## Status

Pre-stable, in active design. Currently consumed by `persistence-tool-box` via a Composer `type: path` symlink. Will be published to Packagist when stable enough to deploy; at that point `in-other-shops` (which has its own equivalent Agent domain) will be refactored to consume this package.

## Quick start in a consuming app

1. Add the path repo to `composer.json`:

   ```json
   "repositories": [
       {"type": "path", "url": "../in-other-agents", "options": {"symlink": true}}
   ]
   ```

2. Require the package:

   ```sh
   composer require jelte-ten-holt/in-other-agents:@dev
   ```

3. Set `AGENT_BEARER_TOKEN` in `.env` to any random string.

4. Register a tool by creating a class that extends `InOtherAgents\AgentTool` and adding it to `config/agents.php`:

   ```php
   'tools' => [
       App\Mcp\Tools\Ping::class,
   ],
   ```

5. POST to `/mcp` with `Authorization: Bearer <token>`.

## Auth roadmap

v1 is bearer-token-only. OAuth (Passport + Dynamic Client Registration) is planned for when this package is wired up to Co-work — the in-other-shops `Agent` domain has the reference implementation; lift it back in here when the migration happens.
