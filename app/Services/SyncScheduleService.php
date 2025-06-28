<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\SyncSchedule;
use App\Jobs\SyncVendorInvoicesJob;
use App\Jobs\SyncVendorCustomersJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;

class SyncScheduleService
{
    public function createSchedule(IntegrationSetting $integration, array $scheduleData): SyncSchedule
    {
        return SyncSchedule::create([
            'integration_setting_id' => $integration->id,
            'sync_type' => $scheduleData['sync_type'],
            'frequency' => $scheduleData['frequency'],
            'frequency_value' => $scheduleData['frequency_value'] ?? null,
            'time_of_day' => $scheduleData['time_of_day'] ?? null,
            'day_of_week' => $scheduleData['day_of_week'] ?? null,
            'day_of_month' => $scheduleData['day_of_month'] ?? null,
            'timezone' => $scheduleData['timezone'] ?? config('app.timezone'),
            'is_active' => $scheduleData['is_active'] ?? true,
            'filters' => $scheduleData['filters'] ?? []
        ]);
    }

    public function updateSchedule(SyncSchedule $schedule, array $scheduleData): SyncSchedule
    {
        $schedule->update($scheduleData);
        return $schedule->fresh();
    }

    public function deleteSchedule(SyncSchedule $schedule): bool
    {
        return $schedule->delete();
    }

    public function getActiveSchedules(): \Illuminate\Database\Eloquent\Collection
    {
        return SyncSchedule::with('integrationSetting')
            ->where('is_active', true)
            ->whereHas('integrationSetting', function ($query) {
                $query->where('is_active', true);
            })
            ->get();
    }

    public function shouldRunNow(SyncSchedule $schedule): bool
    {
        if (!$schedule->is_active || !$schedule->integrationSetting->is_active) {
            return false;
        }

        $now = now($schedule->timezone);
        $lastRun = $schedule->last_run_at ?
            $schedule->last_run_at->setTimezone($schedule->timezone) : null;

        return match ($schedule->frequency) {
            'hourly' => $this->shouldRunHourly($schedule, $now, $lastRun),
            'daily' => $this->shouldRunDaily($schedule, $now, $lastRun),
            'weekly' => $this->shouldRunWeekly($schedule, $now, $lastRun),
            'monthly' => $this->shouldRunMonthly($schedule, $now, $lastRun),
            'custom' => $this->shouldRunCustom($schedule, $now, $lastRun),
            default => false
        };
    }

    public function executeSchedule(SyncSchedule $schedule): void
    {
        try {
            Log::info("Executing scheduled sync", [
                'schedule_id' => $schedule->id,
                'integration_id' => $schedule->integration_setting_id,
                'sync_type' => $schedule->sync_type
            ]);

            $integration = $schedule->integrationSetting;
            $filters = $schedule->filters ?? [];

            switch ($schedule->sync_type) {
                case 'invoices':
                    SyncVendorInvoicesJob::dispatch($integration, $filters);
                    break;
                case 'customers':
                    SyncVendorCustomersJob::dispatch($integration, $filters);
                    break;
                case 'all':
                    SyncVendorInvoicesJob::dispatch($integration, $filters);
                    SyncVendorCustomersJob::dispatch($integration, $filters);
                    break;
            }

            $schedule->update([
                'last_run_at' => now(),
                'next_run_at' => $this->calculateNextRun($schedule),
                'run_count' => $schedule->run_count + 1
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to execute scheduled sync", [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage()
            ]);

            $schedule->update([
                'last_error' => $e->getMessage(),
                'error_count' => $schedule->error_count + 1
            ]);

            throw $e;
        }
    }

    public function calculateNextRun(SyncSchedule $schedule): ?\Carbon\Carbon
    {
        $now = now($schedule->timezone);

        return match ($schedule->frequency) {
            'hourly' => $this->calculateNextHourly($schedule, $now),
            'daily' => $this->calculateNextDaily($schedule, $now),
            'weekly' => $this->calculateNextWeekly($schedule, $now),
            'monthly' => $this->calculateNextMonthly($schedule, $now),
            'custom' => $this->calculateNextCustom($schedule, $now),
            default => null
        };
    }

