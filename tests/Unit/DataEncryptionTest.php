<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\DataEncryption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class DataEncryptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function data_encryption_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('data_encryptions'));

        $expectedColumns = [
            'id', 'key', 'iv', 'version', 'is_current', 'created_at', 'updated_at'
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('data_encryptions', $column),
                "DataEncryption table is missing column: {$column}"
            );
        }
    }

    /** @test */
    public function data_encryption_has_expected_fillable_fields()
    {
        $expectedFillable = [
            'key', 'iv', 'version', 'is_current'
        ];

        $encryption = new DataEncryption();
        $actualFillable = $encryption->getFillable();

        foreach ($expectedFillable as $field) {
            $this->assertContains($field, $actualFillable);
        }
    }

    /** @test */
    public function data_encryption_can_check_current_version()
    {
        $currentEncryption = DataEncryption::factory()->create(['is_current' => true]);
        $this->assertTrue($currentEncryption->isCurrentVersion());

        $oldEncryption = DataEncryption::factory()->create(['is_current' => false]);
        $this->assertFalse($oldEncryption->isCurrentVersion());
    }

    /** @test */
    public function data_encryption_ensures_only_one_current_version()
    {
        DataEncryption::factory()->create(['is_current' => true]);
        $newEncryption = DataEncryption::factory()->create(['is_current' => true]);

        $this->assertFalse(DataEncryption::where('id', '!=', $newEncryption->id)->where('is_current', true)->exists());
        $this->assertTrue($newEncryption->fresh()->is_current);
    }
}
