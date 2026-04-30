<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('cache:warm-attendance')->dailyAt('07:00');

// Auto-generate sessions every Sunday evening for the coming week
Schedule::command('sessions:generate-weekly')->weeklyOn(0, '20:00');
