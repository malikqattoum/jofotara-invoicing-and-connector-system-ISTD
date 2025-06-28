<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true
        ]);
    }

    /** @test */
    public function admin_can_view_vendors_index()
    {
        // Create some vendor users
        User::factory(3)->create(['role' => 'vendor']);

        $response = $this->actingAs($this->admin)->get('/admin/vendors');

        $response->assertStatus(200);
        $response->assertViewIs('admin.vendors');
        $response->assertViewHas('vendors');
    }

    /** @test */
    public function non_admin_cannot_access_admin_panel()
    {
        $response = $this->actingAs($this->regularUser)->get('/admin');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_panel()
    {
        $response = $this->get('/admin');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function admin_can_toggle_vendor_status()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
                         ->post("/admin/vendors/{$vendor->id}/toggle");

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertFalse($vendor->fresh()->is_active);
    }

    /** @test */
    public function admin_can_toggle_vendor_status_from_inactive_to_active()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'is_active' => false
        ]);

        $response = $this->actingAs($this->admin)
                         ->post("/admin/vendors/{$vendor->id}/toggle");

        $response->assertStatus(302);
        $this->assertTrue($vendor->fresh()->is_active);
    }

    /** @test */
    public function admin_cannot_toggle_non_vendor_user()
    {
        $regularUser = User::factory()->create([
            'role' => 'user',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
                         ->post("/admin/vendors/{$regularUser->id}/toggle");

        $response->assertStatus(404);
    }

    /** @test */
    public function non_admin_cannot_toggle_vendor_status()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->regularUser)
                         ->post("/admin/vendors/{$vendor->id}/toggle");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_admin_panel()
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.panel');
    }

    /** @test */
    public function vendors_index_shows_only_vendor_users()
    {
        // Create different types of users
        $vendor1 = User::factory()->create(['role' => 'vendor']);
        $vendor2 = User::factory()->create(['role' => 'vendor']);
        $adminUser = User::factory()->create(['role' => 'admin']);
        $regularUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($this->admin)->get('/admin/vendors');

        $response->assertStatus(200);

        $vendors = $response->viewData('vendors');
        $this->assertCount(2, $vendors);
        $this->assertTrue($vendors->contains($vendor1));
        $this->assertTrue($vendors->contains($vendor2));
        $this->assertFalse($vendors->contains($adminUser));
        $this->assertFalse($vendors->contains($regularUser));
    }

    /** @test */
    public function toggle_vendor_returns_with_success_message()
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->admin)
                         ->post("/admin/vendors/{$vendor->id}/toggle");

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Vendor status updated.');
    }

    /** @test */
    public function admin_routes_require_admin_panel_permission()
    {
        // This tests the middleware requirement
        $userWithoutPermission = User::factory()->create([
            'role' => 'user'
        ]);

        $routes = [
            '/admin',
            '/admin/vendors'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($userWithoutPermission)->get($route);
            $response->assertStatus(403);
        }
    }
}
