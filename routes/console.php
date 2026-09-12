<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('opportunities:expire')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('opportunities:weekly-digest')
    ->weeklyOn(1, '08:00')
    ->timezone('Europe/Berlin')
    ->withoutOverlapping();
