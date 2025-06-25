<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user can register with valid data
     * This tests the POST /api/register endpoint
     */
    public function test_user_can_register_with_valid_data()
    {
        // Arrange: Prepare registration data
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        // Act: Register user
        $response = $this->postJson('/api/register', $userData);

        // Assert: Check response and database
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email']
            ])
            ->assertJson([
                'message' => 'Registration successful',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com'
                ]
            ]);

        // Check database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Check that password is hashed
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * Test that user cannot register with invalid email
     * This tests email validation
     */
    public function test_user_cannot_register_with_invalid_email()
    {
        // Arrange: Prepare invalid registration data
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        // Act: Try to register
        $response = $this->postJson('/api/register', $userData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that user cannot register with duplicate email
     * This tests unique email validation
     */
    public function test_user_cannot_register_with_duplicate_email()
    {
        // Arrange: Create existing user
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        // Act: Try to register with same email
        $response = $this->postJson('/api/register', $userData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that user can login with valid credentials
     * This tests the POST /api/login endpoint
     */
    public function test_user_can_login_with_valid_credentials()
    {
        // Arrange: Create user with known credentials
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // Act: Login
        $response = $this->postJson('/api/login', $loginData);

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email']
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        // Check that user is authenticated
        $this->assertAuthenticated();
    }

    /**
     * Test that user cannot login with invalid credentials
     * This tests invalid login attempts
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        // Arrange: Create user
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // Act: Try to login with wrong password
        $response = $this->postJson('/api/login', $loginData);

        // Assert: Login failed
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);

        // Check that user is not authenticated
        $this->assertGuest();
    }

    /**
     * Test that user cannot login with non-existent email
     * This tests login with unknown email
     */
    public function test_user_cannot_login_with_nonexistent_email()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        // Act: Try to login with non-existent email
        $response = $this->postJson('/api/login', $loginData);

        // Assert: Login failed
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);

        $this->assertGuest();
    }

    /**
     * Test that user can logout when authenticated
     * This tests the POST /api/logout endpoint
     */
    public function test_user_can_logout_when_authenticated()
    {
        // Arrange: Create and authenticate user
        $user = User::factory()->create();

        // Act: Logout
        $response = $this->actingAs($user)
            ->postJson('/api/logout');

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout successful'
            ]);

        // Check that user is no longer authenticated
        $this->assertGuest();
    }

    /**
     * Test that user can get their profile information
     * This tests the GET /api/user endpoint
     */
    public function test_user_can_get_profile_information()
    {
        // Arrange: Create and authenticate user
        $user = User::factory()->create();

        // Act: Get user profile
        $response = $this->actingAs($user)
            ->getJson('/api/user');

        // Assert: Check response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email']
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
    }

    /**
     * Test that unauthenticated user cannot access protected endpoints
     * This tests authentication middleware
     */
    public function test_unauthenticated_user_cannot_access_protected_endpoints()
    {
        // Test user profile endpoint
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);

        // Test logout endpoint
        $response = $this->postJson('/api/logout');
        $response->assertStatus(401);

        // Test todos endpoint
        $response = $this->getJson('/api/todos');
        $response->assertStatus(401);
    }

    /**
     * Test that session is regenerated after login
     * This tests session security
     */
    public function test_session_is_regenerated_after_login()
    {
        // Arrange: Create user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // Act: Login and check session
        $response = $this->postJson('/api/login', $loginData);

        // Assert: Login successful
        $response->assertStatus(200);

        // Check that user is authenticated
        $this->assertAuthenticated();

        // Verify session contains user
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    /**
     * Test that session is invalidated after logout
     * This tests logout security
     */
    public function test_session_is_invalidated_after_logout()
    {
        // Arrange: Create and authenticate user
        $user = User::factory()->create();

        // Act: Logout
        $response = $this->actingAs($user)
            ->postJson('/api/logout');

        // Assert: Logout successful
        $response->assertStatus(200);

        // Check that user is no longer authenticated
        $this->assertGuest();

        // Verify session is invalidated
        $this->assertFalse(auth()->check());
    }

    /**
     * Test that registration automatically logs in the user
     * This tests auto-login after registration
     */
    public function test_registration_automatically_logs_in_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];

        // Act: Register user
        $response = $this->postJson('/api/register', $userData);

        // Assert: Registration successful
        $response->assertStatus(201);

        // Check that user is automatically authenticated
        $this->assertAuthenticated();

        // Verify it's the correct user
        $this->assertEquals('john@example.com', auth()->user()->email);
    }

    /**
     * Test that password is required for registration
     * This tests password validation
     */
    public function test_password_is_required_for_registration()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
            // password missing
        ];

        // Act: Try to register without password
        $response = $this->postJson('/api/register', $userData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test that name is required for registration
     * This tests name validation
     */
    public function test_name_is_required_for_registration()
    {
        $userData = [
            'email' => 'john@example.com',
            'password' => 'password123'
            // name missing
        ];

        // Act: Try to register without name
        $response = $this->postJson('/api/register', $userData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test that email is required for login
     * This tests login validation
     */
    public function test_email_is_required_for_login()
    {
        $loginData = [
            'password' => 'password123'
            // email missing
        ];

        // Act: Try to login without email
        $response = $this->postJson('/api/login', $loginData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that password is required for login
     * This tests login validation
     */
    public function test_password_is_required_for_login()
    {
        $loginData = [
            'email' => 'test@example.com'
            // password missing
        ];

        // Act: Try to login without password
        $response = $this->postJson('/api/login', $loginData);

        // Assert: Validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
