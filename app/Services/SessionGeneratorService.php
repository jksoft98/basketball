<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\TrainingSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SessionGeneratorService
{
    /**
     * Generate sessions for all active batches (or a specific batch)
     * within the given date range.
     *
     * Safe to run multiple times — skips sessions that already exist
     * for the same batch + date + type combination.
     *
     * Returns ['created' => int, 'skipped' => int, 'details' => array]
     */
    public function generateForPeriod(
        Carbon $from,
        Carbon $to,
        ?int   $batchId  = null,
        int    $createdBy = 1
    ): array {
        $created = 0;
        $skipped = 0;
        $details = [];

        // Resolve who is creating (defaults to first admin if no auth)
        $userId = auth()->id() ?? $createdBy;

        $query = Batch::active()->with(['schedules' => fn($q) => $q->where('is_active', true)]);

        if ($batchId) {
            $query->where('id', $batchId);
        }

        $batches = $query->get();

        foreach ($batches as $batch) {
            if ($batch->schedules->isEmpty()) {
                $details[] = [
                    'batch'   => $batch->name,
                    'status'  => 'skipped',
                    'reason'  => 'No schedule defined',
                    'created' => 0,
                ];
                continue;
            }

            $batchCreated = 0;

            // Walk every calendar day in the range
            $period = CarbonPeriod::create($from->copy(), $to->copy());

            foreach ($period as $date) {
                foreach ($batch->schedules as $schedule) {

                    // Does this calendar day match the schedule's weekday?
                    if ($date->dayOfWeek !== $schedule->day_of_week) {
                        continue;
                    }

                    // Skip if a session already exists for this batch + date + type
                    $exists = TrainingSession::where('batch_id', $batch->id)
                        ->whereDate('session_date', $date->toDateString())
                        ->where('session_type', $schedule->session_type)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    TrainingSession::create([
                        'batch_id'     => $batch->id,
                        'created_by'   => $userId,
                        'session_date' => $date->toDateString(),
                        'session_time' => $schedule->session_time,
                        'session_type' => $schedule->session_type,
                        'notes'        => null,
                    ]);

                    $created++;
                    $batchCreated++;
                }
            }

            $details[] = [
                'batch'   => $batch->name,
                'status'  => 'ok',
                'created' => $batchCreated,
            ];
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'details' => $details,
        ];
    }

    /**
     * Generate for the current week (Monday → Sunday)
     */
    public function generateForCurrentWeek(?int $batchId = null): array
    {
        return $this->generateForPeriod(
            now()->startOfWeek(Carbon::MONDAY),
            now()->endOfWeek(Carbon::SUNDAY),
            $batchId
        );
    }

    /**
     * Generate for the coming week (next Mon → next Sun)
     */
    public function generateForNextWeek(?int $batchId = null): array
    {
        return $this->generateForPeriod(
            now()->addWeek()->startOfWeek(Carbon::MONDAY),
            now()->addWeek()->endOfWeek(Carbon::SUNDAY),
            $batchId
        );
    }

    /**
     * Generate for the current month
     */
    public function generateForCurrentMonth(?int $batchId = null): array
    {
        return $this->generateForPeriod(
            now()->startOfMonth(),
            now()->endOfMonth(),
            $batchId
        );
    }

    /**
     * Generate for a specific month (e.g. "2026-05")
     */
    public function generateForMonth(string $yearMonth, ?int $batchId = null): array
    {
        $date = Carbon::createFromFormat('Y-m', $yearMonth);

        return $this->generateForPeriod(
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth(),
            $batchId
        );
    }
}
