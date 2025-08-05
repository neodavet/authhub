<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Application;
use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user and application
        $this->user = User::factory()->create([
            'name' => 'Token Test User',
            'email' => 'tokentest@example.com',
        ]);

        $this->application = Application::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Application',
            'allowed_scopes' => ['read', 'write', 'delete'],
            'is_active' => true,
        ]);
    }

    /**
     * Test: User can create API token for their application
     * 
     * This tests the complete API token creation flow:
     * - Token generation with proper hashing
     * - Scope validation
     * - Proper relationships
     * - Database storage
     */
    public function test_user_can_create_api_token_for_application(): void
    {
        // Arrange: Prepare token data
        $tokenData = [
            'name' => 'Mobile App Token',
            'abilities' => ['read', 'write'],
        ];

        // Act: Create API token
        $response = $this->actingAs($this->user)
            ->postJson("/api/applications/{$this->application->id}/tokens", $tokenData);

        // Assert: Token created successfully
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'plaintext_token', // Should include plaintext for one-time display
                'expires_at',
            ])
            ->assertJson([
                'message' => 'API token created successfully.',
            ]);

        // Verify in database
        $this->assertDatabaseHas('api_tokens', [
            'name' => 'Mobile App Token',
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Verify token hash is stored (not plaintext)
        $apiToken = ApiToken::where('name', 'Mobile App Token')->first();
        $this->assertNotNull($apiToken->token);
        $this->assertEquals(64, strlen($apiToken->token)); // SHA256 hash length
        $this->assertEquals(['read', 'write'], $apiToken->abilities);
    }

    /**
     * Test: User can create token with expiration date
     */
    public function test_user_can_create_token_with_expiration(): void
    {
        // Arrange: Token data with expiration
        $expirationDate = now()->addDays(30)->toDateTimeString();
        $tokenData = [
            'name' => 'Temporary Token',
            'abilities' => ['read'],
            'expires_at' => $expirationDate,
        ];

        // Act: Create token with expiration
        $response = $this->actingAs($this->user)
            ->postJson("/api/applications/{$this->application->id}/tokens", $tokenData);

        // Assert: Token created with expiration
        $response->assertStatus(201);

        // Verify expiration in database
        $this->assertDatabaseHas('api_tokens', [
            'name' => 'Temporary Token',
            'application_id' => $this->application->id,
        ]);

        $token = ApiToken::where('name', 'Temporary Token')->first();
        $this->assertNotNull($token->expires_at);
        $this->assertEquals(
            now()->addDays(30)->format('Y-m-d H:i'),
            $token->expires_at->format('Y-m-d H:i')
        );
    }

    /**
     * Test: Token creation validates scope permissions
     */
    public function test_token_creation_validates_scope_permissions(): void
    {
        // Arrange: Try to create token with unauthorized scope
        $tokenData = [
            'name' => 'Invalid Scope Token',
            'abilities' => ['read', 'admin'], // 'admin' not in application's allowed_scopes
        ];

        // Act: Attempt to create token
        $response = $this->actingAs($this->user)
            ->postJson("/api/applications/{$this->application->id}/tokens", $tokenData);

        // Assert: Validation error for unauthorized scope
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['abilities']);
    }

    /**
     * Test: User can list API tokens for their application
     */
    public function test_user_can_list_api_tokens_for_application(): void
    {
        // Arrange: Create multiple tokens
        $token1 = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'name' => 'Token One',
            'is_active' => true,
        ]);

        $token2 = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'name' => 'Token Two',
            'is_active' => false,
        ]);

        // Create token for different application (should not appear)
        $otherApp = Application::factory()->create(['user_id' => $this->user->id]);
        ApiToken::factory()->create([
            'application_id' => $otherApp->id,
            'user_id' => $this->user->id,
            'name' => 'Other App Token',
        ]);

        // Act: Get tokens for specific application
        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$this->application->id}/tokens");

        // Assert: Only application's tokens returned
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Token One'])
            ->assertJsonFragment(['name' => 'Token Two'])
            ->assertJsonMissing(['name' => 'Other App Token']);
    }

    /**
     * Test: User can revoke API token
     */
    public function test_user_can_revoke_api_token(): void
    {
        // Arrange: Create active token
        $token = ApiToken::factory()->active()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'name' => 'Token to Revoke',
        ]);

        // Act: Revoke token
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/api-tokens/{$token->id}");

        // Assert: Token revoked successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'API token revoked successfully.'
            ]);

        // Verify token is deactivated in database
        $this->assertDatabaseHas('api_tokens', [
            'id' => $token->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test: User cannot access tokens from other users' applications
     */
    public function test_user_cannot_access_other_users_tokens(): void
    {
        // Arrange: Create token for different user's application
        $otherUser = User::factory()->create();
        $otherApplication = Application::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        $otherToken = ApiToken::factory()->create([
            'application_id' => $otherApplication->id,
            'user_id' => $otherUser->id,
        ]);

        // Act & Assert: Try to access other user's tokens
        $this->actingAs($this->user)
            ->getJson("/api/applications/{$otherApplication->id}/tokens")
            ->assertStatus(403);

        $this->actingAs($this->user)
            ->deleteJson("/api/api-tokens/{$otherToken->id}")
            ->assertStatus(403);
    }

    /**
     * Test: API token usage tracking
     */
    public function test_api_token_usage_tracking(): void
    {
        // Arrange: Create token
        $token = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'last_used_at' => null,
        ]);

        // Act: Mark token as used (simulating API usage)
        $token->markAsUsed();

        // Assert: Usage timestamp updated
        $token->refresh();
        $this->assertNotNull($token->last_used_at);
        $this->assertTrue($token->last_used_at->isToday());
    }

    /**
     * Test: Token expiration checking
     */
    public function test_token_expiration_checking(): void
    {
        // Arrange: Create expired and non-expired tokens
        $expiredToken = ApiToken::factory()->expired()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $validToken = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $neverExpiresToken = ApiToken::factory()->neverExpires()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        // Assert: Expiration status correctly identified
        $this->assertTrue($expiredToken->isExpired());
        $this->assertFalse($validToken->isExpired());
        $this->assertFalse($neverExpiresToken->isExpired());
    }

    /**
     * Test: Token ability checking
     */
    public function test_token_ability_checking(): void
    {
        // Arrange: Create tokens with different abilities
        $readOnlyToken = ApiToken::factory()->withAbilities(['read'])->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $adminToken = ApiToken::factory()->withAbilities(['*'])->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $multipleAbilitiesToken = ApiToken::factory()->withAbilities(['read', 'write'])->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        // Assert: Ability checking works correctly
        $this->assertTrue($readOnlyToken->can('read'));
        $this->assertFalse($readOnlyToken->can('write'));
        $this->assertFalse($readOnlyToken->can('delete'));

        $this->assertTrue($adminToken->can('read'));
        $this->assertTrue($adminToken->can('write'));
        $this->assertTrue($adminToken->can('delete'));
        $this->assertTrue($adminToken->can('admin'));

        $this->assertTrue($multipleAbilitiesToken->can('read'));
        $this->assertTrue($multipleAbilitiesToken->can('write'));
        $this->assertFalse($multipleAbilitiesToken->can('delete'));
    }

    /**
     * Test: Token relationships work correctly
     */
    public function test_token_relationships(): void
    {
        // Arrange: Create token with relationships
        $token = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        // Act: Load relationships
        $token->load(['user', 'application']);

        // Assert: Relationships loaded correctly
        $this->assertEquals($this->user->id, $token->user->id);
        $this->assertEquals($this->user->name, $token->user->name);
        $this->assertEquals($this->application->id, $token->application->id);
        $this->assertEquals($this->application->name, $token->application->name);
    }

    /**
     * Test: Application has many API tokens relationship
     */
    public function test_application_has_many_tokens_relationship(): void
    {
        // Arrange: Create multiple tokens for application
        $token1 = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $token2 = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        // Act: Load relationship
        $this->application->load('apiTokens');

        // Assert: Application has correct tokens
        $this->assertCount(2, $this->application->apiTokens);
        $this->assertTrue($this->application->apiTokens->contains($token1));
        $this->assertTrue($this->application->apiTokens->contains($token2));
    }

    /**
     * Test: User has many API tokens relationship
     */
    public function test_user_has_many_tokens_relationship(): void
    {
        // Arrange: Create tokens for user across different applications
        $app2 = Application::factory()->create(['user_id' => $this->user->id]);
        
        $token1 = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $token2 = ApiToken::factory()->create([
            'application_id' => $app2->id,
            'user_id' => $this->user->id,
        ]);

        // Act: Load relationship
        $this->user->load('apiTokens');

        // Assert: User has correct tokens
        $this->assertCount(2, $this->user->apiTokens);
        $this->assertTrue($this->user->apiTokens->contains($token1));
        $this->assertTrue($this->user->apiTokens->contains($token2));
    }

    /**
     * Test: Active tokens scope works correctly
     */
    public function test_active_tokens_scope(): void
    {
        // Arrange: Create active and inactive tokens
        $activeToken = ApiToken::factory()->active()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $inactiveToken = ApiToken::factory()->inactive()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        // Act: Query active tokens
        $activeTokens = ApiToken::active()->get();

        // Assert: Only active tokens returned
        $this->assertTrue($activeTokens->contains($activeToken));
        $this->assertFalse($activeTokens->contains($inactiveToken));
    }

    /**
     * Test: Non-expired tokens scope works correctly
     */
    public function test_not_expired_tokens_scope(): void
    {
        // Arrange: Create expired and valid tokens
        $expiredToken = ApiToken::factory()->expired()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        $validToken = ApiToken::factory()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $neverExpiresToken = ApiToken::factory()->neverExpires()->create([
            'application_id' => $this->application->id,
            'user_id' => $this->user->id,
        ]);

        // Act: Query non-expired tokens
        $nonExpiredTokens = ApiToken::notExpired()->get();

        // Assert: Only non-expired tokens returned
        $this->assertFalse($nonExpiredTokens->contains($expiredToken));
        $this->assertTrue($nonExpiredTokens->contains($validToken));
        $this->assertTrue($nonExpiredTokens->contains($neverExpiresToken));
    }
}