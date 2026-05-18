<?php

declare(strict_types=1);

namespace InOtherAgents\Events;

use Illuminate\Foundation\Events\Dispatchable;
use InOtherAgents\DTOs\ToolInvocation;

final class ToolInvocationFailed
{
    use Dispatchable;

    public function __construct(public readonly ToolInvocation $invocation) {}
}
