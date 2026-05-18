<?php

declare(strict_types=1);

namespace InOtherAgents\Contracts;

interface AgentToolContract
{
    public static function identifier(): string;

    public static function displayName(): string;

    public function description(): string;

    /** @return array<string, mixed> */
    public function inputSchema(): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function __invoke(array $arguments): array;

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function redactInput(array $arguments): array;
}
