<?php

namespace Tests;

use PHPUnit\Framework\TestSuite as BaseTestSuite;

class TestSuite extends BaseTestSuite
{
    public static function suite()
    {
        $suite = new static('JO Invoicing Complete Test Suite');

        // Authentication Tests
        $suite->addTestSuite(\Tests\Unit\AuthenticationTest::class);
        $suite->addTestSuite(\Tests\Feature\AuthenticationControllerTest::class);

        // Invoice Tests
        $suite->addTestSuite(\Tests\Unit\InvoiceTest::class);
        $suite->addTestSuite(\Tests\Feature\InvoiceControllerTest::class);

        // Integration Tests
        $suite->addTestSuite(\Tests\Unit\IntegrationSettingTest::class);

        // Workflow Tests
        $suite->addTestSuite(\Tests\Unit\WorkflowTest::class);

        // Data Pipeline Tests
        $suite->addTestSuite(\Tests\Unit\DataPipelineTest::class);

        // Event Stream Tests
        $suite->addTestSuite(\Tests\Unit\EventStreamTest::class);

        // Data Source Tests
        $suite->addTestSuite(\Tests\Unit\DataSourceTest::class);

        // System Alert Tests
        $suite->addTestSuite(\Tests\Unit\SystemAlertTest::class);

        // Performance Metric Tests
        $suite->addTestSuite(\Tests\Unit\PerformanceMetricTest::class);

        // Sync Log Tests
        $suite->addTestSuite(\Tests\Unit\SyncLogTest::class);

        // Admin Tests
        $suite->addTestSuite(\Tests\Feature\AdminControllerTest::class);

        // Dashboard Tests
        $suite->addTestSuite(\Tests\Feature\DashboardControllerTest::class);

        return $suite;
    }
}
