<?php

namespace App\Console\Commands;

use App\Services\SessionGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklySessions extends Command
{
    protected $signature = 'sessions:generate
                            {--week=next   : Which week — "current" or "next" (default: next)}
                            {--month=      : Generate for a specific month e.g. 2026-05}
                            {--from=       : Custom start date e.g. 2026-05-01}
                            {--to=         : Custom end date e.g. 2026-05-31}
                            {--batch=      : Limit to a specific batch ID}
                            {--dry-run     : Preview what would be created without saving}';

    protected $description = 'Generate training sessions from batch weekly schedules';

    private SessionGeneratorService $generator;

    public function __construct(SessionGeneratorService $generator)
    {
        parent::__construct(); // Must be called before anything else
        $this->generator = $generator;
    }

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $batchId = $this->option('batch') ? (int) $this->option('batch') : null;

        // Resolve date range
        if ($this->option('from') && $this->option('to')) {
            $from  = Carbon::parse($this->option('from'))->startOfDay();
            $to    = Carbon::parse($this->option('to'))->endOfDay();
            $label = $from->format('d M Y') . ' → ' . $to->format('d M Y');

        } elseif ($this->option('month')) {
            $date  = Carbon::createFromFormat('Y-m', $this->option('month'));
            $from  = $date->copy()->startOfMonth();
            $to    = $date->copy()->endOfMonth();
            $label = $date->format('F Y');

        } elseif ($this->option('week') === 'current') {
            $from  = now()->startOfWeek(Carbon::MONDAY);
            $to    = now()->endOfWeek(Carbon::SUNDAY);
            $label = 'Current week (' . $from->format('d M') . ' → ' . $to->format('d M') . ')';

        } else {
            $from  = now()->addWeek()->startOfWeek(Carbon::MONDAY);
            $to    = now()->addWeek()->endOfWeek(Carbon::SUNDAY);
            $label = 'Next week (' . $from->format('d M') . ' → ' . $to->format('d M') . ')';
        }

        $this->newLine();
        $this->line('  🏀 <comment>Basketball Academy — Session Generator</comment>');
        $this->line('  Period : <info>' . $label . '</info>');
        $this->line('  Batch  : <info>' . ($batchId ? 'ID ' . $batchId : 'All active batches') . '</info>');

        if ($dryRun) {
            $this->line('  Mode   : <fg=yellow>DRY RUN — nothing will be saved</>');
            $this->newLine();
            $this->previewGeneration($from, $to, $batchId);
            return self::SUCCESS;
        }

        $this->newLine();

        if ($this->input->isInteractive()) {
            if (!$this->confirm('Generate sessions for the period above?', true)) {
                $this->line('  <comment>Cancelled.</comment>');
                return self::SUCCESS;
            }
            $this->newLine();
        }

        $this->line('  Generating sessions...');

        $result = $this->generator->generateForPeriod($from, $to, $batchId);

        $rows = array_map(function ($detail) {
            return [
                $detail['batch'],
                $detail['status'] === 'ok'
                    ? '<info>OK</info>'
                    : '<comment>' . ($detail['reason'] ?? 'skipped') . '</comment>',
                $detail['status'] === 'ok'
                    ? ($detail['created'] > 0
                        ? '<info>' . $detail['created'] . '</info>'
                        : '<comment>0 (already exist)</comment>')
                    : '—',
            ];
        }, $result['details']);

        $this->table(['Batch', 'Status', 'Sessions Created'], $rows);

        $this->newLine();
        $this->line('  ✅ <info>Created : ' . $result['created'] . ' sessions</info>');
        $this->line('     Skipped : ' . $result['skipped'] . ' (already existed)');
        $this->newLine();

        if ($result['created'] === 0 && $result['skipped'] === 0) {
            $this->warn('  No schedules found. Set up batch schedules in the admin panel first.');
        }

        return self::SUCCESS;
    }

    private function previewGeneration(Carbon $from, Carbon $to, ?int $batchId): void
    {
        $this->line('  <comment>Preview — sessions that WOULD be created:</comment>');
        $this->newLine();

        $query = \App\Models\Batch::active()
            ->with(['schedules' => fn($q) => $q->where('is_active', true)]);

        if ($batchId) {
            $query->where('id', $batchId);
        }

        $batches = $query->get();
        $rows    = [];

        foreach ($batches as $batch) {
            if ($batch->schedules->isEmpty()) continue;

            $period = \Carbon\CarbonPeriod::create($from->copy(), $to->copy());

            foreach ($period as $date) {
                foreach ($batch->schedules as $schedule) {
                    if ($date->dayOfWeek !== $schedule->day_of_week) continue;

                    $exists = \App\Models\TrainingSession::where('batch_id', $batch->id)
                        ->whereDate('session_date', $date->toDateString())
                        ->where('session_type', $schedule->session_type)
                        ->exists();

                    $rows[] = [
                        $date->format('D d M Y'),
                        $batch->name,
                        ucfirst($schedule->session_type),
                        $schedule->formatted_time,
                        $exists ? '<comment>SKIP (exists)</comment>' : '<info>CREATE</info>',
                    ];
                }
            }
        }

        if (empty($rows)) {
            $this->warn('  Nothing to generate. Check that batches have active schedules.');
            return;
        }

        $this->table(['Date', 'Batch', 'Type', 'Time', 'Action'], $rows);
        $this->line('  Run without <comment>--dry-run</comment> to save.');
    }
}
