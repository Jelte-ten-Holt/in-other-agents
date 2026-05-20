<?php

declare(strict_types=1);

namespace InOtherAgents\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after RFC 7591 Dynamic Client Registration creates a new OAuth
 * client. The bundled `AgentLogSubscriber` logs it on the agent channel;
 * consumers can subscribe additional listeners (Slack alerts, ops paging,
 * etc.) without forking the controller.
 */
final readonly class DynamicClientRegistered
{
    use Dispatchable;

    /**
     * @param  array<int, string>  $redirectUris
     */
    public function __construct(
        public string $clientId,
        public string $clientName,
        public array $redirectUris,
        public bool $isConfidential,
    ) {}
}
