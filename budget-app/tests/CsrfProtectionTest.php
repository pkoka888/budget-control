<?php
use PHPUnit\Framework\TestCase;
use BudgetApp\Middleware\CsrfProtection;

class CsrfProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        // Start a clean session for each test
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER = [];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_POST = [];
        $_SERVER = [];
    }

    public function testTokenGenerationCreatesValidToken(): void
    {
        $token = CsrfProtection::generateToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertTrue(ctype_xdigit($token)); // All hexadecimal characters
        $this->assertArrayHasKey('csrf_token', $_SESSION);
    }

    public function testTokenGenerationReturnsExistingToken(): void
    {
        $token1 = CsrfProtection::generateToken();
        $token2 = CsrfProtection::generateToken();

        $this->assertEquals($token1, $token2);
    }

    public function testValidTokenPassesValidation(): void
    {
        $token = CsrfProtection::generateToken();

        $this->assertTrue(CsrfProtection::validateToken($token));
    }

    public function testInvalidTokenFailsValidation(): void
    {
        CsrfProtection::generateToken(); // Generate a valid token
        $fakeToken = bin2hex(random_bytes(32)); // Create a different token

        $this->assertFalse(CsrfProtection::validateToken($fakeToken));
    }

    public function testEmptyTokenFailsValidation(): void
    {
        CsrfProtection::generateToken();

        $this->assertFalse(CsrfProtection::validateToken(''));
    }

    public function testNullTokenFailsValidation(): void
    {
        CsrfProtection::generateToken();

        $this->assertFalse(CsrfProtection::validateToken(null));
    }

    public function testTokenValidationWithoutSessionTokenFails(): void
    {
        $token = bin2hex(random_bytes(32));

        $this->assertFalse(CsrfProtection::validateToken($token));
    }

    public function testTokenRegenerationCreatesNewToken(): void
    {
        $token1 = CsrfProtection::generateToken();
        CsrfProtection::regenerateToken();
        $token2 = CsrfProtection::getToken();

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token2));
    }

    public function testGetTokenReturnsGeneratedToken(): void
    {
        $token = CsrfProtection::generateToken();
        $retrievedToken = CsrfProtection::getToken();

        $this->assertEquals($token, $retrievedToken);
    }

    public function testGetTokenGeneratesTokenIfNotExists(): void
    {
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);

        $token = CsrfProtection::getToken();

        $this->assertNotEmpty($token);
        $this->assertArrayHasKey('csrf_token', $_SESSION);
    }

    public function testMetaTagGeneratesValidHtml(): void
    {
        $token = CsrfProtection::generateToken();
        $metaTag = CsrfProtection::metaTag();

        $this->assertStringContainsString('<meta name="csrf-token"', $metaTag);
        $this->assertStringContainsString('content="' . $token . '"', $metaTag);
    }

    public function testFieldGeneratesValidHiddenInput(): void
    {
        $token = CsrfProtection::generateToken();
        $field = CsrfProtection::field();

        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="' . $token . '"', $field);
    }

    public function testRequireTokenPassesForGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Should not throw exception or exit for GET requests
        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRequireTokenValidatesPostRequestWithValidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CsrfProtection::generateToken();
        $_POST['csrf_token'] = $token;

        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        // Should not produce output for valid token
        $this->assertEmpty($output);
    }

    public function testRequireTokenRejectsPostRequestWithInvalidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        CsrfProtection::generateToken();
        $_POST['csrf_token'] = 'invalid_token';

        ob_start();
        try {
            CsrfProtection::requireToken();
            $this->fail('Expected exit() to be called');
        } catch (\Exception $e) {
            // Exit might throw exception in testing environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid or missing CSRF token', $output);
    }

    public function testRequireTokenRejectsPostRequestWithMissingToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        CsrfProtection::generateToken();
        // $_POST['csrf_token'] not set

        ob_start();
        try {
            CsrfProtection::requireToken();
            $this->fail('Expected exit() to be called');
        } catch (\Exception $e) {
            // Exit might throw exception in testing environment
        }
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid or missing CSRF token', $output);
    }

    public function testRequireTokenAcceptsTokenFromHttpHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = CsrfProtection::generateToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRequireTokenPrefersPostOverHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $validToken = CsrfProtection::generateToken();
        $_POST['csrf_token'] = $validToken;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'wrong_token';

        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        // Should succeed because POST token is valid
        $this->assertEmpty($output);
    }

    public function testRequireTokenValidatesPutRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $token = CsrfProtection::generateToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRequireTokenValidatesPatchRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $token = CsrfProtection::generateToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRequireTokenValidatesDeleteRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $token = CsrfProtection::generateToken();
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        ob_start();
        CsrfProtection::requireToken();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testTimingAttackResistance(): void
    {
        $token = CsrfProtection::generateToken();

        // Time validation with correct token
        $startValid = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            CsrfProtection::validateToken($token);
        }
        $validTime = microtime(true) - $startValid;

        // Time validation with incorrect token
        $wrongToken = bin2hex(random_bytes(32));
        $startInvalid = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            CsrfProtection::validateToken($wrongToken);
        }
        $invalidTime = microtime(true) - $startInvalid;

        // The time difference should be minimal (< 50% difference)
        // This tests for constant-time comparison
        $ratio = max($validTime, $invalidTime) / min($validTime, $invalidTime);
        $this->assertLessThan(1.5, $ratio,
            'Token validation timing differs significantly between valid and invalid tokens');
    }

    public function testTokenEntropyIsHighQuality(): void
    {
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            CsrfProtection::regenerateToken();
            $tokens[] = CsrfProtection::getToken();
        }

        // All tokens should be unique
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(100, $uniqueTokens);

        // Test for predictable patterns (no runs of same character)
        foreach ($tokens as $token) {
            $this->assertDoesNotMatchRegularExpression(
                '/(.)\1{4,}/', // No character repeated 5+ times
                $token,
                'Token contains predictable patterns'
            );
        }
    }

    public function testConcurrentTokenGeneration(): void
    {
        // Simulate multiple token generations
        $token1 = CsrfProtection::generateToken();
        $validated1 = CsrfProtection::validateToken($token1);

        // Regenerate token
        CsrfProtection::regenerateToken();
        $token2 = CsrfProtection::getToken();

        // Old token should no longer be valid
        $validated1After = CsrfProtection::validateToken($token1);
        $validated2 = CsrfProtection::validateToken($token2);

        $this->assertTrue($validated1);
        $this->assertFalse($validated1After);
        $this->assertTrue($validated2);
    }

    public function testTokenDoesNotLeakInErrorMessages(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        CsrfProtection::generateToken();
        $_POST['csrf_token'] = 'invalid';

        ob_start();
        try {
            CsrfProtection::requireToken();
        } catch (\Exception $e) {
            // Catch exit
        }
        $output = ob_get_clean();

        // Error message should not contain the actual token
        $this->assertStringNotContainsString($_SESSION['csrf_token'], $output);
    }
}
