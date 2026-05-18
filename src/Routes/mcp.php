<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use InOtherAgents\Support\ToolRegistry;
use OPGG\LaravelMcpServer\Enums\ProtocolVersion;

Route::middleware('throttle:'.config('agents.route.throttle', '60,1'))->group(function (): void {
    Route::mcp(config('agents.route.path', '/mcp'))
        ->setServerInfo(
            name: config('agents.server.name'),
            version: config('agents.server.version'),
        )
        ->setProtocolVersion(ProtocolVersion::V2025_11_25)
        ->tools(app(ToolRegistry::class)->classes());
});
