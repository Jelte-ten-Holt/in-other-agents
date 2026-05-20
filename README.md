# in-other-agents

Bearer-gated MCP (Streamable HTTP) scaffolding for Laravel apps. Provides:

- `AgentTool` abstract base — the single point of coupling to `opgginc/laravel-mcp-server`
- `AuthenticateAgent` middleware — static bearer token check, request stamping for log correlation
- `ToolRegistry` — resolves the consumer's `config('agents.tools')` list
- `AgentLogSubscriber` — audit logging of tool invocations to a Monolog channel
- Service provider wiring the `/mcp` route + middleware + log subscriber

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

## Auth roadmap

v0.1 is bearer-token-only. OAuth (Passport + Dynamic Client Registration) is planned for when a consumer needs Co-work reachability — the `in-other-shops` Agent domain has a reference implementation that will get lifted back into this package at that point.

## Versioning

[Semantic Versioning](https://semver.org/). Pre-1.0, the surface may shift between minor versions while consumers iterate.
