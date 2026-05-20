<?php

declare(strict_types=1);

namespace InOtherAgents;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InOtherAgents\Http\Middleware\AuthenticateAgent;
use InOtherAgents\Listeners\AgentLogSubscriber;
use InOtherAgents\Support\ToolRegistry;

final class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/agents.php', 'agents');

        $this->app->singleton(ToolRegistry::class);
    }

    public function boot(): void
    {
        // Route registration is deferred to `app.booted` because the `/mcp`
        // route file calls the `Route::mcp(...)` macro from opgginc/laravel-
        // -mcp-server, and that macro is installed in the opgginc provider's
        // own `boot()`. Composer package-discovery boot order is alphabetical,
        // so `jelte-ten-holt/in-other-agents` boots before `opgginc/*` in any
        // consuming app — registering directly in boot() would race the macro.
        $this->app->booted(function (): void {
            if (config('agents.route.enabled', true)) {
                $this->registerMcpRoute();
            }
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/agents.php' => config_path('agents.php'),
            ], 'agents-config');
        }

        Event::subscribe(AgentLogSubscriber::class);
    }

    private function registerMcpRoute(): void
    {
        Route::middleware([AuthenticateAgent::class])
            ->group(__DIR__.'/Routes/mcp.php');
    }
}
