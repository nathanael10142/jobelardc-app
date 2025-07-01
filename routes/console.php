<?php

use App\Console\Commands\DeleteUnboostedListings;
use Illuminate\Foundation\Scheduling\Schedule;

return function (Schedule $schedule) {
    // Planifie la commande DeleteUnboostedListings tous les jours
    $schedule->command(DeleteUnboostedListings::class)->daily();
};
