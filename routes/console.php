<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('cache:warm-attendance')->dailyAt('07:00');
