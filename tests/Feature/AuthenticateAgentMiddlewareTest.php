<?php

declare(strict_types=1);

namespace InOtherAgents\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use InOtherAgents\Http\Middleware\AuthenticateAgent;
use InOtherAgents\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Bearer-path coverage. The OAuth path needs a Passport setup (keys,
 * client, scopes) and is verified end-to-end against the consumer app.
 * Here we lock the static-bearer contract: which attributes get stamped,
 * the shape of 401s, and that the WWW-Authenticate header flips between
 * `Bearer` and `Bearer resource_metadata="..."` depending on OAuth config.
 */
final class AuthenticateAgentMiddlewareTest extends TestCase
{
    private const string BEARER = 'middleware-test-bearer-xyz';

    private const string PROBE_PATH = '/agent-test/probe';

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('agents.auth.bearer_token', self::BEARER);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([AuthenticateAgent::class])
            ->get(self::PROBE_PATH, fn (Request $request): array => [
                'agent.user' => $request->attributes->get('agent.user'),
                'agent.scopes' => $request->attributes->get('agent.scopes'),
                'agent.is_admin' => $request->attributes->get('agent.is_admin'),
                'agent.bearer_hash' => $request->attributes->get('agent.bearer_hash'),
            ]);
    }

    #[Test]
    public function a_valid_bearer_request_reaches_the_route(): void
    {
        $this->getJson(self::PROBE_PATH, [
            'Authorization' => 'Bearer '.self::BEARER,
        ])->assertOk();
    }

    #[Test]
    public function a_valid_bearer_marks_the_caller_as_admin(): void
    {
        $body = $this->getJson(self::PROBE_PATH, [
            'Authorization' => 'Bearer '.self::BEARER,
        ])->assertOk()->json();

        $this->assertTrue($body['agent.is_admin']);
        $this->assertNull($body['agent.user']);
        $this->assertContains('agent', $body['agent.scopes']);
        $this->assertContains('agent.admin', $body['agent.scopes']);
    }

    #[Test]
    public function a_bearer_hash_is_stamped_for_log_correlation(): void
    {
        $body = $this->getJson(self::PROBE_PATH, [
            'Authorization' => 'Bearer '.self::BEARER,
        ])->assertOk()->json();

        $this->assertIsString($body['agent.bearer_hash']);
        $this->assertSame(12, strlen($body['agent.bearer_hash']));
    }

    #[Test]
    public function a_missing_authorization_header_returns_401(): void
    {
        $this->getJson(self::PROBE_PATH)
            ->assertStatus(401)
            ->assertJson(['error' => 'unauthorized'])
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    #[Test]
    public function a_wrong_bearer_returns_401(): void
    {
        $this->getJson(self::PROBE_PATH, [
            'Authorization' => 'Bearer not-the-right-token',
        ])->assertStatus(401)
            ->assertJson(['error' => 'unauthorized']);
    }

    #[Test]
    public function an_empty_bearer_token_config_locks_closed_even_for_an_empty_header(): void
    {
        config()->set('agents.auth.bearer_token', '');

        $this->getJson(self::PROBE_PATH, [
            'Authorization' => 'Bearer ',
        ])->assertStatus(401);
    }

    #[Test]
    public function when_oauth_is_enabled_the_401_advertises_resource_metadata_url(): void
    {
        config()->set('agents.auth.oauth.enabled', true);
        config()->set('agents.canonical_url', 'https://agent.example.test/mcp');

        $response = $this->getJson(self::PROBE_PATH)->assertStatus(401);

        $this->assertSame(
            'Bearer resource_metadata="https://agent.example.test/.well-known/oauth-protected-resource"',
            $response->headers->get('WWW-Authenticate'),
        );
    }
}