    protected function shouldRunHourly(SyncSchedule $schedule, $now, $lastRun): bool
    {
        if (!$lastRun) return true;

        $intervalHours = $schedule->frequency_value ?? 1;
        return $now->diffInHours($lastRun) >= $intervalHours;
    }

    protected function shouldRunDaily(SyncSchedule $schedule, $now, $lastRun): bool
    {
        if (!$lastRun) return true;

        $timeOfDay = $schedule->time_of_day ?? '00:00';
        $targetTime = $now->copy()->setTimeFromTimeString($timeOfDay);

        // If target time has passed today and we haven't run today
        if ($now->gte($targetTime) && (!$lastRun || $lastRun->format('Y-m-d') !== $now->format('Y-m-d'))) {
            return true;
        }

        return false;
    }

    protected function shouldRunWeekly(SyncSchedule $schedule, $now, $lastRun): bool
    {
        if (!$lastRun) return true;

        $dayOfWeek = $schedule->day_of_week ?? 1; // Monday
        $timeOfDay = $schedule->time_of_day ?? '00:00';

        if ($now->dayOfWeek === $dayOfWeek) {
            $targetTime = $now->copy()->setTimeFromTimeString($timeOfDay);

            if ($now->gte($targetTime) && $now->diffInDays($lastRun) >= 7) {
                return true;
            }
        }

        return false;
    }

    protected function shouldRunMonthly(SyncSchedule $schedule, $now, $lastRun): bool
    {
        if (!$lastRun) return true;

        $dayOfMonth = $schedule->day_of_month ?? 1;
        $timeOfDay = $schedule->time_of_day ?? '00:00';

        if ($now->day === $dayOfMonth) {
            $targetTime = $now->copy()->setTimeFromTimeString($timeOfDay);

            if ($now->gte($targetTime) && $now->diffInMonths($lastRun) >= 1) {
                return true;
            }
        }

        return false;
    }

    protected function shouldRunCustom(SyncSchedule $schedule, $now, $lastRun): bool
    {
        // Custom frequency logic based on cron-like expressions
        // This would need more complex implementation
        return false;
    }

    protected function calculateNextHourly(SyncSchedule $schedule, $now): \Carbon\Carbon
    {
        $intervalHours = $schedule->frequency_value ?? 1;
        return $now->copy()->addHours($intervalHours);
    }

    protected function calculateNextDaily(SyncSchedule $schedule, $now): \Carbon\Carbon
    {
        $timeOfDay = $schedule->time_of_day ?? '00:00';
        $nextRun = $now->copy()->addDay()->setTimeFromTimeString($timeOfDay);

        return $nextRun;
    }

    protected function calculateNextWeekly(SyncSchedule $schedule, $now): \Carbon\Carbon
    {
        $dayOfWeek = $schedule->day_of_week ?? 1;
        $timeOfDay = $schedule->time_of_day ?? '00:00';

        $nextRun = $now->copy()->next($dayOfWeek)->setTimeFromTimeString($timeOfDay);

        return $nextRun;
    }

    protected function calculateNextMonthly(SyncSchedule $schedule, $now): \Carbon\Carbon
    {
        $dayOfMonth = $schedule->day_of_month ?? 1;
        $timeOfDay = $schedule->time_of_day ?? '00:00';

        $nextRun = $now->copy()->addMonth()->day($dayOfMonth)->setTimeFromTimeString($timeOfDay);

        return $nextRun;
    }

    protected function calculateNextCustom(SyncSchedule $schedule, $now): ?\Carbon\Carbon
    {
        // Custom calculation logic
        return null;
    }

    public function registerScheduledTasks(Schedule $schedule): void
    {
        $schedule->call(function () {
            $activeSchedules = $this->getActiveSchedules();

            foreach ($activeSchedules as $syncSchedule) {
                if ($this->shouldRunNow($syncSchedule)) {
                    $this->executeSchedule($syncSchedule);
                }
            }
        })->everyMinute()->name('vendor-sync-scheduler');
    }
}
