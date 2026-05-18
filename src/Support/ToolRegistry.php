<?php

declare(strict_types=1);

namespace InOtherAgents\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use InOtherAgents\Contracts\AgentToolContract;

final class ToolRegistry
{
    /** @var array<string, AgentToolContract> */
    private array $tools = [];

    public function __construct(
        private readonly Application $app,
    ) {
        foreach ($this->classes() as $class) {
            $instance = $this->app->make($class);
            $this->tools[$class::identifier()] = $instance;
        }
    }

    /** @return Collection<string, AgentToolContract> */
    public function all(): Collection
    {
        return collect($this->tools);
    }

    public function find(string $identifier): ?AgentToolContract
    {
        return $this->tools[$identifier] ?? null;
    }

    /**
     * The full tool class list, drawn from the consumer's `config('agents.tools')`.
     * This package ships no tools of its own — consumers register everything.
     *
     * @return array<int, class-string<AgentToolContract>>
     */
    public function classes(): array
    {
        /** @var array<int, class-string<AgentToolContract>> $consumerTools */
        $consumerTools = config('agents.tools', []);

        return array_values(array_filter($consumerTools, 'is_string'));
    }
}
