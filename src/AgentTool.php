<?php

declare(strict_types=1);

namespace InOtherAgents;

use InOtherAgents\Contracts\AgentToolContract;
use InOtherAgents\DTOs\ToolInvocation;
use InOtherAgents\Events\ToolInvocationFailed;
use InOtherAgents\Events\ToolInvoked;
use InvalidArgumentException;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use Throwable;

/**
 * Bridge between AgentToolContract (our domain surface) and the upstream
 * opgginc/laravel-mcp-server ToolInterface (library surface). Tools extend
 * this and implement AgentToolContract; library adaptation + event dispatch
 * live here in a single place so tools stay library-agnostic.
 */
abstract class AgentTool implements AgentToolContract, ToolInterface
{
    public function name(): string
    {
        return static::identifier();
    }

    public function annotations(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array
    {
        $start = hrtime(true);
        $bearerHash = $this->currentBearerHash();
        $redacted = $this->redactInput($arguments);

        try {
            $output = ($this)($arguments);
        } catch (Throwable $e) {
            ToolInvocationFailed::dispatch(new ToolInvocation(
                tool: static::identifier(),
                redactedInput: $redacted,
                output: null,
                error: $e->getMessage(),
                durationMs: $this->elapsedMs($start),
                bearerHash: $bearerHash,
            ));

            // Shape errors → JSON-RPC INVALID_PARAMS so the calling agent
            // sees the actual message instead of a generic INTERNAL_ERROR.
            // Other throwables propagate untouched (the framework wraps
            // them as INTERNAL_ERROR, which is correct for unexpected
            // failures). Direct PHP callers (in-process, tests) bypass
            // execute() and still receive the original exception type.
            if ($e instanceof InvalidArgumentException) {
                throw new JsonRpcErrorException(
                    message: $e->getMessage(),
                    code: JsonRpcErrorCode::INVALID_PARAMS,
                );
            }

            throw $e;
        }

        ToolInvoked::dispatch(new ToolInvocation(
            tool: static::identifier(),
            redactedInput: $redacted,
            output: $output,
            error: null,
            durationMs: $this->elapsedMs($start),
            bearerHash: $bearerHash,
        ));

        return $output;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function redactInput(array $arguments): array
    {
        return $arguments;
    }

    /**
     * True when the current caller holds the static bearer. Fails closed when
     * the middleware hasn't stamped anything — in-process callers must opt-in
     * by stamping `agent.is_admin` themselves.
     */
    protected function isAdmin(): bool
    {
        return (bool) request()?->attributes->get('agent.is_admin', false);
    }

    private function elapsedMs(int $startHrtime): float
    {
        return (hrtime(true) - $startHrtime) / 1_000_000;
    }

    private function currentBearerHash(): ?string
    {
        $request = request();

        $hash = $request?->attributes->get('agent.bearer_hash');

        return is_string($hash) ? $hash : null;
    }
}
