<?php

declare(strict_types=1);

namespace InOtherAgents\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use InOtherAgents\Events\DynamicClientRegistered;
use InOtherAgents\Events\ToolInvocationFailed;
use InOtherAgents\Events\ToolInvoked;

final class AgentLogSubscriber
{
    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            ToolInvoked::class => 'handleInvoked',
            ToolInvocationFailed::class => 'handleFailed',
            DynamicClientRegistered::class => 'handleDynamicClientRegistered',
        ];
    }

    public function handleInvoked(ToolInvoked $event): void
    {
        Log::channel($this->channel())->info('agent.tool.invoked', [
            'tool' => $event->invocation->tool,
            'input' => $event->invocation->redactedInput,
            'duration_ms' => $event->invocation->durationMs,
            'bearer_hash' => $event->invocation->bearerHash,
        ]);
    }

    public function handleFailed(ToolInvocationFailed $event): void
    {
        Log::channel($this->channel())->warning('agent.tool.failed', [
            'tool' => $event->invocation->tool,
            'input' => $event->invocation->redactedInput,
            'error' => $event->invocation->error,
            'duration_ms' => $event->invocation->durationMs,
            'bearer_hash' => $event->invocation->bearerHash,
        ]);
    }

    public function handleDynamicClientRegistered(DynamicClientRegistered $event): void
    {
        Log::channel($this->channel())->notice('agent.oauth.client_registered', [
            'client_id' => $event->clientId,
            'client_name' => $event->clientName,
            'redirect_uris' => $event->redirectUris,
            'confidential' => $event->isConfidential,
        ]);
    }

    private function channel(): string
    {
        return (string) config('agents.log.channel', 'stack');
    }
}
