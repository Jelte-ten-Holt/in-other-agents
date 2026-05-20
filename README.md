# in-other-agents

MCP (Streamable HTTP) scaffolding for Laravel apps. Provides:

- `AgentTool` abstract base — the single point of coupling to `opgginc/laravel-mcp-server`
- `AuthenticateAgent` middleware — bearer **and** OAuth 2.1 (Passport) token check, request stamping for log correlation
- OAuth discovery + RFC 7591 Dynamic Client Registration so Co-work / web MCP clients can self-onboard
- `ToolRegistry` — resolves the consumer's `config('agents.tools')` list
- `AgentLogSubscriber` — audit logging of tool invocations and DCR registrations to a Monolog channel
- Service provider wiring the `/mcp` route, OAuth routes, middleware, and log subscriber

This package ships zero tools of its own. Consumers register everything via `config/agents.php`.

## Install

```sh
composer require jelte-ten-holt/in-other-agents
```

Laravel package discovery picks up the service provider automatically.

Publish the config (optional — defaults work out of the box):

```sh
php artisan vendor:publish --tag=agents-config
```

Set the bearer token in `.env`:

```
AGENT_BEARER_TOKEN=some-long-random-string
```

An empty token fails closed — the `/mcp` endpoint rejects everything until a token is set.

## Defining a tool

Create a class that extends `InOtherAgents\AgentTool`:

```php
namespace App\Mcp\Tools;

use InOtherAgents\AgentTool;

final class Ping extends AgentTool
{
    public static function identifier(): string
    {
        return 'ping';
    }

    public static function displayName(): string
    {
        return 'Ping';
    }

    public function description(): string
    {
        return 'Health check — returns pong.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
        ];
    }

    public function __invoke(array $arguments): array
    {
        return ['result' => 'pong'];
    }
}
```

Register it in `config/agents.php`:

```php
'tools' => [
    App\Mcp\Tools\Ping::class,
],
```

Then POST to `/mcp` with `Authorization: Bearer <token>` and a standard MCP JSON-RPC envelope.

## Audit log

Every tool invocation dispatches `InOtherAgents\Events\ToolInvoked` (success) or `ToolInvocationFailed` (throw). The bundled `AgentLogSubscriber` writes them to the configured Monolog channel (`AGENT_LOG_CHANNEL`, defaults to `stack`). Bearer tokens are hashed before logging — never the raw value.

Errors thrown as `InvalidArgumentException` are translated to JSON-RPC `INVALID_PARAMS` so the calling agent sees the actual message. Other throwables propagate as the framework's `INTERNAL_ERROR`.

## OAuth (since 0.2.0)

Bearer remains the default. To unlock OAuth for Co-work / web MCP clients:

1. `composer require laravel/passport`
2. `php artisan passport:install` (generates encryption keys + initial clients)
3. Configure the `api` guard in `config/auth.php`:
   ```php
   'guards' => [
       'api' => ['driver' => 'passport', 'provider' => 'users'],
   ],
   ```
4. In `.env`:
   ```
   AGENT_OAUTH_ENABLED=true
   AGENT_CANONICAL_URL=https://your-app.example.com/mcp
   ```

What you get:

- `POST /oauth/register` — RFC 7591 Dynamic Client Registration. Co-work posts here on first connect when the connector form is left with blank OAuth fields. Open by default; set `AGENT_DCR_INITIAL_ACCESS_TOKEN` to gate it.
- `GET /.well-known/oauth-authorization-server` — RFC 8414 discovery doc.
- `GET /.well-known/oauth-protected-resource` — RFC 9728 doc. Advertised in `WWW-Authenticate` on every 401 from `/mcp`.
- RFC 8707 `resource` parameter enforcement on every Passport route (off by default — flip `AGENT_OAUTH_REQUIRE_RESOURCE=true` once every known client sends it).
- Scope-gated access (`agent` base, `agent.admin` elevated). Static bearer holders are admin by construction; that path is untouched.

End-to-end OAuth flow is verified via the consumer app's Passport setup; the package's test suite covers the metadata endpoints, resource enforcement, and the bearer path.

## Versioning

[Semantic Versioning](https://semver.org/). Pre-1.0, the surface may shift between minor versions while consumers iterate.
