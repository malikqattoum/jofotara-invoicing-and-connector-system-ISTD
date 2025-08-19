<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function audit_log_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));

        $expectedColumns = [
            'id', 'user_id', 'action', 'description', 'severity', 'is_security_related', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('audit_logs', $column),
                "AuditLog table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function audit_log_belongs_to_user()
    {
        $user = User::factory()->create();
        $log = AuditLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);
    }

    /** @test */
    public function audit_log_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'user_id', 'action', 'description', 'severity', 'is_security_related'
        ];

        $log = new AuditLog();
        $actualFillable = $log->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function audit_log_can_check_critical_status()
    {
        $criticalLog = AuditLog::factory()->create(['severity' => 'critical']);
        $this->assertTrue($criticalLog->isCritical());

        $nonCriticalLog = AuditLog::factory()->create(['severity' => 'info']);
        $this->assertFalse($nonCriticalLog->isCritical());
    }

    /** @test */
    public function audit_log_can_check_security_related_status()
    {
        $securityLog = AuditLog::factory()->create(['is_security_related' => true]);
        $this->assertTrue($securityLog->isSecurityRelated());

        $nonSecurityLog = AuditLog::factory()->create(['is_security_related' => false]);
        $this->assertFalse($nonSecurityLog->isSecurityRelated());
    }
}
