<?php

declare(strict_types=1);

namespace InOtherAgents;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InOtherAgents\Http\Middleware\AuthenticateAgent;
use InOtherAgents\Http\Middleware\EnforceResourceParameter;
use InOtherAgents\Listeners\AgentLogSubscriber;
use InOtherAgents\Support\ToolRegistry;

final class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/agents.php', 'agents');

        $this->app->singleton(ToolRegistry::class);

        if ((bool) config('agents.auth.oauth.enabled', false)) {
            // Attach our RFC 8707 resource-parameter validator to every
            // Passport route (/oauth/authorize, /oauth/token, etc.).
            // Passport reads `passport.middleware` when it registers its
            // routes in its own boot(); our register runs first under the
            // default alphabetical discovery order, so this push is in
            // place by the time Passport needs it.
            $existing = (array) config('passport.middleware', []);
            if (! in_array(EnforceResourceParameter::class, $existing, true)) {
                $existing[] = EnforceResourceParameter::class;
            }
            config(['passport.middleware' => $existing]);
        }
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

            if ((bool) config('agents.auth.oauth.enabled', false)) {
                $this->registerOauthRoutes();
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

    private function registerOauthRoutes(): void
    {
        Route::group([], __DIR__.'/Routes/oauth.php');
    }
}
