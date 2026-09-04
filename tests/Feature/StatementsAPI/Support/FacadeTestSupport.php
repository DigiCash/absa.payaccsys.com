<?php

declare(strict_types=1);

namespace Tests\Feature\StatementsAPI\Support;

use App\Services\StatementsAPI\Contracts\OAuth2TokenManagerInterface;

/**
 * Hermetic double for {@see OAuth2TokenManagerInterface}: returns a fixed token
 * so inbound facade feature tests never perform a real OAuth2 / cache flow.
 */
final class StubOAuth2TokenManager implements OAuth2TokenManagerInterface
{
    public function __construct(
        private readonly string $token,
    ) {}

    public function getValidToken(): string
    {
        return $this->token;
    }
}

/**
 * Wiring helpers for the inbound facade feature tests.
 */
final class FacadeTestSupport
{
    /** Base URL the transport is pointed at inside feature tests. */
    public const string BASE_URL = 'https://statements.test/v1';

    /**
     * Wire the inbound facade stack for a feature test.
     *
     * Points the transport at a fake base URL, configures the static key the
     * transport resolves into `Authorization: Bearer {token}`, and replaces the
     * real credential manager with {@see StubOAuth2TokenManager}. Outbound calls
     * are then intercepted with `Http::fake()` inside each test.
     */
    public static function wire(string $token = 'facade-test-token'): StubOAuth2TokenManager
    {
        config()->set('absa.statements.base_url', self::BASE_URL);
        config()->set('absa.statements.api_key', $token);

        // Never touch mTLS material on disk inside feature tests.
        config()->set('absa.statements.cert_path', null);
        config()->set('absa.statements.key_path', null);
        config()->set('absa.statements.passphrase', null);

        $tokenManager = new StubOAuth2TokenManager($token);
        app()->instance(OAuth2TokenManagerInterface::class, $tokenManager);

        return $tokenManager;
    }
}
