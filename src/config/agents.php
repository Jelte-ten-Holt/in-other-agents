<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tools
    |--------------------------------------------------------------------------
    |
    | The consumer's tool class list. Each entry is the fully qualified class
    | name of an AgentTool. The order here is the order in which tools are
    | advertised to MCP clients via tools/list.
    |
    */
    'tools' => [],

    /*
    |--------------------------------------------------------------------------
    | Route
    |--------------------------------------------------------------------------
    */
    'route' => [
        'enabled' => true,
        'path' => env('AGENT_ROUTE_PATH', '/mcp'),
        'throttle' => env('AGENT_ROUTE_THROTTLE', '60,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server identity
    |--------------------------------------------------------------------------
    |
    | Advertised to MCP clients in the initialize response.
    |
    */
    'server' => [
        'name' => env('AGENT_SERVER_NAME', 'In Other Agents'),
        'version' => env('AGENT_SERVER_VERSION', '0.1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | v1: static bearer token only. Set AGENT_BEARER_TOKEN in .env. An empty
    | token fails closed — the /mcp endpoint rejects everything until set.
    |
    */
    'auth' => [
        'bearer_token' => env('AGENT_BEARER_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Monolog channel used by AgentLogSubscriber. Defaults to the app's
    | configured stack — consumers can route to a dedicated `agent` channel
    | by setting AGENT_LOG_CHANNEL and configuring it in `config/logging.php`.
    |
    */
    'log' => [
        'channel' => env('AGENT_LOG_CHANNEL', 'stack'),
    ],
];
