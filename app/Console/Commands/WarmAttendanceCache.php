<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmAttendanceCache extends Command
{
    protected $signature   = 'cache:warm-attendance';
    protected $description = 'Pre-warm student photo grids for all active batches';

    public function handle(): void
    {
        $batches = Batch::active()->get();

        foreach ($batches as $batch) {
            Cache::remember(
                "batch_students_{$batch->id}",
                now()->addMinutes(30),
                fn() => Student::where('batch_id', $batch->id)
                    ->active()->orderBy('full_name')->get()
            );
            $this->info("Warmed cache for: {$batch->name}");
        }

        $this->info('Cache warmed successfully.');
    }
}
