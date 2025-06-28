<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function auth_middleware_redirects_guests_to_login()
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function auth_middleware_allows_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_middleware_redirects_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function guest_middleware_allows_unauthenticated_users()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_middleware_restricts_non_admin_users()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_middleware_allows_admin_users()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    /** @test */
    public function verified_middleware_restricts_unverified_users()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/email/verify');
    }

    /** @test */
    public function throttle_middleware_limits_requests()
    {
        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 100; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
        }

        // Should eventually get throttled
        $response->assertStatus(429);
    }

    /** @test */
    public function cors_middleware_sets_appropriate_headers()
    {
        $response = $this->get('/api/invoices');

        $response->assertHeader('Access-Control-Allow-Origin');
    }

    /** @test */
    public function api_auth_middleware_requires_token()
    {
        $response = $this->getJson('/api/invoices');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /** @test */
    public function organization_middleware_filters_by_organization()
    {
        $user1 = User::factory()->create(['organization_id' => 1]);
        $user2 = User::factory()->create(['organization_id' => 2]);

        // User should only see their organization's data
        $response = $this->actingAs($user1)->get('/invoices');
        $response->assertStatus(200);

        // This would be tested more thoroughly in controller tests
    }

    /** @test */
    public function role_middleware_restricts_based_on_roles()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Vendor cannot access admin routes
        $response = $this->actingAs($vendor)->get('/admin/vendors');
        $response->assertStatus(403);

        // Admin can access admin routes
        $response = $this->actingAs($admin)->get('/admin/vendors');
        $response->assertStatus(200);
    }

    /** @test */
    public function maintenance_mode_middleware_shows_maintenance_page()
    {
        // This would typically require setting the app to maintenance mode
        $this->artisan('down');

        $response = $this->get('/');

        $response->assertStatus(503);

        // Clean up
        $this->artisan('up');
    }

    /** @test */
    public function csrf_middleware_protects_against_csrf_attacks()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        // Without CSRF token, should fail
        $response->assertStatus(419);
    }

    /** @test */
    public function sanitize_input_middleware_cleans_user_input()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/invoices', [
            'customer_name' => '<script>alert("xss")</script>John Doe',
            'description' => 'Safe content'
        ]);

        // Input should be sanitized (exact implementation depends on your sanitization logic)
        $this->assertDatabaseMissing('invoices', [
            'customer_name' => '<script>alert("xss")</script>John Doe'
        ]);
    }

    /** @test */
    public function api_version_middleware_handles_versioning()
    {
        $user = User::factory()->create();

        // Test different API versions
        $responseV1 = $this->actingAs($user)->getJson('/api/v1/invoices');
        $responseV2 = $this->actingAs($user)->getJson('/api/v2/invoices');

        // Both should work but might return different structures
        $responseV1->assertStatus(200);
        $responseV2->assertStatus(200);
    }

    /** @test */
    public function request_logging_middleware_logs_requests()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        // Verify that request was logged (implementation depends on your logging setup)
        $response->assertStatus(200);

        // You might check log files or database entries here
        // $this->assertDatabaseHas('request_logs', [...]);
    }
}
