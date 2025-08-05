<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApplicationManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for all tests
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Test: User can create a new application
     * 
     * This tests the complete application creation flow including:
     * - Form validation
     * - Database insertion
     * - Automatic generation of client_id and client_secret
     * - Proper relationships
     */
    public function test_authenticated_user_can_create_application(): void
    {
        // Arrange: Prepare test data
        $applicationData = [
            'name' => 'My Test Application',
            'description' => 'This is a test application for API access',
            'callback_urls' => [
                'https://example.com/callback',
                'https://myapp.com/auth/callback'
            ],
            'allowed_scopes' => ['read', 'write'],
            'rate_limit' => 2000,
        ];

        // Act: Make authenticated request to create application
        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', $applicationData);

        // Assert: Check response and database
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'application' => [
                    'id',
                    'name',
                    'description',
                    'client_id',
                    'allowed_scopes',
                    'rate_limit',
                    'callback_urls',
                    'is_active',
                    'user_id',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJson([
                'message' => 'Application created successfully.',
                'application' => [
                    'name' => 'My Test Application',
                    'description' => 'This is a test application for API access',
                    'is_active' => true,
                    'user_id' => $this->user->id,
                ]
            ]);

        // Verify in database
        $this->assertDatabaseHas('applications', [
            'name' => 'My Test Application',
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Verify client_id and client_secret were generated
        $application = Application::where('name', 'My Test Application')->first();
        $this->assertNotNull($application->client_id);
        $this->assertNotNull($application->client_secret);
        $this->assertIsString($application->client_id);
        $this->assertIsString($application->client_secret);
        $this->assertEquals(36, strlen($application->client_id)); // UUID length
        $this->assertEquals(64, strlen($application->client_secret)); // Secret length
    }

    /**
     * Test: Application creation validates required fields
     */
    public function test_application_creation_validates_required_fields(): void
    {
        // Act: Try to create application without required fields
        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', []);

        // Assert: Validation errors
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Application creation validates callback URLs
     */
    public function test_application_creation_validates_callback_urls(): void
    {
        // Act: Try to create application with invalid callback URLs
        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', [
                'name' => 'Test App',
                'callback_urls' => [
                    'not-a-valid-url',
                    'https://valid-url.com'
                ]
            ]);

        // Assert: Validation errors for invalid URL
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['callback_urls.0']);
    }

    /**
     * Test: User can view their applications list
     */
    public function test_user_can_view_their_applications(): void
    {
        // Arrange: Create test applications
        $app1 = Application::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'First App',
        ]);
        
        $app2 = Application::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Second App',
        ]);

        // Create application for different user (should not appear)
        $otherUser = User::factory()->create();
        Application::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other User App',
        ]);

        // Act: Get applications list
        $response = $this->actingAs($this->user)
            ->getJson('/api/applications');

        // Assert: Only user's applications are returned
        $response->assertStatus(200)
            ->assertJsonCount(2, 'applications.data')
            ->assertJsonFragment(['name' => 'First App'])
            ->assertJsonFragment(['name' => 'Second App'])
            ->assertJsonMissing(['name' => 'Other User App']);
    }

    /**
     * Test: User can view specific application details
     */
    public function test_user_can_view_application_details(): void
    {
        // Arrange: Create test application
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Detailed Test App',
            'description' => 'Detailed description',
        ]);

        // Act: Get application details
        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$application->id}");

        // Assert: Application details returned
        $response->assertStatus(200)
            ->assertJson([
                'application' => [
                    'id' => $application->id,
                    'name' => 'Detailed Test App',
                    'description' => 'Detailed description',
                    'user_id' => $this->user->id,
                ]
            ]);
    }

    /**
     * Test: User cannot view other user's applications
     */
    public function test_user_cannot_view_other_users_applications(): void
    {
        // Arrange: Create application for different user
        $otherUser = User::factory()->create();
        $otherApplication = Application::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act: Try to view other user's application
        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$otherApplication->id}");

        // Assert: Access denied
        $response->assertStatus(403);
    }

    /**
     * Test: User can update their application
     */
    public function test_user_can_update_their_application(): void
    {
        // Arrange: Create test application
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
            'description' => 'Original Description',
        ]);

        // Act: Update application
        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'rate_limit' => 5000,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/applications/{$application->id}", $updateData);

        // Assert: Application updated successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Application updated successfully.',
                'application' => [
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                    'rate_limit' => 5000,
                ]
            ]);

        // Verify in database
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'rate_limit' => 5000,
        ]);
    }

    /**
     * Test: User can delete their application
     */
    public function test_user_can_delete_their_application(): void
    {
        // Arrange: Create test application
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act: Delete application
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/applications/{$application->id}");

        // Assert: Application deleted successfully
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Application deleted successfully.'
            ]);

        // Verify application is deleted from database
        $this->assertDatabaseMissing('applications', [
            'id' => $application->id,
        ]);
    }

    /**
     * Test: User can regenerate application client secret
     */
    public function test_user_can_regenerate_client_secret(): void
    {
        // Arrange: Create test application
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
        ]);
        $originalSecret = $application->client_secret;

        // Act: Regenerate client secret
        $response = $this->actingAs($this->user)
            ->postJson("/api/applications/{$application->id}/regenerate-secret");

        // Assert: New secret generated
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Client secret regenerated successfully.',
            ])
            ->assertJsonStructure([
                'client_secret'
            ]);

        // Verify new secret is different and saved
        $application->refresh();
        $this->assertNotEquals($originalSecret, $application->client_secret);
        $this->assertEquals(64, strlen($application->client_secret));
    }

    /**
     * Test: User can toggle application status
     */
    public function test_user_can_toggle_application_status(): void
    {
        // Arrange: Create active application
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Act: Toggle to inactive
        $response = $this->actingAs($this->user)
            ->patchJson("/api/applications/{$application->id}/toggle-status");

        // Assert: Status toggled
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Application deactivated successfully.',
                'is_active' => false,
            ]);

        // Verify in database
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'is_active' => false,
        ]);

        // Act: Toggle back to active
        $response = $this->actingAs($this->user)
            ->patchJson("/api/applications/{$application->id}/toggle-status");

        // Assert: Status toggled back
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Application activated successfully.',
                'is_active' => true,
            ]);
    }

    /**
     * Test: Unauthenticated users cannot access application endpoints
     */
    public function test_unauthenticated_users_cannot_access_applications(): void
    {
        // Act & Assert: Test various endpoints without authentication
        $this->getJson('/api/applications')
            ->assertStatus(401);

        $this->postJson('/api/applications', ['name' => 'Test'])
            ->assertStatus(401);

        $application = Application::factory()->create();
        
        $this->getJson("/api/applications/{$application->id}")
            ->assertStatus(401);

        $this->putJson("/api/applications/{$application->id}", ['name' => 'Updated'])
            ->assertStatus(401);

        $this->deleteJson("/api/applications/{$application->id}")
            ->assertStatus(401);
    }

    /**
     * Test: Application has proper relationships with user
     */
    public function test_application_belongs_to_user(): void
    {
        // Arrange: Create application
        $application = Application::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act: Load relationship
        $application->load('user');

        // Assert: Relationship works correctly
        $this->assertEquals($this->user->id, $application->user->id);
        $this->assertEquals($this->user->name, $application->user->name);
        $this->assertEquals($this->user->email, $application->user->email);
    }

    /**
     * Test: User has proper relationships with applications
     */
    public function test_user_has_many_applications(): void
    {
        // Arrange: Create multiple applications for user
        $app1 = Application::factory()->create(['user_id' => $this->user->id]);
        $app2 = Application::factory()->create(['user_id' => $this->user->id]);

        // Act: Load relationships
        $this->user->load('applications');

        // Assert: User has correct applications
        $this->assertCount(2, $this->user->applications);
        $this->assertTrue($this->user->applications->contains($app1));
        $this->assertTrue($this->user->applications->contains($app2));
    }
}